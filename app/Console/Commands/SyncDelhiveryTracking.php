<?php

namespace App\Console\Commands;

use App\Events\OrderDelivered;
use App\Models\Order;
use App\Services\DelhiveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncDelhiveryTracking extends Command
{
    protected $signature = 'delhivery:sync-tracking';
    protected $description = 'Poll Delhivery tracking API and auto-update order statuses';

    public function handle(DelhiveryService $delhivery): int
    {
        if (!$delhivery->isConfigured()) {
            return 0;
        }

        $orders = Order::where('carrier', 'Delhivery')
            ->whereNotNull('tracking_number')
            ->whereIn('status', ['shipped', 'out_for_delivery'])
            ->get();

        if ($orders->isEmpty()) {
            return 0;
        }

        $this->info("Syncing {$orders->count()} Delhivery shipments...");
        $updated = 0;

        foreach ($orders as $order) {
            try {
                $result = $delhivery->track($order->tracking_number);

                if (!$result['success']) {
                    continue;
                }

                $newStatus = $this->mapStatus($result['status'] ?? '');

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
                Log::debug("Delhivery sync failed for {$order->order_number}: " . $e->getMessage());
            }

            // Small delay to avoid rate limiting
            usleep(200000);
        }

        $this->info("Done! Updated {$updated} orders.");

        return 0;
    }

    private function mapStatus(string $status): ?string
    {
        $status = strtolower($status);

        return match (true) {
            str_contains($status, 'delivered') => 'delivered',
            str_contains($status, 'out for delivery') => 'out_for_delivery',
            str_contains($status, 'in transit'), str_contains($status, 'dispatched') => 'shipped',
            str_contains($status, 'rto'), str_contains($status, 'returned') => 'returned',
            str_contains($status, 'cancelled') => 'cancelled',
            default => null,
        };
    }
}
