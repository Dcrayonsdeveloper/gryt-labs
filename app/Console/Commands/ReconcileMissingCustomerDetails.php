<?php

namespace App\Console\Commands;

use App\Jobs\SyncShiprocketCustomerDetails;
use App\Models\Order;
use Illuminate\Console\Command;

/**
 * Re-dispatch SyncShiprocketCustomerDetails for orders that Shiprocket's API
 * was slow to surface. The job itself retries 8 times over ~8h after order
 * creation; this command extends that window to 7 days by re-queueing nightly.
 *
 *   php artisan orders:reconcile-missing-customer-details
 *   php artisan orders:reconcile-missing-customer-details --dry-run
 */
class ReconcileMissingCustomerDetails extends Command
{
    protected $signature = 'orders:reconcile-missing-customer-details
                            {--dry-run : List candidates without dispatching}';

    protected $description = 'Re-dispatch Shiprocket customer-details sync for orders aged 8h-7d with missing customer data';

    public function handle(): int
    {
        $candidates = Order::query()
            ->whereNotNull('shiprocket_order_id')
            ->where(function ($q) {
                $q->whereNull('guest_phone')->orWhere('guest_phone', '');
            })
            ->where('created_at', '<', now()->subHours(8))
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No orders need customer-detail reconciliation.');
            return self::SUCCESS;
        }

        $this->info("Found {$candidates->count()} order(s) to reconcile:");
        $this->table(
            ['ID', 'Order Number', 'SR Checkout ID', 'Age', 'Status'],
            $candidates->map(fn ($o) => [
                $o->id,
                $o->order_number,
                $o->shiprocket_order_id,
                $o->created_at->diffForHumans(),
                $o->status,
            ])->toArray()
        );

        if ($this->option('dry-run')) {
            $this->warn('Dry run — no jobs dispatched. Drop --dry-run to re-queue them.');
            return self::SUCCESS;
        }

        foreach ($candidates as $order) {
            SyncShiprocketCustomerDetails::dispatch($order);
        }
        $this->info("Re-dispatched sync for {$candidates->count()} order(s).");

        return self::SUCCESS;
    }
}
