<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\ShiprocketCheckout\ShiprocketCheckoutService;
use Illuminate\Console\Command;

/**
 * Fill in customer details on Shiprocket Checkout orders that have none.
 *
 * Orders created by the success callback carry customer data only if a webhook
 * delivered it first. When webhooks never arrive, orders land with NULL name /
 * phone / email — unshippable, and no confirmation email can be sent. This pulls
 * the details straight from Shiprocket's order-details API instead.
 *
 *   php artisan shiprocket:backfill-customers            # dry run
 *   php artisan shiprocket:backfill-customers --apply
 *   php artisan shiprocket:backfill-customers --apply --order=6a59c0ea28825d003b1fe439
 */
class BackfillShiprocketCustomers extends Command
{
    protected $signature = 'shiprocket:backfill-customers
                            {--apply : Write the changes (omit for a dry run)}
                            {--order= : Only this Shiprocket order id}';

    protected $description = 'Backfill missing customer details on Shiprocket Checkout orders from the Shiprocket API';

    public function handle(ShiprocketCheckoutService $checkout): int
    {
        $apply = (bool) $this->option('apply');

        if (! $apply) {
            $this->warn('DRY RUN — no changes written. Re-run with --apply.');
        }

        $query = Order::whereNotNull('shiprocket_order_id')
            ->where(function ($q) {
                $q->whereNull('guest_name')
                  ->orWhereNull('guest_phone')
                  ->orWhereNull('guest_email');
            });

        if ($this->option('order')) {
            $query->where('shiprocket_order_id', $this->option('order'));
        }

        $orders = $query->orderBy('id')->get();

        if ($orders->isEmpty()) {
            $this->info('Nothing to backfill — every Shiprocket order already has customer details.');
            return self::SUCCESS;
        }

        $this->line("Found {$orders->count()} order(s) missing customer details.");
        $rows = [];
        $filled = 0;

        foreach ($orders as $order) {
            $sr = $checkout->getOrder($order->shiprocket_order_id);

            if (! $sr) {
                $rows[] = [$order->order_number, '—', '—', '—', 'API returned nothing'];
                continue;
            }

            $c = $checkout->extractCustomer($sr);

            $updates = [];
            if (empty($order->guest_name)  && ! empty($c['name']))  $updates['guest_name']  = $c['name'];
            if (empty($order->guest_phone) && ! empty($c['phone'])) $updates['guest_phone'] = $c['phone'];
            if (empty($order->guest_email) && ! empty($c['email'])) $updates['guest_email'] = $c['email'];
            if (empty($order->shipping_address_snapshot) && ! empty($c['address'])) {
                $updates['shipping_address_snapshot'] = $c['address'];
            }

            if (empty($updates)) {
                $rows[] = [$order->order_number, $c['name'] ?: '—', $c['phone'] ?: '—', $c['email'] ?: '—', 'already complete'];
                continue;
            }

            if ($apply) {
                $order->update($updates);
                $filled++;
            }

            $rows[] = [
                $order->order_number,
                $c['name'] ?: '—',
                $c['phone'] ?: '—',
                $c['email'] ?: '—',
                $apply ? 'UPDATED' : 'would update',
            ];
        }

        $this->table(['Order', 'Name', 'Phone', 'Email', 'Result'], $rows);

        if ($apply) {
            $this->info("Backfilled {$filled} order(s).");
        } else {
            $this->warn('Dry run — re-run with --apply to write these.');
        }

        return self::SUCCESS;
    }
}
