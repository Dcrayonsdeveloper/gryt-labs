<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\ShiprocketCheckout\ShiprocketCheckoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Pull real Shiprocket pricing (coupon/discount/COD → total + metadata.sr_pricing)
 * onto ONE order as soon as it is created — so the admin shows the discounted total
 * and "Discount Codes Applied" within seconds instead of waiting for the 15-minute
 * shiprocket:sync-pricing sweep. Dispatched by OrderObserver::created.
 *
 * Tenant-aware via stancl QueueTenancyBootstrapper (like PushProductToShiprocket).
 * Best-effort: the queue is `sync` on prod, so this runs inline during the order-
 * creation request — it must NEVER throw, or it would break order creation. Any
 * failure is logged and left for the scheduled command to backfill.
 */
class SyncOrderShiprocketPricing implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Drop quietly if the order was deleted before this ran. */
    public bool $deleteWhenMissingModels = true;

    public function __construct(public Order $order) {}

    public function handle(ShiprocketCheckoutService $checkout): void
    {
        try {
            $checkout->syncOrderPricing($this->order);
        } catch (\Throwable $e) {
            // Never break order creation — the scheduled shiprocket:sync-pricing
            // sweep will backfill this order on its next run.
            Log::warning('SyncOrderShiprocketPricing failed for order '
                . $this->order->order_number . ' — ' . $e->getMessage());
        }
    }
}
