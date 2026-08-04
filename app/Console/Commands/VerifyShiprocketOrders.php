<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Tenant;
use App\Services\ShiprocketCheckout\OrderSyncEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Background verification loop for the Shiprocket order sync engine.
 *
 * Webhooks remain the primary real-time mechanism; this is the scheduled
 * safety net (every 5 minutes) that detects missed webhook events, missing
 * orders and incomplete records, and repairs them automatically:
 *
 *   1. Discovery — completed Shiprocket Checkout orders with no local Order
 *      are created (missed callback + missed webhook, or locally deleted).
 *   2. Verify & repair — orders that still need attention (active lifecycle,
 *      or missing pricing/customer/address/AWB) are re-verified against the
 *      source APIs and repaired field-by-field.
 *
 * Fully idempotent, so overlapping with the admin "Sync Orders" button or the
 * reconcile command is safe. --limit bounds API usage per run; every order
 * verified is stamped metadata.sr_verified_at (the resume checkpoint).
 *
 *   php artisan shiprocket:verify-orders                     # scheduled shape
 *   php artisan shiprocket:verify-orders --tenant=gryt --full --limit=0
 *   php artisan shiprocket:verify-orders --order=6a71...113
 */
class VerifyShiprocketOrders extends Command
{
    protected $signature = 'shiprocket:verify-orders
        {--tenant= : Only this tenant id (default: all tenants)}
        {--days=2 : Discovery window for missing orders}
        {--limit=10 : Max orders to verify per tenant per run (0 = no cap)}
        {--order= : Verify a single order by Shiprocket id or order number}
        {--full : Verify every Shiprocket order, not just ones needing attention}';

    protected $description = 'Detect and auto-repair missed/missing/incomplete Shiprocket orders (background safety net for webhooks)';

    public function handle(): int
    {
        $tenants = $this->option('tenant')
            ? Tenant::where('id', $this->option('tenant'))->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('No matching tenant.');
            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);
            try {
                // Per tenant: the engine's ShiprocketService caches its auth
                // token from tenant settings at construction time.
                $this->verifyTenant((string) $tenant->id, app(OrderSyncEngine::class));
            } catch (\Throwable $e) {
                $this->error("[{$tenant->id}] verify failed: {$e->getMessage()}");
                Log::error('shiprocket:verify-orders failed', ['tenant' => $tenant->id, 'error' => $e->getMessage()]);
            } finally {
                tenancy()->end();
            }
        }

        return self::SUCCESS;
    }

    private function verifyTenant(string $tenantId, OrderSyncEngine $engine): void
    {
        $days = max(1, (int) $this->option('days'));
        $limit = max(0, (int) $this->option('limit'));
        $single = (string) $this->option('order');

        // Phase 1 — recover missing orders.
        if ($single === '') {
            $disc = $engine->discoverAndCreate($days);
            foreach ($disc['created'] as $c) {
                $this->info("[{$tenantId}] recovered {$c['order_number']} ({$c['id']}, ₹{$c['total']})");
            }
            foreach ($disc['failed'] as $f) {
                $this->error("[{$tenantId}] recovery failed {$f['id']}: {$f['error']}");
            }
            if ($disc['failed']) {
                Log::error('shiprocket:verify-orders: recovery failures', ['tenant' => $tenantId, 'failed' => $disc['failed']]);
            }
        }

        // Phase 2 — verify & repair.
        $query = Order::query()->whereNotNull('shiprocket_order_id');

        if ($single !== '') {
            $query->where(fn ($q) => $q->where('shiprocket_order_id', $single)->orWhere('order_number', $single));
        } elseif (! $this->option('full')) {
            // Orders that can still change at the source or are visibly incomplete.
            $query->where(function ($q) {
                $q->whereNotIn('status', ['delivered', 'cancelled', 'returned'])
                    ->orWhereNull('metadata->sr_pricing')
                    ->orWhereNull('guest_name')
                    ->orWhereNull('shipping_address_snapshot');
            });
        }

        $orders = $query->orderByDesc('id')
            ->when($limit > 0, fn ($q) => $q->limit($limit))
            ->get();

        if ($orders->isEmpty()) {
            $this->info("[{$tenantId}] nothing to verify.");
            return;
        }

        $repaired = 0; $clean = 0; $failed = 0;
        foreach ($orders as $order) {
            try {
                $r = $engine->verifyAndRepair($order);
                if ($r['changed']) {
                    $repaired++;
                    $this->info("  {$r['order_number']}: " . implode(', ', $r['repairs']));
                } else {
                    $clean++;
                }
                foreach ($r['discrepancies'] as $d) {
                    $this->warn("  {$r['order_number']}: NEEDS REVIEW — {$d}");
                    Log::warning('shiprocket:verify-orders discrepancy', ['order' => $r['order_number'], 'issue' => $d]);
                }
                if ($r['errors']) {
                    $this->line("  {$r['order_number']}: partial errors — " . implode(' | ', $r['errors']));
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->error("  {$order->order_number}: {$e->getMessage()}");
                Log::error('shiprocket:verify-orders order failed', ['order' => $order->order_number, 'error' => $e->getMessage()]);
            }
        }

        $this->info("[{$tenantId}] verified {$orders->count()}: {$repaired} repaired, {$clean} clean, {$failed} failed ({$engine->apiCalls} API calls).");
    }
}
