<?php

namespace App\Observers;

use App\Jobs\PushCollectionToShiprocket;
use App\Models\Category;
use App\Models\Setting;

/**
 * Keeps Shiprocket Checkout's collections in sync with our categories.
 * Pushes to wh/v1/custom/collection on every category create/update.
 */
class CategoryObserver
{
    /**
     * Fires on both create and update.
     */
    public function saved(Category $category): void
    {
        if (!$this->shiprocketConfigured()) {
            return;
        }

        PushCollectionToShiprocket::dispatch($category)->afterCommit();
    }

    private function shiprocketConfigured(): bool
    {
        return !empty(Setting::get('shiprocket_checkout_api_key', ''))
            || !empty(Setting::get('api_key', ''));
    }
}
