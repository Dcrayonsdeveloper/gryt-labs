<?php

namespace App\Services;

use App\Models\Setting;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    private const MAX_OTPS_PER_DAY = 5;
    private const OTP_EXPIRY_MINUTES = 10;
    private const OTP_LENGTH = 6;

    /**
     * Generate and send OTP to phone (WhatsApp) and/or email.
     */
    public function send(string $identifier, string $purpose = 'login', bool $viaWhatsApp = true, bool $viaEmail = true): array
    {
        // Rate limit: max 5 OTPs per identifier per day
        $todayCount = DB::table('otp_codes')
            ->where('identifier', $identifier)
            ->whereDate('created_at', today())
            ->count();

        if ($todayCount >= self::MAX_OTPS_PER_DAY) {
            return ['success' => false, 'message' => 'Too many OTP requests. Try again tomorrow.'];
        }

        // Invalidate previous unused OTPs
        DB::table('otp_codes')
            ->where('identifier', $identifier)
            ->where('purpose', $purpose)
            ->where('used', false)
            ->update(['used' => true]);

        // Generate OTP
        $code = str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);

        DB::table('otp_codes')->insert([
            'identifier' => $identifier,
            'code' => $code,
            'purpose' => $purpose,
            'used' => false,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sent = false;

        // Send via WhatsApp if identifier is a phone number
        if ($viaWhatsApp && $this->isPhone($identifier)) {
            $sent = $this->sendWhatsApp($identifier, $code, $purpose);
        }

        // Send via email if identifier is an email
        if ($viaEmail && filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $sent = $this->sendEmail($identifier, $code, $purpose) || $sent;
        }

        // If identifier is a phone, also try to find email and send there
        if ($this->isPhone($identifier)) {
            $user = \App\Models\User::where('phone', $this->cleanPhone($identifier))->first();
            if ($user && $user->email && $viaEmail) {
                $this->sendEmail($user->email, $code, $purpose);
            }
        }

        return [
            'success' => $sent,
            'message' => $sent ? 'OTP sent successfully.' : 'Failed to send OTP.',
            'expires_in' => self::OTP_EXPIRY_MINUTES,
        ];
    }

    /**
     * Verify an OTP.
     */
    public function verify(string $identifier, string $code, string $purpose = 'login'): bool
    {
        $otp = DB::table('otp_codes')
            ->where('identifier', $identifier)
            ->where('purpose', $purpose)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->where('attempts', '<', 5)
            ->latest()
            ->first();

        if (!$otp) {
            return false;
        }

        // Increment attempts
        DB::table('otp_codes')->where('id', $otp->id)->increment('attempts');

        if ($otp->code !== $code) {
            return false;
        }

        // Mark as used
        DB::table('otp_codes')->where('id', $otp->id)->update(['used' => true]);

        return true;
    }

    /**
     * Send OTP via WhatsApp.
     */
    private function sendWhatsApp(string $phone, string $code, string $purpose): bool
    {
        $whatsapp = app(WhatsAppService::class);
        if (!$whatsapp->isConfigured()) {
            return false;
        }

        $cleanPhone = $this->cleanPhone($phone);
        $purposeText = match ($purpose) {
            'login' => 'login to your account',
            'reset_password' => 'reset your password',
            'verify_phone' => 'verify your phone number',
            default => 'verify your identity',
        };

        $storeName = Setting::get('store_name', config('app.name'));
        $message = "Your {$storeName} OTP is: *{$code}*\n\nUse this to {$purposeText}.\nValid for " . self::OTP_EXPIRY_MINUTES . " minutes.\n\nDo not share this code with anyone.";

        return $whatsapp->sendText($cleanPhone, $message);
    }

    /**
     * Send OTP via Email.
     */
    private function sendEmail(string $email, string $code, string $purpose): bool
    {
        try {
            Mail::send([], [], function ($m) use ($email, $code, $purpose) {
                $purposeText = match ($purpose) {
                    'login' => 'Login',
                    'reset_password' => 'Password Reset',
                    default => 'Verification',
                };

                $storeName = config('app.name', 'Store');
                $brandColor = Setting::get('primary_color', '') ?: '#334155';
                $m->to($email)
                  ->from(config('mail.from.address'), $storeName)
                  ->subject("Your {$storeName} {$purposeText} OTP: {$code}")
                  ->html("<div style='font-family:sans-serif;max-width:400px;margin:0 auto;padding:20px;'>
                    <h2 style='color:{$brandColor};margin-bottom:10px;'>{$storeName} {$purposeText} OTP</h2>
                    <p style='font-size:14px;color:#333;'>Your one-time password is:</p>
                    <div style='background:#f5f5f5;border:2px dashed {$brandColor};border-radius:8px;padding:15px;text-align:center;margin:15px 0;'>
                        <span style='font-size:32px;font-weight:bold;letter-spacing:8px;color:{$brandColor};'>{$code}</span>
                    </div>
                    <p style='font-size:12px;color:#666;'>Valid for " . self::OTP_EXPIRY_MINUTES . " minutes. Do not share this code.</p>
                    <p style='font-size:12px;color:#999;margin-top:20px;'>— Team " . e(Setting::get('store_name', config('app.name'))) . "</p>
                  </div>");
            });

            return true;
        } catch (\Exception $e) {
            Log::warning('OTP email failed: ' . $e->getMessage());
            return false;
        }
    }

    private function isPhone(string $value): bool
    {
        $clean = preg_replace('/\D/', '', $value);
        return strlen($clean) >= 10;
    }

    private function cleanPhone(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);
        if (!str_starts_with($clean, '91') && strlen($clean) === 10) {
            $clean = '91' . $clean;
        }
        return $clean;
    }
}
