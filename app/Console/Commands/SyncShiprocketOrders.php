<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\ShiprocketService;
use Illuminate\Console\Command;

class SyncShiprocketOrders extends Command
{
    protected $signature = 'shiprocket:sync-orders {--limit=50 : Max orders to sync} {--order= : Sync a specific order by ID}';
    protected $description = 'Sync missing customer details from Shiprocket API for orders placed via Shiprocket Checkout';

    public function handle(ShiprocketService $srService): int
    {
        $singleOrderId = $this->option('order');

        $query = Order::whereNotNull('shiprocket_order_id')->whereNull('user_id');

        if ($singleOrderId) {
            $query = Order::where('id', $singleOrderId)->whereNotNull('shiprocket_order_id');
        } else {
            $query->where(function ($q) {
                $q->whereNull('guest_name')->orWhere('guest_name', '');
            });
        }

        $orders = $query->orderByDesc('created_at')->limit((int) $this->option('limit'))->get();

        if ($orders->isEmpty()) {
            $this->info('No orders need syncing.');
            return 0;
        }

        $this->info("Found {$orders->count()} orders to sync.");
        $synced = 0;
        $failed = 0;

        // --- Pass 1: list endpoint — one API call covers all orders ---
        if (!$singleOrderId) {
            $this->line('Trying stored webhook event data...');
            $listData = $srService->getCheckoutOrdersList(1, 100);

            if ($listData && !empty($listData['orders'])) {
                $this->info("  -> Got " . count($listData['orders']) . " orders from webhook events.");

                $srIndex = [];
                foreach ($listData['orders'] as $srOrder) {
                    $id = $srOrder['order_id'] ?? $srOrder['id'] ?? $srOrder['oid'] ?? null;
                    if ($id) {
                        $srIndex[(string) $id] = $srOrder;
                    }
                }

                foreach ($orders as $order) {
                    $srOrder = $srIndex[(string) $order->shiprocket_order_id] ?? null;
                    if (!$srOrder) {
                        continue;
                    }
                    $updated = $this->applyData($order, $srOrder);
                    if ($updated) {
                        $this->info("  [list] {$order->order_number} -> synced.");
                        $synced++;
                    }
                }

                // Narrow remaining orders to those still missing data
                $orders = $orders->filter(fn ($o) => empty($o->fresh()->guest_name));
                if ($orders->isEmpty()) {
                    $this->newLine();
                    $this->info("Done. Synced: {$synced}, Failed: 0 (list endpoint covered all)");
                    return 0;
                }
                $this->line("  -> {$orders->count()} order(s) not in list, trying per-order API...");
            } else {
                $this->warn('  -> No webhook event data found, trying per-order API.');
            }
        }

        // --- Pass 2: per-order Checkout API calls ---
        foreach ($orders as $order) {
            $this->line("Syncing {$order->order_number} (SR: {$order->shiprocket_order_id})...");

            $srOrder = $srService->getCheckoutOrder($order->shiprocket_order_id);

            if (!$srOrder) {
                $this->warn("  -> Checkout API returned no data, will try Shipping API in Pass 3.");
                continue;
            }

            $updated = $this->applyData($order, $srOrder);
            $updated ? $this->info('  -> Synced.') && $synced++ : $this->line('  -> No new data.');
        }

        // Reload what's still missing after both Checkout API passes
        $orders = $orders->filter(fn ($o) => empty($o->fresh()->guest_name));

        // --- Pass 3: Shiprocket Shipping API ---
        // Checkout orders are auto-mirrored to the Shipping platform, which exposes full
        // billing details. Match by channel_order_id = our order_number.
        if ($orders->isNotEmpty()) {
            $this->line('Trying Shipping API (apiv2.shiprocket.in)...');

            if (!$srService->isConfigured()) {
                $this->warn('  -> Shipping API not configured (shiprocket_email / shiprocket_password not set).');
                $failed += $orders->count();
            } else {
                $shippingIndex = [];
                $page = 1;

                do {
                    $this->line("  -> Fetching Shipping API page {$page}...");
                    $shippingData = $srService->getShippingOrders($page, 100);

                    if (!$shippingData || empty($shippingData['orders'])) {
                        $this->warn("  -> No data on page {$page}, stopping.");
                        break;
                    }

                    foreach ($shippingData['orders'] as $shOrder) {
                        $ref = (string) ($shOrder['channel_order_id'] ?? '');
                        if ($ref !== '') {
                            $shippingIndex[$ref] = $shOrder;
                        }
                        $sid = (string) ($shOrder['id'] ?? '');
                        if ($sid !== '') {
                            $shippingIndex[$sid] = $shOrder;
                        }
                    }

                    $page++;
                    $hasMore = count($shippingData['orders']) >= 100 && $page <= 10;
                } while ($hasMore);

                $this->line('  -> Indexed ' . count($shippingIndex) . ' Shipping API orders. Matching...');

                foreach ($orders as $order) {
                    $shOrder = $shippingIndex[$order->order_number]
                        ?? $shippingIndex[(string) $order->shiprocket_order_id]
                        ?? null;

                    if (!$shOrder) {
                        $this->warn("  [{$order->order_number}] not found in Shipping API.");
                        $failed++;
                        continue;
                    }

                    $updated = $this->applyData($order->fresh(), $shOrder);
                    if ($updated) {
                        $this->info("  [{$order->order_number}] synced from Shipping API.");
                        $synced++;
                    } else {
                        $this->line("  [{$order->order_number}] found but no new data.");
                    }
                }
            }
        }

        $this->newLine();
        $this->info("Done. Synced: {$synced}, Failed/No data: {$failed}");
        return 0;
    }

    private function applyData(Order $order, array $srOrder): bool
    {
        $c = $srOrder['customer_details'] ?? $srOrder;
        $updates = [];

        if (empty($order->guest_name)) {
            $name = trim(
                ($c['billing_customer_name'] ?? $c['customer_name'] ?? $c['name'] ?? '') . ' ' .
                ($c['billing_last_name'] ?? '')
            );
            if (!empty($name)) {
                $updates['guest_name'] = $name;
            }
        }

        if (empty($order->guest_email)) {
            $email = $c['billing_email'] ?? $c['customer_email'] ?? $c['email'] ?? null;
            if (!empty($email)) {
                $updates['guest_email'] = $email;
            }
        }

        if (empty($order->guest_phone)) {
            $phone = $c['billing_phone'] ?? $c['customer_phone'] ?? $c['phone'] ?? $c['mobile'] ?? null;
            if (!empty($phone)) {
                $updates['guest_phone'] = $phone;
            }
        }

        if (empty($order->shipping_address_snapshot) && !empty($updates)) {
            $updates['shipping_address_snapshot'] = [
                'name'           => $updates['guest_name'] ?? '',
                'phone'          => $updates['guest_phone'] ?? '',
                'address_line_1' => $c['billing_address'] ?? $c['address'] ?? '',
                'address_line_2' => $c['billing_address_2'] ?? '',
                'city'           => $c['billing_city'] ?? $c['city'] ?? '',
                'state'          => $c['billing_state'] ?? $c['state'] ?? '',
                'postal_code'    => $c['billing_pincode'] ?? $c['pincode'] ?? '',
                'country'        => $c['billing_country'] ?? 'India',
            ];
        }

        if (!empty($updates)) {
            $order->update($updates);
            return true;
        }

        return false;
    }
}
