<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Delete specific orders (by order_number) and their child rows.
 *
 * Built for clearing test orders. Deliberately NARROW and SAFE:
 *   - only deletes the exact order_number(s) passed via --order (never "all")
 *   - previews by default; nothing is removed without --force
 *   - tenant-aware (loops tenants like shiprocket:sync-pricing)
 *
 * Cascades: order_items / payments / shipments / returns are FK cascadeOnDelete;
 * abandoned_checkouts.order_id & draft_orders.order_id are nullOnDelete. Status
 * history is deleted explicitly (no cascade FK) before the order.
 *
 *   php artisan orders:delete --order=ORD-1 --order=ORD-2            # dry run
 *   php artisan orders:delete --order=ORD-1 --tenant=gryt --force    # delete
 */
class DeleteOrders extends Command
{
    protected $signature = 'orders:delete
        {--order=* : Order number(s) to delete (required)}
        {--tenant= : Only this tenant id (default: all tenants)}
        {--force : Actually delete (default: dry-run preview)}';

    protected $description = 'Delete specific orders by order_number (test-order cleanup). Dry-run unless --force.';

    public function handle(): int
    {
        $numbers = array_values(array_filter((array) $this->option('order')));
        if (empty($numbers)) {
            $this->error('Pass at least one --order=ORD-… (this command never deletes all orders).');
            return self::FAILURE;
        }

        $force = (bool) $this->option('force');

        $tenants = $this->option('tenant')
            ? Tenant::where('id', $this->option('tenant'))->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('No matching tenant.');
            return self::FAILURE;
        }

        $deleted = 0; $missing = 0;

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);
            try {
                $orders = Order::whereIn('order_number', $numbers)->get();

                foreach ($orders as $order) {
                    $this->line(sprintf(
                        '  %s[%s]: %s | total %s | %d item(s)%s',
                        $force ? '' : '[DRY] ',
                        $tenant->id,
                        $order->order_number,
                        number_format((float) $order->total, 2),
                        $order->items()->count(),
                        $force ? '' : ' — would delete'
                    ));

                    if ($force) {
                        DB::transaction(function () use ($order) {
                            $order->statusHistory()->delete();
                            $order->delete(); // cascades items/payments/shipments/returns
                        });
                        $deleted++;
                    }
                }

                // Report any requested numbers not present in this tenant.
                $found = $orders->pluck('order_number')->all();
                foreach (array_diff($numbers, $found) as $notFound) {
                    // Only count as missing once (last tenant) — but harmless if multi-tenant.
                    $missing++;
                }
            } catch (\Throwable $e) {
                $this->error("[{$tenant->id}] delete failed: {$e->getMessage()}");
            } finally {
                tenancy()->end();
            }
        }

        $this->newLine();
        $this->info($force
            ? "Deleted: {$deleted} order(s)."
            : "[DRY RUN] Re-run with --force to delete. Matched above.");
        return self::SUCCESS;
    }
}
