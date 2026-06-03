<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerificationController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->intended(route('account.dashboard'))
            : view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('account.dashboard'));
        }

        try {
            if ($request->user()->markEmailAsVerified()) {
                event(new Verified($request->user()));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Verified event failed', ['error' => $e->getMessage()]);
        }

        return redirect()->intended(route('account.dashboard'))->with('verified', true);
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('account.dashboard'));
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Verification email failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Could not send verification email. Please try again later.');
        }

        return back()->with('status', 'Verification link sent!');
    }
}
