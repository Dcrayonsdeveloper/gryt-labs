<?php

namespace App\Services\ShiprocketCheckout;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Temporary stock reservation during Shiprocket Checkout.
 *
 * Between token generation (customer starts checkout) and callback (order created),
 * there's a 2-5 minute gap where no stock is held. This service uses Redis to
 * place soft holds that prevent overselling during concurrent checkouts.
 *
 * Holds auto-expire after 10 minutes if the customer abandons checkout.
 * A Lua script ensures atomic check-and-hold to prevent race conditions.
 *
 * Redis key format: stock_hold:{tenant_id}:{product_id} (HASH)
 *   field = session_id, value = "qty:expiry_timestamp"
 */
class StockHoldService
{
    private const HOLD_TTL_SECONDS = 600;  // 10 minutes
    private const KEY_TTL_SECONDS  = 900;  // 15 minutes (hash auto-cleanup)

    /**
     * Atomically check available stock and place a hold.
     * Returns true if hold was placed (enough stock), false if insufficient.
     */
    public function hold(int $productId, int $qty, string $sessionId, int $dbStock): bool
    {
        try {
            $key    = $this->key($productId);
            $expiry = now()->addSeconds(self::HOLD_TTL_SECONDS)->timestamp;
            $now    = now()->timestamp;

            $result = Redis::eval(
                $this->holdScript(),
                1,
                $key, $sessionId, (string) $qty, (string) $expiry,
                (string) $dbStock, (string) $now, (string) self::KEY_TTL_SECONDS
            );

            return (bool) $result;
        } catch (\Throwable $e) {
            Log::warning('StockHoldService: Redis hold failed, falling back to DB check', [
                'product_id' => $productId,
                'error'      => $e->getMessage(),
            ]);
            return $dbStock >= $qty;
        }
    }

    /**
     * Release a hold for a specific product + session.
     */
    public function release(int $productId, string $sessionId): void
    {
        try {
            Redis::hdel($this->key($productId), $sessionId);
        } catch (\Throwable $e) {
            Log::warning('StockHoldService: Redis release failed', [
                'product_id' => $productId,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Release holds for multiple products in one session (e.g. after order creation).
     */
    public function releaseSession(string $sessionId, array $productIds): void
    {
        foreach ($productIds as $productId) {
            $this->release((int) $productId, $sessionId);
        }
    }

    /**
     * Get available stock = DB stock minus active holds.
     * Used for display purposes; hold() does the atomic version.
     */
    public function availableStock(int $productId, int $dbStock): int
    {
        try {
            $holds     = Redis::hgetall($this->key($productId));
            $now       = now()->timestamp;
            $totalHeld = 0;

            foreach ($holds as $sid => $val) {
                $parts = explode(':', $val);
                $hExp  = (int) ($parts[1] ?? 0);
                if ($hExp <= $now) {
                    Redis::hdel($this->key($productId), $sid);
                } else {
                    $totalHeld += (int) ($parts[0] ?? 0);
                }
            }

            return max(0, $dbStock - $totalHeld);
        } catch (\Throwable $e) {
            return $dbStock;
        }
    }

    private function key(int $productId): string
    {
        $tenantId = tenant('id') ?? 'central';
        return "stock_hold:{$tenantId}:{$productId}";
    }

    /**
     * Lua script for atomic check-and-hold.
     *
     * KEYS[1] = hash key
     * ARGV[1] = session_id, ARGV[2] = qty, ARGV[3] = expiry timestamp
     * ARGV[4] = db_stock, ARGV[5] = current timestamp, ARGV[6] = key TTL
     *
     * Returns 1 if hold placed, 0 if insufficient stock.
     */
    private function holdScript(): string
    {
        return <<<'LUA'
local key = KEYS[1]
local session_id = ARGV[1]
local qty = tonumber(ARGV[2])
local expiry = ARGV[3]
local db_stock = tonumber(ARGV[4])
local now = tonumber(ARGV[5])
local key_ttl = tonumber(ARGV[6])

local holds = redis.call('HGETALL', key)
local total_held = 0

for i = 1, #holds, 2 do
    local sid = holds[i]
    local val = holds[i + 1]
    local sep = string.find(val, ':')
    local h_qty = tonumber(string.sub(val, 1, sep - 1))
    local h_exp = tonumber(string.sub(val, sep + 1))

    if h_exp <= now then
        redis.call('HDEL', key, sid)
    elseif sid ~= session_id then
        total_held = total_held + h_qty
    end
end

if (db_stock - total_held) >= qty then
    redis.call('HSET', key, session_id, qty .. ':' .. expiry)
    redis.call('EXPIRE', key, key_ttl)
    return 1
end

return 0
LUA;
    }
}
