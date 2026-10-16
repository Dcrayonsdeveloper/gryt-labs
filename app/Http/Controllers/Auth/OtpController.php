<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OtpController extends Controller
{
    public function __construct(private OtpService $otpService) {}

    /**
     * Send OTP for login (phone or email).
     */
    public function sendLoginOtp(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => 'required|string|min:5',
        ]);

        $identifier = $request->input('identifier');
        $clean = preg_replace('/\D/', '', $identifier);
        $isPhone = strlen($clean) >= 10;

        // Find or auto-create user for phone numbers
        $user = $this->findUser($identifier);

        if (!$user && $isPhone) {
            // Auto-create account for phone login (standard Indian e-commerce flow)
            $phone10 = substr($clean, -10);
            $user = User::create([
                'first_name' => 'User',
                'last_name' => '',
                'phone' => $phone10,
                'email' => $phone10 . '@phone.' . parse_url(config('app.url', 'localhost'), PHP_URL_HOST),
                'password' => bcrypt(Str::random(16)),
                'role' => 'customer',
            ]);
        }

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'No account found with this email. Please register first.'], 404);
        }

        // Send OTP
        $phone = $user->phone ?? ($isPhone ? $identifier : null);
        $email = $user->email ?? (filter_var($identifier, FILTER_VALIDATE_EMAIL) ? $identifier : null);

        $result = $this->otpService->send($phone ?? $email, 'login');

        return response()->json($result, $result['success'] ? 200 : 429);
    }

    /**
     * Verify OTP and login.
     */
    public function verifyLoginOtp(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        $identifier = $request->input('identifier');
        $user = $this->findUser($identifier);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Account not found. Please try again.'], 404);
        }

        $clean = preg_replace('/\D/', '', $identifier);
        $isPhone = strlen($clean) >= 10;
        $phone = $user->phone ?? ($isPhone ? substr($clean, -10) : null);
        $email = $user->email ?? (filter_var($identifier, FILTER_VALIDATE_EMAIL) ? $identifier : null);
        $checkIdentifier = $isPhone ? $phone : $email;

        if (!$this->otpService->verify($checkIdentifier, $request->input('otp'), 'login')) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired OTP.'], 422);
        }

        // Read the guest session id before Auth::login() migrates it, then merge
        // after, when there is a user id to merge into. The old call sat before
        // the login and passed Auth::id() — null at that point — so it never
        // merged into the right cart.
        $guestSessionId = $request->session()->getId();

        Auth::login($user, true);
        \App\Models\Cart::mergeGuestCart($guestSessionId, (int) $user->id);
        $request->session()->regenerate();

        return response()->json([
            // Same as the password login path: home by default, but any stored
            // destination still wins so the cart auth gate returns people to the
            // product they were adding.
            'success' => true,
            'redirect' => session()->pull('url.intended', route('home')),
        ]);
    }

    /**
     * Send OTP for password reset.
     */
    public function sendResetOtp(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => 'required|string|min:5',
        ]);

        $identifier = $request->input('identifier');
        $user = $this->findUser($identifier);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'No account found.'], 404);
        }

        $phone = $user->phone ?? $identifier;
        $result = $this->otpService->send($phone, 'reset_password');

        return response()->json($result, $result['success'] ? 200 : 429);
    }

    /**
     * Verify OTP and show reset password form.
     */
    public function verifyResetOtp(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        $identifier = $request->input('identifier');
        $user = $this->findUser($identifier);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Account not found.'], 404);
        }

        $phone = $user->phone ?? $identifier;
        $email = $user->email ?? $identifier;
        $checkIdentifier = is_numeric(preg_replace('/\D/', '', $identifier)) ? $phone : $email;

        if (!$this->otpService->verify($checkIdentifier, $request->input('otp'), 'reset_password')) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired OTP.'], 422);
        }

        // Store reset token in session
        session()->put('otp_reset_user_id', $user->id);
        session()->put('otp_reset_verified', true);

        return response()->json([
            'success' => true,
            'message' => 'OTP verified. You can now reset your password.',
        ]);
    }

    /**
     * Reset password after OTP verification.
     */
    public function resetPassword(Request $request): RedirectResponse|JsonResponse
    {
        if (!session('otp_reset_verified')) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'OTP not verified.'], 403)
                : redirect()->route('password.request')->with('error', 'Please verify OTP first.');
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::find(session('otp_reset_user_id'));
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        $user->update(['password' => bcrypt($request->input('password'))]);

        session()->forget(['otp_reset_user_id', 'otp_reset_verified']);

        $guestSessionId = $request->session()->getId();
        Auth::login($user);
        \App\Models\Cart::mergeGuestCart($guestSessionId, (int) $user->id);

        // A reset signs the user straight in, so it lands on the storefront like
        // the other sign-in paths rather than dropping them on the dashboard.
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'redirect' => route('home')]);
        }

        return redirect()->route('home')->with('success', 'Password reset successfully!');
    }


    private function findUser(string $identifier): ?User
    {
        $clean = preg_replace('/\D/', '', $identifier);

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return User::where('email', $identifier)->first();
        }

        if (strlen($clean) >= 10) {
            // Search with and without 91 prefix
            return User::where('phone', $clean)
                ->orWhere('phone', '91' . substr($clean, -10))
                ->orWhere('phone', substr($clean, -10))
                ->first();
        }

        return User::where('email', $identifier)->first();
    }
}
