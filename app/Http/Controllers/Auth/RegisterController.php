<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;


class RegisterController extends Controller
{
    public function showRegistrationForm(): RedirectResponse
    {
        return redirect()->route('login', ['mode' => 'register']);
    }

    public function register(Request $request): RedirectResponse|JsonResponse
    {
        // Normalise before validating so the unique check compares like with like:
        // without this, "+91 98765 43210" and "9876543210" are the same number but
        // both pass unique:users and create two accounts.
        if ($request->filled('phone')) {
            $digits = preg_replace('/\D/', '', (string) $request->input('phone'));
            if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
                $digits = substr($digits, 2);
            }
            $request->merge(['phone' => $digits]);
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:101'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            // Phone is mandatory: it is the only channel abandoned-cart and order
            // notifications can reach, so an account without one is unreachable.
            // Accepts an optional +91 / 91 prefix and spaces or dashes, then stores
            // the bare 10 digits (WhatsAppService::cleanPhone adds 91 when sending).
            'phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^(?:\+?91[\s-]?)?[6-9]\d{9}$/',
                'unique:users',
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $nameParts = explode(' ', trim($validated['full_name']), 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        $user = User::create([
            'uuid' => Str::uuid(),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
            'is_active' => true,
        ]);

        try {
            event(new Registered($user));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Registration email failed', ['user' => $user->id, 'error' => $e->getMessage()]);
        }

        // Facebook CAPI: CompleteRegistration
        try {
            app(AnalyticsService::class)->trackCompleteRegistration($user, $request);
        } catch (\Exception $e) {
            // Don't block registration for analytics failure
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('login')->with('success', 'Account created successfully! Please login to continue.');
    }
}
