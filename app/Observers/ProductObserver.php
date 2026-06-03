<?php

namespace App\Observers;

use App\Jobs\PushProductToShiprocket;
use App\Models\Product;
use App\Models\Setting;

/**
 * Keeps Shiprocket Checkout's catalog in sync with our products.
 *
 * Shiprocket charges from its own cached catalog copy, so every product
 * change must be pushed to it (wh/v1/custom/product). Observing the model —
 * rather than hooking a controller — guarantees admin edits, bulk actions,
 * CSV imports and API writes all trigger the push.
 */
class ProductObserver
{
    /**
     * Fires on both create and update.
     */
    public function saved(Product $product): void
    {
        if (!$this->shiprocketConfigured()) {
            return;
        }

        // afterCommit: don't push until the DB write is committed.
        PushProductToShiprocket::dispatch($product)->afterCommit();
    }

    /**
     * Only push for tenants that actually use Shiprocket Checkout.
     * Setting::get is tenant-cached, so this is cheap on every save.
     */
    private function shiprocketConfigured(): bool
    {
        return !empty(Setting::get('shiprocket_checkout_api_key', ''))
            || !empty(Setting::get('api_key', ''));
    }
}
