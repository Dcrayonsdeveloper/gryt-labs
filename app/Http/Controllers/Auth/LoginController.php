<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(Request $request): View
    {
        // Routes that do require an account (wishlist, account pages) send guests
        // here as /login?redirect=/the/page so they land back where they were
        // instead of on the dashboard. Stored as the intended URL, which
        // redirect()->intended() below already consumes. Cart and checkout no
        // longer arrive this way — both accept guests.
        //
        // Only same-site absolute paths are accepted: a value starting with "//"
        // or containing a scheme would be an open redirect.
        $redirect = (string) $request->query('redirect', '');
        if ($redirect !== ''
            && str_starts_with($redirect, '/')
            && !str_starts_with($redirect, '//')
            && !str_contains($redirect, '\\')
        ) {
            $request->session()->put('url.intended', $redirect);
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Read before Auth::attempt(): a successful attempt migrates the session
        // id, so afterwards this would be the new one and the guest's cart —
        // stored under the old id — would never be found.
        $guestSessionId = $request->session()->getId();

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            Cart::mergeGuestCart($guestSessionId, (int) Auth::id());
            $request->session()->regenerate();

            if ($request->wantsJson()) {
                return response()->json(['success' => true]);
            }

            // Land on the storefront, not the account dashboard - someone who just
            // signed in is far more likely to want to carry on shopping.
            //
            // Still intended(), so a guest bounced off an account-only page is
            // returned to it; only a plain sign-in with no stored destination
            // falls through to home.
            return redirect()->intended(route('home'));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'The provided credentials do not match our records.',
                'errors' => ['email' => ['The provided credentials do not match our records.']],
            ], 422);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }


    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
