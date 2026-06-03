<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Require2FA
{
    /**
     * Routes that should be accessible even when 2FA is pending verification.
     */
    private const ALLOWED_ROUTES = [
        'admin.2fa.verify',
        'admin.2fa.verify.code',
        'admin.2fa.recovery',
        'admin.2fa.setup',
        'admin.2fa.enable',
        'admin.2fa.disable',
        'admin.logout',
    ];

    /**
     * Re-verification interval in seconds (12 hours).
     */
    private const REVERIFY_AFTER = 43200;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('admin');

        // No user authenticated — let other middleware handle it
        if (!$user) {
            return $next($request);
        }

        // User doesn't have 2FA enabled — pass through
        if (!$user->two_factor_enabled) {
            return $next($request);
        }

        // Allow 2FA-related routes to avoid redirect loops
        $currentRoute = $request->route()?->getName();
        if ($currentRoute && in_array($currentRoute, self::ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        // Check if 2FA has been verified this session and is still fresh
        $verifiedAt = session('2fa_verified_at');

        if ($verifiedAt && (time() - $verifiedAt) < self::REVERIFY_AFTER) {
            return $next($request);
        }

        // 2FA is enabled but not verified (or expired) — redirect to verify page
        return redirect()->route('admin.2fa.verify');
    }
}
