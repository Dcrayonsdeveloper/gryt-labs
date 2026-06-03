<?php

namespace App\Jobs;

use App\Models\Category;
use App\Services\ShiprocketCheckout\ShiprocketCheckoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Pushes a single collection (category) to Shiprocket Checkout's custom
 * catalog webhook. Dispatched by CategoryObserver on every category
 * create/update. Tenant-aware via stancl QueueTenancyBootstrapper.
 */
class PushCollectionToShiprocket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120, 300];

    /** Drop the job quietly if the category was deleted before it ran. */
    public bool $deleteWhenMissingModels = true;

    public function __construct(public Category $category) {}

    public function handle(ShiprocketCheckoutService $checkout): void
    {
        if (!$checkout->pushCatalogCollection($this->category)) {
            // Network / 5xx failure — let the queue retry via backoff.
            throw new \RuntimeException("Shiprocket catalog push failed for collection {$this->category->id}");
        }
    }
}
