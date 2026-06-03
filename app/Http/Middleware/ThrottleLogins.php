<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ThrottleLogins
{
    /**
     * Handle an incoming request.
     *
     * Track failed login attempts by IP + email combination.
     * - After 5 failed attempts: lock for 15 minutes
     * - After 10 failed attempts: lock for 1 hour
     * On successful login, clear attempts.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to POST requests (actual login attempts)
        if ($request->method() !== 'POST') {
            return $next($request);
        }

        $email = strtolower(trim($request->input('email', '')));
        $ip = $request->ip();
        $key = "login_attempts:{$ip}:{$email}";
        $lockKey = "login_locked:{$ip}:{$email}";

        // Check if currently locked out
        if ($lockData = Cache::get($lockKey)) {
            $lockedUntil = $lockData['locked_until'];
            $remainingSeconds = max(0, $lockedUntil - now()->timestamp);
            $remainingMinutes = (int) ceil($remainingSeconds / 60);

            return response()->json([
                'message' => "Too many login attempts. Please try again in {$remainingMinutes} minute(s).",
                'locked_until' => date('Y-m-d H:i:s', $lockedUntil),
                'remaining_minutes' => $remainingMinutes,
            ], 429)->withHeaders([
                'Retry-After' => $remainingSeconds,
            ]);
        }

        $attempts = (int) Cache::get($key, 0);

        // Process the login request
        $response = $next($request);

        // Determine if login failed based on response
        // A redirect back (302) or 422 validation error means failure
        // A redirect to dashboard/home (302 to different URL) or 200 means success
        $loginFailed = $this->didLoginFail($request, $response);

        if ($loginFailed) {
            $attempts++;
            // Store attempts for 1 hour (max lockout period)
            Cache::put($key, $attempts, now()->addHour());

            if ($attempts >= 10) {
                // Lock for 1 hour
                Cache::put($lockKey, [
                    'locked_until' => now()->addHour()->timestamp,
                    'attempts' => $attempts,
                ], now()->addHour());

                $remaining = 0;
            } elseif ($attempts >= 5) {
                // Lock for 15 minutes
                Cache::put($lockKey, [
                    'locked_until' => now()->addMinutes(15)->timestamp,
                    'attempts' => $attempts,
                ], now()->addMinutes(15));

                $remaining = 0;
            } else {
                $remaining = 5 - $attempts;
            }

            // Add remaining attempts info to session for Blade templates
            if ($remaining > 0) {
                session()->flash('login_attempts_remaining', $remaining);
            }
        } else {
            // Successful login - clear all attempt tracking
            Cache::forget($key);
            Cache::forget($lockKey);
        }

        return $response;
    }

    /**
     * Determine if the login attempt failed.
     *
     * Checks for:
     * - 422 status (validation error / failed credentials)
     * - Redirect back to the same login URL (failed attempt redirects back)
     * - Session has errors
     */
    protected function didLoginFail(Request $request, Response $response): bool
    {
        // Explicit failure status codes
        if ($response->getStatusCode() === 422 || $response->getStatusCode() === 401) {
            return true;
        }

        // If it's a redirect, check if it goes back to the login page
        if ($response->getStatusCode() === 302) {
            $targetUrl = $response->headers->get('Location', '');
            $currentUrl = $request->url();

            // If redirecting back to the same URL or a URL containing "login", it's a failure
            if ($targetUrl === $currentUrl || str_contains($targetUrl, 'login')) {
                return true;
            }

            // Successful login typically redirects to dashboard/home/intended
            return false;
        }

        // If session has errors after the request, it's a failure
        if (session()->has('errors')) {
            return true;
        }

        return false;
    }
}
