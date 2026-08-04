<?php

namespace App\Observers;

use App\Jobs\SyncOrderShiprocketPricing;
use App\Models\Order;
use App\Models\Setting;

/**
 * Captures Shiprocket Checkout pricing the moment an order is created.
 *
 * Shiprocket doesn't push the coupon/discount breakdown on this account, so orders
 * arrive at the full pre-discount total. Rather than wait for the 15-minute
 * shiprocket:sync-pricing sweep, pull the pricing immediately on creation so the
 * discounted total + "Discount Codes Applied" show right away. The scheduled sweep
 * remains the safety net for any order this misses.
 */
class OrderObserver
{
    /**
     * Only on create — pricing is captured once. (Re-syncing on every status
     * update would hammer the order-details API for no benefit.)
     */
    public function created(Order $order): void
    {
        // Only Shiprocket-checkout orders have pricing to pull.
        if (empty($order->shiprocket_order_id)) {
            return;
        }

        // Already captured by the creating code path → nothing to do.
        if (!empty($order->metadata['sr_pricing'])) {
            return;
        }

        if (!$this->shiprocketConfigured()) {
            return;
        }

        // afterCommit: don't fetch until the order row is committed (the job
        // re-queries the order fresh). On the sync queue this runs inline.
        SyncOrderShiprocketPricing::dispatch($order)->afterCommit();
    }

    private function shiprocketConfigured(): bool
    {
        return !empty(Setting::get('shiprocket_checkout_api_key', ''))
            || !empty(Setting::get('api_key', ''));
    }
}
