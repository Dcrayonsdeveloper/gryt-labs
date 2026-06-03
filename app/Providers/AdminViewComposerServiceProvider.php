<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Shares "needs-attention" work counts with the admin header.
 *
 * Surfaces three pills (orders pending, low stock, reviews pending) so admins
 * don't have to click into every list page to discover work.
 *
 * Cache key scheme: admin.attention.{tenant_key} (60s TTL, single entry per tenant).
 * Per-tenant scoping is automatic because the tenant DB connection is already
 * active by the time the view composer runs.
 */
class AdminViewComposerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap admin view composers.
     */
    public function boot(): void
    {
        View::composer('admin.partials.header', function ($view) {
            $view->with('adminAttentionCounts', $this->resolveAttentionCounts());
        });
    }

    /**
     * Resolve the three attention counts (orders / low stock / reviews).
     *
     * Wrapped in Cache::remember with a tenant-scoped key. If the tenancy
     * package is active, the underlying cache store is already tenant-scoped —
     * but we include the tenant key in the cache name defensively so a shared
     * cache store never leaks counts across tenants.
     *
     * Never throws: on any failure the badge cluster simply hides.
     */
    protected function resolveAttentionCounts(): array
    {
        $tenantKey = function_exists('tenant') && tenant()
            ? tenant()->getTenantKey()
            : 'central';

        $cacheKey = 'admin.attention.' . $tenantKey;

        try {
            return Cache::remember($cacheKey, 60, function () {
                $threshold = (int) Setting::get('low_stock_threshold', 10);
                if ($threshold < 1) {
                    $threshold = 10;
                }

                return [
                    'orders' => Order::whereIn('payment_status', ['failed', 'pending'])->count(),
                    'low_stock' => Product::where('stock_quantity', '>', 0)
                        ->where('stock_quantity', '<=', $threshold)
                        ->count(),
                    'reviews' => Review::where('status', 'pending')->count(),
                ];
            });
        } catch (\Throwable $e) {
            return ['orders' => 0, 'low_stock' => 0, 'reviews' => 0];
        }
    }
}
