<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\ShiprocketCheckout\ShiprocketCheckoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Pushes a single product to Shiprocket Checkout's custom catalog webhook.
 *
 * Dispatched by ProductObserver on every product create/update so Shiprocket's
 * cached catalog — and the prices it charges at checkout — stay current.
 * Tenant-aware via stancl QueueTenancyBootstrapper.
 */
class PushProductToShiprocket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120, 300];

    /** Drop the job quietly if the product was deleted before it ran. */
    public bool $deleteWhenMissingModels = true;

    public function __construct(public Product $product) {}

    public function handle(ShiprocketCheckoutService $checkout): void
    {
        // Relations are needed for a complete payload (variants, images, category).
        $this->product->loadMissing(['images', 'category', 'variants', 'variants.images']);

        if (!$checkout->pushCatalogProduct($this->product)) {
            // Network / 5xx failure — let the queue retry via backoff.
            throw new \RuntimeException("Shiprocket catalog push failed for product {$this->product->id}");
        }
    }
}
