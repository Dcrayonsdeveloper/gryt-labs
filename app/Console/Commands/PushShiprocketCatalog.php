<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Services\ShiprocketCheckout\ShiprocketCheckoutService;
use Illuminate\Console\Command;

/**
 * Full catalog backfill — pushes every active product and collection to
 * Shiprocket Checkout. Use for the initial sync or to recover after drift.
 *
 *   php artisan tenants:run shiprocket:push-catalog --tenants=natually
 *
 * Ongoing per-change syncing is handled automatically by ProductObserver /
 * CategoryObserver; this command is the one-shot bulk equivalent.
 */
class PushShiprocketCatalog extends Command
{
    protected $signature = 'shiprocket:push-catalog';

    protected $description = 'Push the full catalog (products + collections) to Shiprocket Checkout';

    public function handle(ShiprocketCheckoutService $checkout): int
    {
        if (empty(Setting::get('shiprocket_checkout_api_key', '')) && empty(Setting::get('api_key', ''))) {
            $this->warn('Shiprocket Checkout not configured for this tenant — skipping.');
            return self::SUCCESS;
        }

        // Collections first, then products.
        $categories = Category::where('is_active', true)->get();
        $this->info("Pushing {$categories->count()} collections...");
        $cOk = 0;
        $cFail = 0;
        $bar = $this->output->createProgressBar($categories->count());
        foreach ($categories as $category) {
            $checkout->pushCatalogCollection($category) ? $cOk++ : $cFail++;
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->line("  Collections: <info>{$cOk} pushed</info>, <comment>{$cFail} failed</comment>");

        $products = Product::where('is_active', true)
            ->with(['images', 'category', 'variants', 'variants.images'])
            ->get();
        $this->info("Pushing {$products->count()} products...");
        $pOk = 0;
        $pFail = 0;
        $bar = $this->output->createProgressBar($products->count());
        foreach ($products as $product) {
            $checkout->pushCatalogProduct($product) ? $pOk++ : $pFail++;
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->line("  Products: <info>{$pOk} pushed</info>, <comment>{$pFail} failed</comment>");

        return ($cFail || $pFail) ? self::FAILURE : self::SUCCESS;
    }
}
