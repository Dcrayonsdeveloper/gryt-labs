<?php

namespace App\Console\Commands;

use App\Events\OrderPlaced;
use App\Models\Order;
use App\Models\Payment;
use App\Services\CashfreeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncCashfreeOrders extends Command
{
    protected $signature = 'cashfree:sync
        {--from= : Start date (Y-m-d), defaults to 30 days ago}
        {--to= : End date (Y-m-d), defaults to today}
        {--dry-run : Show what would be synced without creating orders}';

    protected $description = 'Sync paid Cashfree orders that are missing from the admin panel';

    public function handle(CashfreeService $cashfree): int
    {
        if (!$cashfree->isConfigured()) {
            $this->error('Cashfree is not configured for this tenant.');
            return 1;
        }

        $from = $this->option('from') ?: now()->subDays(30)->toDateString();
        $to = $this->option('to') ?: now()->toDateString();
        $dryRun = $this->option('dry-run');

        $this->info("Fetching Cashfree orders from {$from} to {$to}...");

        // Fetch orders from Cashfree API
        $cfOrders = $this->fetchAllCashfreeOrders($cashfree, $from, $to);

        if (empty($cfOrders)) {
            $this->info('No orders found on Cashfree.');
            return 0;
        }

        $this->info('Found ' . count($cfOrders) . ' order(s) on Cashfree.');

        // Get existing order IDs from our DB
        $existingCfIds = Order::whereNotNull('razorpay_order_id')
            ->pluck('razorpay_order_id')
            ->toArray();

        // Also check metadata for older orders where razorpay_order_id was NULL
        $metadataCfIds = Order::whereNotNull('metadata')
            ->get()
            ->filter(fn ($o) => !empty($o->metadata['cashfree_order_id']))
            ->pluck('metadata.cashfree_order_id')
            ->toArray();

        $allKnownIds = array_unique(array_merge($existingCfIds, $metadataCfIds));

        $missing = [];
        $skipped = 0;

        foreach ($cfOrders as $cfOrder) {
            $orderId = $cfOrder['order_id'] ?? null;
            $status = $cfOrder['order_status'] ?? '';

            if (!$orderId || $status !== 'PAID') {
                $skipped++;
                continue;
            }

            if (in_array($orderId, $allKnownIds)) {
                continue;
            }

            $missing[] = $cfOrder;
        }

        if (empty($missing)) {
            $this->info('All Cashfree orders are already synced. Nothing to do.');
            return 0;
        }

        $this->warn(count($missing) . ' paid order(s) missing from admin:');
        $this->newLine();

        $rows = [];
        foreach ($missing as $cfOrder) {
            $customer = $cfOrder['customer_details'] ?? [];
            $rows[] = [
                $cfOrder['order_id'],
                '₹' . number_format((float) ($cfOrder['order_amount'] ?? 0), 2),
                $customer['customer_name'] ?? '-',
                $customer['customer_email'] ?? '-',
                $customer['customer_phone'] ?? '-',
                $cfOrder['created_at'] ?? '-',
            ];
        }

        $this->table(['Cashfree Order ID', 'Amount', 'Name', 'Email', 'Phone', 'Created'], $rows);

        if ($dryRun) {
            $this->info('Dry run — no orders created.');
            return 0;
        }

        if (!$this->confirm('Create these orders in admin?')) {
            $this->info('Cancelled.');
            return 0;
        }

        $created = 0;
        foreach ($missing as $cfOrder) {
            try {
                $order = $this->createOrderFromCashfree($cashfree, $cfOrder);
                $this->info("Created: {$order->order_number} ← {$cfOrder['order_id']}");
                $created++;
            } catch (\Throwable $e) {
                $this->error("Failed: {$cfOrder['order_id']} — {$e->getMessage()}");
                Log::error('cashfree:sync order creation failed', [
                    'cf_order_id' => $cfOrder['order_id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Done. Created {$created} order(s).");
        return 0;
    }

    private function fetchAllCashfreeOrders(CashfreeService $cashfree, string $from, string $to): array
    {
        // Cashfree API: GET /orders?order_status=PAID&cf_order_id=...
        // The list endpoint may require filtering — fetch recent orders
        $allOrders = [];
        $cursor = null;

        do {
            $params = ['cf_order_status' => 'PAID'];
            if ($cursor) {
                $params['last_id'] = $cursor;
            }

            $response = $cashfree->listOrders($params);

            if (!$response || !is_array($response)) {
                break;
            }

            // Cashfree may return orders directly as array or nested
            $orders = $response['data'] ?? $response;
            if (!is_array($orders) || empty($orders)) {
                break;
            }

            foreach ($orders as $order) {
                $createdAt = $order['created_at'] ?? '';
                // Filter by date range
                if ($createdAt && substr($createdAt, 0, 10) >= $from && substr($createdAt, 0, 10) <= $to) {
                    $allOrders[] = $order;
                }
            }

            // Pagination
            $cursor = end($orders)['cf_order_id'] ?? null;
            if (count($orders) < 25) {
                break; // Last page
            }
        } while ($cursor);

        return $allOrders;
    }

    private function createOrderFromCashfree(CashfreeService $cashfree, array $cfOrder): Order
    {
        $cfOrderId = $cfOrder['order_id'];
        $amount = (float) ($cfOrder['order_amount'] ?? 0);
        $customer = $cfOrder['customer_details'] ?? [];

        $customerName = $customer['customer_name'] ?? 'Cashfree Customer';
        $customerEmail = $customer['customer_email'] ?? null;
        $customerPhone = $customer['customer_phone'] ?? null;

        // Get payment details
        $payments = $cashfree->getOrderPayments($cfOrderId);
        $cfPaymentId = $payments[0]['cf_payment_id'] ?? null;
        $paymentMethodRaw = $payments[0]['payment_method'] ?? null;
        $methodStr = 'online';
        if (is_array($paymentMethodRaw)) {
            $methodStr = array_key_first($paymentMethodRaw) ?? 'online';
        }

        $order = DB::transaction(function () use ($cfOrderId, $cfPaymentId, $amount, $customerName, $customerEmail, $customerPhone, $methodStr, $cfOrder) {
            $order = Order::create([
                'user_id' => null,
                'guest_email' => $customerEmail,
                'guest_name' => $customerName,
                'guest_phone' => $customerPhone,
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'subtotal' => $amount,
                'discount' => 0,
                'shipping_cost' => 0,
                'tax' => 0,
                'total' => $amount,
                'paid_amount' => $amount,
                'razorpay_order_id' => $cfOrderId,
                'razorpay_payment_id' => $cfPaymentId,
                'shipping_address_snapshot' => ['name' => $customerName, 'phone' => $customerPhone ?? ''],
                'billing_address_snapshot' => ['name' => $customerName],
                'notes' => 'Synced from Cashfree — ' . ($cfOrder['order_note'] ?? 'no note'),
                'source' => 'api',
                'metadata' => [
                    'payment_method' => $methodStr,
                    'payment_gateway' => 'cashfree',
                    'cashfree_order_id' => $cfOrderId,
                    'cashfree_payment_id' => $cfPaymentId,
                    'created_by' => 'cashfree_sync',
                    'original_created_at' => $cfOrder['created_at'] ?? null,
                ],
            ]);

            if ($cfPaymentId && $amount > 0) {
                Payment::create([
                    'order_id' => $order->id,
                    'transaction_id' => (string) $cfPaymentId,
                    'gateway' => 'cashfree',
                    'gateway_transaction_id' => (string) $cfPaymentId,
                    'method' => $methodStr,
                    'amount' => $amount,
                    'currency' => 'INR',
                    'status' => 'captured',
                    'captured_at' => now(),
                ]);
            }

            return $order;
        });

        try {
            OrderPlaced::dispatch($order, 'api');
        } catch (\Throwable $e) {
            Log::error('OrderPlaced event failed (cashfree:sync)', ['order' => $order->id, 'error' => $e->getMessage()]);
        }

        return $order;
    }
}
