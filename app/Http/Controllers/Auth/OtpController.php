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

        // Merge guest cart before session regeneration (uses old session ID)
        $this->mergeGuestCart($request);

        Auth::login($user, true);
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'redirect' => session()->pull('url.intended', route('account.dashboard')),
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

        Auth::login($user);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'redirect' => route('account.dashboard')]);
        }

        return redirect()->route('account.dashboard')->with('success', 'Password reset successfully!');
    }

    private function mergeGuestCart(Request $request): void
    {
        $sessionId = $request->session()->getId();
        $guestCart = \App\Models\Cart::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->with('items')
            ->first();

        if (!$guestCart || $guestCart->items->isEmpty()) {
            return;
        }

        $userCart = \App\Models\Cart::firstOrCreate(
            ['user_id' => Auth::id()],
            ['session_id' => $sessionId, 'subtotal' => 0, 'discount' => 0, 'tax' => 0, 'total' => 0]
        );

        foreach ($guestCart->items as $item) {
            $existing = $userCart->items()
                ->where('product_id', $item->product_id)
                ->where('variant_id', $item->variant_id)
                ->first();

            if ($existing) {
                $existing->update(['quantity' => $existing->quantity + $item->quantity]);
            } else {
                $userCart->items()->create([
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ]);
            }
        }

        $guestCart->items()->delete();
        $guestCart->delete();
        $userCart->recalculate();
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
