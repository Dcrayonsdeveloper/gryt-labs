<?php

namespace App\Http\Controllers\Influencer\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::guard('influencer')->check()) {
            return redirect()->route('influencer.dashboard');
        }

        return view('influencer.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Only active influencers can sign in (status is a query condition of attempt()).
        $ok = Auth::guard('influencer')->attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'status'   => 'active',
        ], $request->boolean('remember'));

        if ($ok) {
            $request->session()->regenerate();
            Auth::guard('influencer')->user()->forceFill(['last_login_at' => now()])->save();

            return redirect()->intended(route('influencer.dashboard'));
        }

        return back()->withErrors([
            'username' => 'Invalid username or password, or your account is inactive.',
        ])->onlyInput('username');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('influencer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('influencer.login');
    }
}
