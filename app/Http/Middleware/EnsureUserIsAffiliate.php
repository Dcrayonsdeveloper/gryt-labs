<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAffiliate
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->affiliate || !$user->affiliate->isApproved()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Access Denied'], 403);
            }

            if ($user && $user->affiliate && $user->affiliate->isPending()) {
                abort(403, 'Your affiliate account is pending approval.');
            }

            if ($user && $user->affiliate && $user->affiliate->isSuspended()) {
                abort(403, 'Your affiliate account has been suspended.');
            }

            abort(403, 'Access Denied');
        }

        return $next($request);
    }
}
