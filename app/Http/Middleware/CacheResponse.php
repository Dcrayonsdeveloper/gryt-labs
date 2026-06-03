<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheResponse
{
    public function handle(Request $request, Closure $next, int $minutes = 5): Response
    {
        // Only cache GET requests for unauthenticated users
        if ($request->method() !== 'GET' || auth()->check()) {
            return $next($request);
        }

        try {
            $dbName = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
            $bustKey = "cache_bust.{$dbName}";
            $key = "response_cache.{$dbName}." . md5($request->fullUrl());

            // Check if cache was recently busted
            $lastBust = Cache::get($bustKey, 0);
            $cacheEntry = Cache::get($key);

            if ($cacheEntry && ($cacheEntry['cached_at'] ?? 0) > $lastBust) {
                // Replace stale CSRF token with fresh one for current session
                $content = $this->replaceCsrfToken($cacheEntry['content']);

                // CRITICAL: Use safe headers only — never re-emit cached Set-Cookie
                // (cached cookies would overwrite visitor's session → CSRF 419 on next POST)
                return response($content, $cacheEntry['status'])
                    ->withHeaders($cacheEntry['headers'] ?? [])
                    ->header('X-Cache', 'HIT');
            }

            $response = $next($request);

            if ($response->getStatusCode() === 200) {
                // Strip session/auth/cookie headers BEFORE caching — these are per-visitor
                $safeHeaders = collect($response->headers->all())
                    ->reject(fn ($_, $name) => in_array(strtolower($name), [
                        'set-cookie', 'cookie', 'authorization',
                        'x-csrf-token', 'x-xsrf-token',
                    ]))
                    ->map(fn ($h) => is_array($h) ? ($h[0] ?? null) : $h)
                    ->filter()
                    ->toArray();

                Cache::put($key, [
                    'content' => $response->getContent(),
                    'status' => $response->getStatusCode(),
                    'headers' => $safeHeaders,
                    'cached_at' => time(),
                ], now()->addMinutes($minutes));

                $response->header('X-Cache', 'MISS');
            }

            return $response;
        } catch (\Exception $e) {
            return $next($request);
        }
    }

    /**
     * Replace the cached CSRF token with the current session's token.
     * This ensures forms work even when the HTML is served from cache.
     */
    private function replaceCsrfToken(string $content): string
    {
        // Ensure session is initialized so csrf_token() returns the visitor's actual token
        // (not the cached visitor's token, and not empty)
        try {
            $freshToken = csrf_token();
        } catch (\Throwable) {
            return $content; // Session not available — leave content as-is
        }

        if (empty($freshToken)) {
            return $content; // Don't replace with empty — would guarantee CSRF mismatch
        }

        // Replace <meta name="csrf-token" content="OLD_TOKEN">
        $content = preg_replace(
            '/(<meta\s+name="csrf-token"\s+content=")[^"]*(")/i',
            '${1}' . $freshToken . '${2}',
            $content
        );

        // Replace any <input type="hidden" name="_token" value="OLD_TOKEN">
        $content = preg_replace(
            '/(<input\s+type="hidden"\s+name="_token"\s+value=")[^"]*(")/i',
            '${1}' . $freshToken . '${2}',
            $content
        );

        return $content;
    }

    /**
     * Bust all response caches for the current tenant.
     * Call this after admin updates products, settings, etc.
     */
    public static function bustAll(): void
    {
        try {
            $dbName = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
            Cache::put("cache_bust.{$dbName}", time(), now()->addHours(24));
        } catch (\Exception $e) {
            // Ignore
        }
    }
}
