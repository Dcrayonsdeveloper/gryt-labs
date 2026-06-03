<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class TwoFactorService
{
    /**
     * Base32 alphabet per RFC 4648.
     */
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * TOTP time step in seconds (RFC 6238 default).
     */
    private const PERIOD = 30;

    /**
     * Number of digits in the TOTP code.
     */
    private const DIGITS = 6;

    /**
     * Number of time windows to check before/after current for clock skew.
     */
    private const WINDOW = 1;

    /**
     * Generate a new random TOTP secret (base32-encoded, 20 bytes = 160 bits).
     */
    public function generateSecret(): string
    {
        $randomBytes = random_bytes(20);

        return $this->base32Encode($randomBytes);
    }

    /**
     * Generate an encrypted secret suitable for database storage.
     */
    public function generateEncryptedSecret(): array
    {
        $secret = $this->generateSecret();

        return [
            'plain' => $secret,
            'encrypted' => Crypt::encryptString($secret),
        ];
    }

    /**
     * Decrypt a stored secret.
     */
    public function decryptSecret(string $encryptedSecret): string
    {
        return Crypt::decryptString($encryptedSecret);
    }

    /**
     * Generate a TOTP code for the given secret at the current time.
     */
    public function generateCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $timeCounter = intdiv($timestamp, self::PERIOD);

        return $this->hotpCode($secret, $timeCounter);
    }

    /**
     * Verify a TOTP code against the secret, checking +/- WINDOW periods for clock skew.
     *
     * Returns true if the code matches any valid window.
     */
    public function verifyCode(string $secret, string $code): bool
    {
        // Normalize: strip spaces, ensure string
        $code = trim(str_replace(' ', '', $code));

        if (strlen($code) !== self::DIGITS || !ctype_digit($code)) {
            return false;
        }

        $timestamp = time();
        $currentCounter = intdiv($timestamp, self::PERIOD);

        for ($offset = -self::WINDOW; $offset <= self::WINDOW; $offset++) {
            $testCounter = $currentCounter + $offset;
            $expectedCode = $this->hotpCode($secret, $testCounter);

            if (hash_equals($expectedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate an otpauth:// URI for QR code scanning (Google Authenticator, Authy, etc.).
     */
    public function generateQrCodeUrl(string $secret, string $email, ?string $issuer = null): string
    {
        $issuer = $issuer ?: (\App\Models\Setting::get('store_name', config('app.name')));

        // URI format: otpauth://totp/{issuer}:{email}?secret={secret}&issuer={issuer}&digits=6&period=30
        $label = rawurlencode($issuer) . ':' . rawurlencode($email);

        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ], '', '&', PHP_QUERY_RFC3986);

        return 'otpauth://totp/' . $label . '?' . $params;
    }

    /**
     * Generate a Google Charts QR code image URL for the otpauth URI.
     * This is a simple approach that doesn't require any PHP packages.
     */
    public function generateQrCodeImageUrl(string $otpauthUrl): string
    {
        return 'https://chart.googleapis.com/chart?'
            . http_build_query([
                'chs' => '200x200',
                'chld' => 'M|0',
                'cht' => 'qr',
                'chl' => $otpauthUrl,
            ]);
    }

    /**
     * Generate 8 single-use recovery codes.
     * Each code is 10 alphanumeric characters formatted as xxxxx-xxxxx.
     */
    public function generateRecoveryCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < 8; $i++) {
            $code = Str::lower(Str::random(10));
            // Format as xxxxx-xxxxx for readability
            $codes[] = substr($code, 0, 5) . '-' . substr($code, 5, 5);
        }

        return $codes;
    }

    /**
     * Encrypt recovery codes for database storage.
     */
    public function encryptRecoveryCodes(array $codes): string
    {
        return Crypt::encryptString(json_encode($codes));
    }

    /**
     * Decrypt recovery codes from database storage.
     */
    public function decryptRecoveryCodes(string $encrypted): array
    {
        return json_decode(Crypt::decryptString($encrypted), true) ?: [];
    }

    /**
     * Check a recovery code and remove it if valid (one-time use).
     * Returns the remaining codes if valid, or null if invalid.
     */
    public function verifyRecoveryCode(string $encryptedCodes, string $code): ?array
    {
        $codes = $this->decryptRecoveryCodes($encryptedCodes);
        $code = trim(Str::lower($code));

        $index = array_search($code, $codes, true);

        if ($index === false) {
            return null;
        }

        // Remove the used code
        array_splice($codes, $index, 1);

        return $codes;
    }

    // ─── Internal HOTP Implementation (RFC 4226) ────────────────────────

    /**
     * Generate an HOTP code per RFC 4226.
     */
    private function hotpCode(string $base32Secret, int $counter): string
    {
        // Decode the base32 secret to raw bytes
        $key = $this->base32Decode($base32Secret);

        // Pack counter as 64-bit big-endian unsigned integer
        $message = pack('N*', 0, $counter);

        // HMAC-SHA1
        $hash = hash_hmac('sha1', $message, $key, true);

        // Dynamic truncation (RFC 4226 Section 5.4)
        $offset = ord($hash[19]) & 0x0F;

        $binary =
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF);

        $otp = $binary % (10 ** self::DIGITS);

        return str_pad((string) $otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Encode raw bytes to Base32 (RFC 4648).
     */
    private function base32Encode(string $data): string
    {
        $binary = '';
        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        $chunks = str_split($binary, 5);

        foreach ($chunks as $chunk) {
            // Pad the last chunk to 5 bits if needed
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $encoded .= self::BASE32_ALPHABET[bindec($chunk)];
        }

        return $encoded;
    }

    /**
     * Decode a Base32 string back to raw bytes (RFC 4648).
     */
    private function base32Decode(string $base32): string
    {
        $base32 = strtoupper(rtrim($base32, '='));
        $binary = '';

        for ($i = 0, $len = strlen($base32); $i < $len; $i++) {
            $pos = strpos(self::BASE32_ALPHABET, $base32[$i]);

            if ($pos === false) {
                continue; // skip invalid characters
            }

            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        $chunks = str_split($binary, 8);

        foreach ($chunks as $chunk) {
            if (strlen($chunk) < 8) {
                break; // discard incomplete trailing bits
            }

            $bytes .= chr(bindec($chunk));
        }

        return $bytes;
    }
}
