<?php

namespace App\Console\Commands;

use App\Events\OrderDelivered;
use App\Models\Order;
use App\Services\ShippingManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncShippingTracking extends Command
{
    protected $signature = 'shipping:sync-tracking {--carrier= : Sync a specific carrier only (delhivery, bluedart)}';
    protected $description = 'Poll all active shipping providers and auto-update order statuses';

    public function handle(ShippingManager $shipping): int
    {
        $specificCarrier = $this->option('carrier');

        $providers = $specificCarrier
            ? [$specificCarrier => $shipping->provider($specificCarrier)]
            : $shipping->activeProviders();

        if (empty($providers)) {
            $this->info('No active shipping providers configured.');
            return 0;
        }

        $totalUpdated = 0;

        foreach ($providers as $name => $provider) {
            $label = $provider->label();

            $orders = Order::where('carrier', $label)
                ->whereNotNull('tracking_number')
                ->whereIn('status', ['shipped', 'out_for_delivery'])
                ->get();

            if ($orders->isEmpty()) {
                $this->line("[{$label}] No active shipments to sync.");
                continue;
            }

            $this->info("[{$label}] Syncing {$orders->count()} shipments...");
            $updated = 0;

            foreach ($orders as $order) {
                try {
                    $result = $provider->track($order->tracking_number);

                    if (!$result['success']) {
                        continue;
                    }

                    $newStatus = $provider->mapStatus($result['status'] ?? '');

                    if ($newStatus && $newStatus !== $order->status) {
                        $oldStatus = $order->status;
                        $order->update([
                            'status' => $newStatus,
                            'delivered_at' => $newStatus === 'delivered' ? now() : $order->delivered_at,
                        ]);

                        if ($newStatus === 'delivered') {
                            OrderDelivered::dispatch($order);
                        }

                        $updated++;
                        $this->line("  {$order->order_number}: {$oldStatus} → {$newStatus}");
                    }
                } catch (\Exception $e) {
                    Log::debug("{$label} sync failed for {$order->order_number}: " . $e->getMessage());
                }

                usleep(200000);
            }

            $this->info("[{$label}] Updated {$updated} orders.");
            $totalUpdated += $updated;
        }

        $this->info("Done! Total updated: {$totalUpdated}");

        return 0;
    }
}
