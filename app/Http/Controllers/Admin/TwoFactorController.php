<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function __construct(
        private TwoFactorService $twoFactor,
    ) {}

    /**
     * Show the 2FA setup page with QR code.
     */
    public function setup(Request $request): View
    {
        $user = Auth::guard('admin')->user();

        // Generate a new secret (or reuse one stored in session during setup flow)
        $secret = session('2fa_setup_secret');

        if (!$secret) {
            $secretData = $this->twoFactor->generateEncryptedSecret();
            $secret = $secretData['plain'];
            session(['2fa_setup_secret' => $secret]);
            session(['2fa_setup_encrypted' => $secretData['encrypted']]);
        }

        $otpauthUrl = $this->twoFactor->generateQrCodeUrl($secret, $user->email);
        $qrCodeUrl = $this->twoFactor->generateQrCodeImageUrl($otpauthUrl);

        return view('admin.two-factor.setup', [
            'secret' => $secret,
            'qrCodeUrl' => $qrCodeUrl,
            'isEnabled' => $user->two_factor_enabled,
        ]);
    }

    /**
     * Verify the code and enable 2FA. Shows recovery codes on success.
     */
    public function enable(Request $request): View|RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = Auth::guard('admin')->user();

        $secret = session('2fa_setup_secret');
        $encrypted = session('2fa_setup_encrypted');

        if (!$secret || !$encrypted) {
            return redirect()->route('admin.2fa.setup')
                ->with('error', 'Setup session expired. Please start again.');
        }

        // Verify the code against the setup secret
        if (!$this->twoFactor->verifyCode($secret, $request->input('code'))) {
            return redirect()->route('admin.2fa.setup')
                ->with('error', 'Invalid verification code. Please try again.');
        }

        // Generate recovery codes
        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();

        // Save to user
        $user->two_factor_secret = $encrypted;
        $user->two_factor_enabled = true;
        $user->two_factor_recovery_codes = $this->twoFactor->encryptRecoveryCodes($recoveryCodes);
        $user->save();

        // Mark as verified for this session
        session(['2fa_verified_at' => time()]);

        // Clear setup session data
        session()->forget(['2fa_setup_secret', '2fa_setup_encrypted']);

        return view('admin.two-factor.setup', [
            'recoveryCodes' => $recoveryCodes,
            'justEnabled' => true,
            'isEnabled' => true,
            'secret' => null,
            'qrCodeUrl' => null,
        ]);
    }

    /**
     * Disable 2FA after password confirmation.
     */
    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = Auth::guard('admin')->user();

        if (!Hash::check($request->input('password'), $user->password)) {
            return back()->with('error', 'Incorrect password.');
        }

        $user->two_factor_secret = null;
        $user->two_factor_enabled = false;
        $user->two_factor_recovery_codes = null;
        $user->save();

        session()->forget('2fa_verified_at');

        return redirect()->route('admin.profile')
            ->with('success', 'Two-factor authentication has been disabled.');
    }

    /**
     * Show the 2FA verification form (after login, standalone page).
     */
    public function verify(Request $request): View|RedirectResponse
    {
        $user = Auth::guard('admin')->user();

        if (!$user->two_factor_enabled) {
            return redirect()->route('admin.dashboard');
        }

        // Already verified and fresh
        $verifiedAt = session('2fa_verified_at');
        if ($verifiedAt && (time() - $verifiedAt) < 43200) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.two-factor.verify', [
            'showRecovery' => $request->boolean('recovery'),
        ]);
    }

    /**
     * Verify the TOTP code and mark the session as verified.
     */
    public function verifyCode(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = Auth::guard('admin')->user();

        if (!$user->two_factor_enabled || !$user->two_factor_secret) {
            return redirect()->route('admin.dashboard');
        }

        $secret = $this->twoFactor->decryptSecret($user->two_factor_secret);

        if (!$this->twoFactor->verifyCode($secret, $request->input('code'))) {
            return back()->with('error', 'Invalid authentication code. Please try again.');
        }

        session(['2fa_verified_at' => time()]);

        return redirect()->intended(route('admin.dashboard'))
            ->with('success', 'Successfully verified.');
    }

    /**
     * Verify using a one-time recovery code.
     */
    public function verifyRecovery(Request $request): RedirectResponse
    {
        $request->validate([
            'recovery_code' => ['required', 'string'],
        ]);

        $user = Auth::guard('admin')->user();

        if (!$user->two_factor_enabled || !$user->two_factor_recovery_codes) {
            return redirect()->route('admin.dashboard');
        }

        $remainingCodes = $this->twoFactor->verifyRecoveryCode(
            $user->two_factor_recovery_codes,
            $request->input('recovery_code')
        );

        if ($remainingCodes === null) {
            return back()->with('error', 'Invalid recovery code.');
        }

        // Update the remaining codes (the used one has been removed)
        $user->two_factor_recovery_codes = $this->twoFactor->encryptRecoveryCodes($remainingCodes);
        $user->save();

        session(['2fa_verified_at' => time()]);

        $remaining = count($remainingCodes);
        $warning = $remaining <= 2
            ? " You have {$remaining} recovery code(s) remaining. Consider generating new ones."
            : '';

        return redirect()->intended(route('admin.dashboard'))
            ->with('success', 'Verified with recovery code.' . $warning);
    }
}
