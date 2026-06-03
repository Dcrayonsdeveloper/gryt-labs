<?php

namespace App\Services\Concerns;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit breaker pattern for external API calls.
 *
 * If an API fails 5+ times in 60 seconds, the circuit opens and
 * subsequent calls return failure immediately without hitting the API.
 * After 60 seconds, the circuit half-opens and allows one test request.
 *
 * Usage in any service:
 *   use HasCircuitBreaker;
 *
 *   $result = $this->withCircuitBreaker('razorpay', function () {
 *       return Http::timeout(10)->retry(2, 500)->post(...);
 *   });
 */
trait HasCircuitBreaker
{
    protected function withCircuitBreaker(string $service, callable $callback, int $maxFailures = 5, int $cooldownSeconds = 60): mixed
    {
        $cacheKey = "circuit_breaker:{$service}";
        $failureKey = "circuit_failures:{$service}";

        // Check if circuit is open (too many recent failures)
        $failures = (int) Cache::get($failureKey, 0);
        if ($failures >= $maxFailures) {
            Log::warning("Circuit breaker OPEN for {$service} — skipping call ({$failures} recent failures)");
            return null;
        }

        try {
            $result = $callback();

            // Reset failure count on success
            if ($failures > 0) {
                Cache::forget($failureKey);
            }

            return $result;
        } catch (\Throwable $e) {
            // Increment failure count
            $newCount = $failures + 1;
            Cache::put($failureKey, $newCount, $cooldownSeconds);
            Log::warning("Circuit breaker: {$service} failed ({$newCount}/{$maxFailures})", [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
