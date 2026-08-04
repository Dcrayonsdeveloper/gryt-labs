<?php

namespace App\Services\ShiprocketCheckout;

use App\Events\OrderPlaced;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShipment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\ShiprocketService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Verification, reconciliation, repair and recovery engine for Shiprocket orders.
 *
 * Webhooks remain the primary real-time sync mechanism (WebhookController /
 * ShiprocketWebhookController are untouched); this engine is the guarantee of
 * parity when they are missed — which on this account is the norm, since
 * Shiprocket has never delivered a webhook here (shiprocket_checkout_events
 * is empty and the courier webhook endpoints have zero hits).
 *
 * Two phases, both idempotent, keyed on the Shiprocket order id:
 *
 *  1. discoverAndCreate() — every checkout token we issued is tracked in
 *     shiprocket_checkout_ids; ask the Checkout order-details API about recent
 *     ones with no local Order and create the ones that completed (SUCCESS).
 *     This also restores orders that were deleted locally but still exist at
 *     the source.
 *
 *  2. verifyAndRepair() — per existing order, pull the authoritative state and
 *     fill/correct: pricing + coupons (syncOrderPricing), customer identity,
 *     shipping address, line items, the payments ledger, fulfillment (AWB /
 *     courier / shipment record), order status + real lifecycle timestamps,
 *     and the AWB tracking timeline. Every write is either fill-if-blank or
 *     forward-only; nothing is deleted and stock is never touched during
 *     repair (a divergence is reported instead).
 *
 * Enum safety (prod is MySQL with ENUM columns — out-of-range writes BLANK the
 * value): order_shipments.status and payments.method/status are only ever
 * written through the map*() helpers, which emit values from the column's set.
 */
class OrderSyncEngine
{
    /** Forward-only order-status ranks — the engine never moves an order backwards. */
    private const STATUS_RANK = [
        'pending' => 0, 'confirmed' => 1, 'processing' => 2, 'packed' => 3,
        'shipped' => 4, 'out_for_delivery' => 5, 'delivered' => 6,
        'returned' => 7, 'cancelled' => 7,
    ];

    public int $apiCalls = 0;

    public function __construct(
        private ShiprocketCheckoutService $checkout,
        private ShiprocketService $shipping,
    ) {}

    // ─── Phase 1: discover & create missing orders ──────────────────────────

    /**
     * Find completed Shiprocket Checkout orders with no local Order and create
     * them. Also the restore path for locally-deleted orders (the token row in
     * shiprocket_checkout_ids survives deletion).
     *
     * @return array{scanned:int, created:array, existing:int, incomplete:int, failed:array, api_calls:int}
     */
    public function discoverAndCreate(int $days = 7, ?string $onlyId = null): array
    {
        $report = ['scanned' => 0, 'created' => [], 'existing' => 0, 'incomplete' => 0, 'failed' => [], 'api_calls' => 0];
        $before = $this->apiCalls;

        foreach ($this->candidateIds($days, $onlyId) as $cid) {
            $report['scanned']++;

            if ($this->findLocalOrder($cid)) {
                $report['existing']++;
                continue;
            }

            try {
                $sr = $this->checkout->getOrder($cid);
                $this->apiCalls++;
            } catch (\Throwable $e) {
                $report['failed'][] = ['id' => $cid, 'error' => 'checkout API: ' . $e->getMessage()];
                continue;
            }

            if (! is_array($sr)
                || strtoupper((string) ($sr['status'] ?? '')) !== 'SUCCESS'
                || empty($sr['cart_data']['items'])) {
                $report['incomplete']++; // abandoned / failed / still-pending checkout — not an order
                continue;
            }

            // Never double-create under an alternate id Shiprocket may use.
            $known = false;
            foreach (array_filter(array_map('strval', [$sr['cart_id'] ?? '', $sr['platform_order_id'] ?? '', $sr['fastrr_order_id'] ?? ''])) as $aid) {
                if ($this->findLocalOrder($aid)) { $known = true; break; }
            }
            if ($known) { $report['existing']++; continue; }

            $res = $this->createFromShape($this->buildFromCheckoutApi($cid, $sr));
            if ($res['status'] === 'created') {
                $report['created'][] = ['id' => $cid, 'order_number' => $res['order']->order_number, 'total' => (float) $res['order']->total];
            } elseif ($res['status'] === 'exists') {
                $report['existing']++;
            } else {
                $report['failed'][] = ['id' => $cid, 'error' => $res['message']];
            }
        }

        $report['api_calls'] = $this->apiCalls - $before;
        return $report;
    }

    /** Recent checkout tokens with no matching local order (or the single requested id). */
    private function candidateIds(int $days, ?string $onlyId): array
    {
        if ($onlyId) {
            return [$onlyId];
        }

        return DB::table('shiprocket_checkout_ids')
            ->where('id_type', 'token')
            ->where('created_at', '>=', now()->subDays(max(1, $days)))
            ->orderByDesc('id')
            ->pluck('shiprocket_id')
            ->map(fn ($v) => (string) $v)
            ->unique()
            ->values()
            ->all();
    }

    /** Locate a local Order by any of the ids Shiprocket Checkout can key it under. */
    public function findLocalOrder(string $ref): ?Order
    {
        if ($ref === '') {
            return null;
        }

        return Order::where('shiprocket_order_id', $ref)
            ->orWhere('order_number', $ref)
            ->orWhereJsonContains('metadata->shiprocket_checkout_id', $ref)
            ->orWhereJsonContains('metadata->shiprocket_cart_id', $ref)
            ->first();
    }

    /**
     * Normalise a Checkout order-details payload (getOrder → result) into the
     * shared recoverable-order shape. Confirmed live field shape:
     * cart_data.items[].{variant_id,quantity,price} · subtotal_price ·
     * total_discount · shipping_charges · total_amount_payable · payment_type ·
     * payment_status · shipping_address.{first_name,phone,email,line1,line2,…}.
     */
    public function buildFromCheckoutApi(string $checkoutId, array $sr): array
    {
        $addr = $sr['shipping_address'] ?? $sr['billing_address'] ?? [];

        $items = [];
        foreach (($sr['cart_data']['items'] ?? []) as $i) {
            $items[] = [
                'product_id' => $i['variant_id'] ?? $i['product_id'] ?? null,
                'name' => $i['name'] ?? $i['title'] ?? 'Product',
                'sku' => $i['sku'] ?? '',
                'quantity' => (int) ($i['quantity'] ?? 1),
                'price' => (float) ($i['price'] ?? 0),
            ];
        }

        $subtotal = array_sum(array_map(fn ($i) => $i['price'] * $i['quantity'], $items));
        if ($subtotal <= 0) {
            $subtotal = (float) ($sr['subtotal_price'] ?? 0);
        }

        $discount = (float) ($sr['total_discount'] ?? $sr['coupon_discount'] ?? 0);
        $shipping = (float) ($sr['shipping_charges'] ?? 0);
        $total = (float) ($sr['total_amount_payable'] ?? max(0, $subtotal - $discount + $shipping));

        $isPrepaid = strtoupper((string) ($sr['payment_type'] ?? '')) === 'PREPAID';
        $paidOk = strtolower((string) ($sr['payment_status'] ?? '')) === 'success';

        $name = trim(($addr['first_name'] ?? '') . ' ' . ($addr['last_name'] ?? ''));

        return [
            'shiprocket_id' => $checkoutId,
            'source' => 'checkout API',
            'customer' => [
                'name' => $name ?: null,
                'email' => $addr['email'] ?? $sr['email'] ?? null,
                'phone' => $addr['phone'] ?? $sr['phone'] ?? null,
                'address' => $addr['line1'] ?? '',
                'address_2' => $addr['line2'] ?? '',
                'city' => $addr['city'] ?? '',
                'state' => $addr['state'] ?? '',
                'pincode' => $addr['pincode'] ?? '',
                'country' => $addr['country'] ?? 'India',
            ],
            'items' => $items,
            'payment_mode' => $isPrepaid ? 'prepaid' : 'cod',
            'amounts' => [
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping' => $shipping,
                'tax' => 0.0,
                'total' => $total,
                'paid' => ($isPrepaid && $paidOk) ? $total : 0.0,
            ],
        ];
    }

    /** Resolve an item line to a Product by id → SKU → exact name. */
    public function resolveProduct(array $item): ?Product
    {
        $product = ! empty($item['product_id']) ? Product::find($item['product_id']) : null;
        if (! $product && ! empty($item['sku'])) {
            $product = Product::where('sku', $item['sku'])->first();
        }
        if (! $product && ! empty($item['name'])) {
            $product = Product::where('name', $item['name'])->first();
        }

        return $product;
    }

    /**
     * Create an Order + items from a recoverable-order shape and dispatch
     * OrderPlaced. Shared by the engine, the reconcile command and the admin
     * button so there is exactly ONE creation path. Idempotent: re-checks for
     * an existing order inside the transaction under an advisory lock.
     *
     * @return array{status:'created'|'exists'|'skipped_product'|'failed', order:?Order, message:string}
     */
    public function createFromShape(array $m): array
    {
        // Resolve every line to a real product BEFORE the transaction —
        // order_items.product_id is NOT NULL on prod MySQL.
        foreach ($m['items'] as $idx => $item) {
            $product = $this->resolveProduct($item);
            if (! $product) {
                return ['status' => 'skipped_product', 'order' => null,
                    'message' => "product not found for '" . ($item['name'] ?? $item['sku'] ?? '?') . "', create manually"];
            }
            $m['items'][$idx]['product_id'] = $product->id;
        }

        try {
            $order = DB::transaction(function () use ($m) {
                if (DB::connection()->getDriverName() === 'pgsql') {
                    DB::select('SELECT pg_advisory_xact_lock(hashtext(?))', [$m['shiprocket_id']]);
                }

                if ($existing = $this->findLocalOrder($m['shiprocket_id'])) {
                    return $existing; // wasRecentlyCreated = false signals "exists"
                }

                $cust = $m['customer'];
                $a = $m['amounts'];

                if ($m['payment_mode'] === 'cod') {
                    $paymentStatus = 'pending';
                    $paid = 0.0;
                } elseif ($a['paid'] > 0 && $a['paid'] < $a['total']) {
                    $paymentStatus = 'partial';
                    $paid = $a['paid'];
                } else {
                    $paymentStatus = 'paid';
                    $paid = $a['total'];
                }

                $cleanPhone = $cust['phone'] ? preg_replace('/\D/', '', $cust['phone']) : null;
                $userId = ($cust['email'] || $cleanPhone)
                    ? User::query()
                        ->when($cust['email'], fn ($q) => $q->orWhere('email', $cust['email']))
                        ->when($cleanPhone, fn ($q) => $q->orWhere('phone', $cleanPhone))
                        ->value('id')
                    : null;

                $order = Order::create([
                    'user_id' => $userId,
                    'guest_name' => $cust['name'] ?: null,
                    'guest_email' => $cust['email'] ?: null,
                    'guest_phone' => $cust['phone'] ?: null,
                    'status' => 'confirmed',
                    'payment_status' => $paymentStatus,
                    'subtotal' => $a['subtotal'],
                    'discount' => $a['discount'],
                    'shipping_cost' => $a['shipping'],
                    'tax' => $a['tax'],
                    'total' => $a['total'],
                    'paid_amount' => $paid,
                    'source' => 'api',
                    'shiprocket_order_id' => $m['shiprocket_id'],
                    'shipping_address_snapshot' => array_filter([
                        'name' => $cust['name'] ?? '',
                        'phone' => $cust['phone'] ?? '',
                        'address_line_1' => $cust['address'] ?? '',
                        'address_line_2' => $cust['address_2'] ?? '',
                        'city' => $cust['city'] ?? '',
                        'state' => $cust['state'] ?? '',
                        'postal_code' => $cust['pincode'] ?? '',
                        'country' => $cust['country'] ?? 'India',
                    ]),
                    'metadata' => array_filter([
                        'payment_method' => $m['payment_mode'],
                        'payment_gateway' => 'shiprocket',
                        'shiprocket_checkout_id' => $m['shiprocket_id'],
                        'created_from' => 'sync_engine',
                        'reconcile_source' => $m['source'],
                        'reconciled_at' => now()->toIso8601String(),
                    ]),
                ]);

                foreach ($m['items'] as $item) {
                    $this->createItem($order, $item);
                }

                // Backdate to the REAL order time. A recovered order must show
                // when the customer actually placed it, not when the engine
                // found it. The checkout token is issued the moment checkout
                // starts, so its created_at matches the Shiprocket panel time
                // to the minute. (The Checkout API's order_created_date is NOT
                // usable — it returns an internal update stamp, not placement.)
                $placedAt = DB::table('shiprocket_checkout_ids')
                    ->where('shiprocket_id', $m['shiprocket_id'])
                    ->value('created_at');
                if ($placedAt) {
                    try {
                        $ts = Carbon::parse($placedAt);
                        if ($ts->isPast()) {
                            $order->created_at = $ts;
                            $order->confirmed_at = $ts;
                            $order->save();
                        }
                    } catch (\Throwable) {
                    }
                }

                return $order;
            });

            if (! $order->wasRecentlyCreated) {
                return ['status' => 'exists', 'order' => $order, 'message' => "already exists as {$order->order_number}"];
            }

            $order->load('items.product', 'user');
            try {
                OrderPlaced::dispatch($order, 'shiprocket_checkout');
            } catch (\Throwable $e) {
                Log::error('OrderSyncEngine: OrderPlaced dispatch failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            }

            Log::info('OrderSyncEngine: recovered order', [
                'order_id' => $order->id, 'order_number' => $order->order_number,
                'shiprocket_order_id' => $m['shiprocket_id'], 'source' => $m['source'],
            ]);

            return ['status' => 'created', 'order' => $order, 'message' => "created {$order->order_number}"];
        } catch (\Throwable $e) {
            Log::error('OrderSyncEngine: creation failed', ['shiprocket_order_id' => $m['shiprocket_id'], 'error' => $e->getMessage()]);
            return ['status' => 'failed', 'order' => null, 'message' => $e->getMessage()];
        }
    }

    /** Create one OrderItem and decrement stock (creation path only — never on repair). */
    private function createItem(Order $order, array $item): void
    {
        $product = $this->resolveProduct($item);

        $qty = max(1, (int) $item['quantity']);
        $price = (float) $item['price'];

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product?->id,
            'product_name' => $product?->name ?? $item['name'] ?? 'Product',
            'sku' => $product?->sku ?? $item['sku'] ?? '',
            'quantity' => $qty,
            'mrp' => $product?->mrp ?? $price,
            'price' => $price,
            'tax' => 0,
            'discount' => 0,
            'total' => $price * $qty,
        ]);

        if (! $product) {
            return;
        }
        $locked = Product::where('id', $product->id)->lockForUpdate()->first();
        if ($locked && $locked->stock_quantity >= $qty) {
            $locked->decrement('stock_quantity', $qty);
            $locked->increment('sales_count', $qty);
            if ($locked->fresh()->stock_quantity <= 0) {
                $locked->update(['stock_status' => 'out_of_stock']);
            }
        } else {
            $product->increment('sales_count', $qty);
        }
    }

    // ─── Phase 2: verify & repair an existing order ─────────────────────────

    /**
     * Verify one order against the source platform and repair every divergence
     * the APIs can answer for. Never throws — every sub-step is isolated so one
     * failure can't abort the rest of the order (or the batch).
     *
     * @return array{order_number:string, repairs:array, discrepancies:array, errors:array, api_calls:int, changed:bool}
     */
    public function verifyAndRepair(Order $order): array
    {
        $repairs = [];
        $discrepancies = [];
        $errors = [];
        $apiBefore = $this->apiCalls;

        $order->loadMissing('items');
        $srcId = (string) $order->shiprocket_order_id;
        $isCheckoutId = (bool) preg_match('/^[0-9a-f]{20,32}$/i', $srcId);

        // A. Checkout API — pricing, payment, customer, address, items, transactions
        $sr = null;
        if ($isCheckoutId) {
            try {
                $sr = $this->checkout->getOrder($srcId);
                $this->apiCalls++;
            } catch (\Throwable $e) {
                $errors[] = 'checkout API: ' . $e->getMessage();
            }
        }

        if (is_array($sr)) {
            try {
                $s = $this->checkout->syncOrderPricing($order, false, $sr);
                if ($s && $s['changed']) {
                    $repairs[] = 'pricing/payment';
                    $order->refresh();
                }
            } catch (\Throwable $e) {
                $errors[] = 'pricing: ' . $e->getMessage();
            }

            try {
                if ($this->repairCustomer($order, $sr)) {
                    $repairs[] = 'customer';
                }
                if ($this->repairAddress($order, $sr)) {
                    $repairs[] = 'address';
                }
            } catch (\Throwable $e) {
                $errors[] = 'customer/address: ' . $e->getMessage();
            }

            try {
                $itemResult = $this->repairItems($order, $sr);
                if ($itemResult['repaired']) {
                    $repairs[] = $itemResult['repaired'];
                }
                if ($itemResult['discrepancy']) {
                    $discrepancies[] = $itemResult['discrepancy'];
                }
            } catch (\Throwable $e) {
                $errors[] = 'items: ' . $e->getMessage();
            }

            try {
                $n = $this->repairTransactions($order, $sr);
                if ($n > 0) {
                    $repairs[] = "transactions ledger (+{$n})";
                }
            } catch (\Throwable $e) {
                $errors[] = 'transactions: ' . $e->getMessage();
            }
        }

        // B. Shipping API — fulfillment, AWB, courier, status, lifecycle timestamps
        $ship = null;
        if ($this->shipping->isConfigured() && $srcId !== '') {
            try {
                $ship = $isCheckoutId
                    ? $this->shipping->findShippingOrderByCheckoutId($srcId)
                    : $this->shipping->getOrder($srcId);
                $this->apiCalls++;
            } catch (\Throwable $e) {
                $errors[] = 'shipping API: ' . $e->getMessage();
            }
        }

        if (is_array($ship)) {
            try {
                foreach ($this->repairFulfillment($order, $ship) as $r) {
                    $repairs[] = $r;
                }
            } catch (\Throwable $e) {
                $errors[] = 'fulfillment: ' . $e->getMessage();
            }
        }

        // C. AWB tracking timeline → order_shipments.tracking_history
        if ($order->shiprocket_awb && $this->shipping->isConfigured()) {
            try {
                $n = $this->repairTrackingTimeline($order);
                if ($n > 0) {
                    $repairs[] = "tracking timeline (+{$n} events)";
                }
            } catch (\Throwable $e) {
                $errors[] = 'tracking: ' . $e->getMessage();
            }
        }

        // D. Sequence repair — recovery-created orders must carry the REAL
        // placement time, not the time the engine found them. Only orders the
        // recovery paths created are re-anchored: callback-created orders keep
        // their payment-completion time (their checkout token can legitimately
        // be much older — a customer may start checkout one day and pay the next).
        try {
            if ($this->repairPlacementTime($order)) {
                $repairs[] = 'order time → real placement time';
            }
        } catch (\Throwable $e) {
            $errors[] = 'placement time: ' . $e->getMessage();
        }

        // Stamp verification checkpoint (drives the background job's round-robin).
        try {
            $meta = is_array($order->fresh()->metadata) ? $order->fresh()->metadata : [];
            $meta['sr_verified_at'] = now()->toIso8601String();
            Order::withoutEvents(fn () => Order::whereKey($order->id)->update(['metadata' => json_encode($meta)]));
        } catch (\Throwable $e) {
            $errors[] = 'checkpoint: ' . $e->getMessage();
        }

        return [
            'order_number' => $order->order_number,
            'repairs' => $repairs,
            'discrepancies' => $discrepancies,
            'errors' => $errors,
            'api_calls' => $this->apiCalls - $apiBefore,
            'changed' => ! empty($repairs),
        ];
    }

    /**
     * Re-anchor a recovery-created order to its real placement time (the
     * checkout token's created_at, which matches the Shiprocket panel to the
     * minute). Applies ONLY to orders created by the recovery paths — never to
     * callback-created orders, whose created_at is the payment time.
     */
    private function repairPlacementTime(Order $order): bool
    {
        $createdFrom = $order->metadata['created_from'] ?? '';
        if (! in_array($createdFrom, ['sync_engine', 'reconcile_command'], true)) {
            return false;
        }

        $tok = DB::table('shiprocket_checkout_ids')
            ->where('shiprocket_id', (string) $order->shiprocket_order_id)
            ->value('created_at');
        if (! $tok) {
            return false;
        }

        $ts = Carbon::parse($tok);
        if (! $ts->isPast() || abs($ts->diffInMinutes($order->created_at)) <= 5) {
            return false;
        }

        $order->created_at = $ts;
        $order->confirmed_at = $ts;
        $order->save();
        return true;
    }

    /** Fill missing guest identity from the Checkout payload. Fill-if-blank only. */
    private function repairCustomer(Order $order, array $sr): bool
    {
        $cust = $this->checkout->extractCustomer($sr);
        $updates = [];

        if (empty($order->guest_name) && ! empty($cust['name'])) {
            $updates['guest_name'] = $cust['name'];
        }
        if (empty($order->guest_email) && ! empty($cust['email'])) {
            $updates['guest_email'] = $cust['email'];
        }
        if (empty($order->guest_phone) && ! empty($cust['phone'])) {
            $updates['guest_phone'] = $cust['phone'];
        }
        if (empty($order->user_id)) {
            $phone = preg_replace('/\D/', '', (string) ($updates['guest_phone'] ?? $order->guest_phone ?? ''));
            $email = $updates['guest_email'] ?? $order->guest_email;
            if ($phone || $email) {
                $userId = User::query()
                    ->when($email, fn ($q) => $q->orWhere('email', $email))
                    ->when($phone, fn ($q) => $q->orWhere('phone', strlen($phone) > 10 ? substr($phone, -10) : $phone))
                    ->value('id');
                if ($userId) {
                    $updates['user_id'] = $userId;
                }
            }
        }

        if ($updates) {
            $order->update($updates);
            return true;
        }
        return false;
    }

    /** Fill a missing/empty shipping address snapshot from the Checkout payload. */
    private function repairAddress(Order $order, array $sr): bool
    {
        $snap = $order->shipping_address_snapshot ?? [];
        $hasAddress = ! empty($snap['address_line_1']) || ! empty($snap['city']) || ! empty($snap['postal_code']);
        if ($hasAddress) {
            return false;
        }

        $cust = $this->checkout->extractCustomer($sr);
        if (empty($cust['address'])) {
            return false;
        }
        $a = $cust['address'];
        if (empty($a['address_line_1']) && empty($a['city']) && empty($a['postal_code'])) {
            return false;
        }

        $order->update(['shipping_address_snapshot' => [
            'name' => $cust['name'] ?? $order->guest_name ?? '',
            'phone' => $cust['phone'] ?? $order->guest_phone ?? '',
            'address_line_1' => $a['address_line_1'] ?? '',
            'address_line_2' => $a['address_line_2'] ?? '',
            'city' => $a['city'] ?? '',
            'state' => $a['state'] ?? '',
            'postal_code' => $a['postal_code'] ?? '',
            'country' => $a['country'] ?? 'India',
        ]]);
        return true;
    }

    /**
     * Verify line items against Shiprocket's cart. Rebuilds a childless order,
     * corrects quantity drift and adds source lines we're missing. Never deletes
     * a local line and never adjusts stock during repair — the order may already
     * be picked/shipped, so a physical-stock correction here would be wrong;
     * extra local lines are reported as a discrepancy for a human instead.
     *
     * @return array{repaired: ?string, discrepancy: ?string}
     */
    private function repairItems(Order $order, array $sr): array
    {
        $srItems = $sr['cart_data']['items'] ?? [];
        if (empty($srItems)) {
            return ['repaired' => null, 'discrepancy' => null];
        }

        // Source cart keyed by resolved local product id.
        $srByProduct = [];
        foreach ($srItems as $i) {
            $product = $this->resolveProduct([
                'product_id' => $i['variant_id'] ?? $i['product_id'] ?? null,
                'sku' => $i['sku'] ?? '',
                'name' => $i['name'] ?? '',
            ]);
            if ($product) {
                $srByProduct[$product->id] = [
                    'product' => $product,
                    'quantity' => (int) ($i['quantity'] ?? 1),
                    'price' => (float) ($i['price'] ?? 0),
                ];
            }
        }
        if (empty($srByProduct)) {
            return ['repaired' => null, 'discrepancy' => 'source items could not be matched to local products'];
        }

        // Childless order → rebuild all lines from source (no stock changes).
        if ($order->items->isEmpty()) {
            foreach ($srByProduct as $pid => $line) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $pid,
                    'product_name' => $line['product']->name,
                    'sku' => $line['product']->sku ?? '',
                    'quantity' => $line['quantity'],
                    'mrp' => $line['product']->mrp ?? $line['price'],
                    'price' => $line['price'],
                    'tax' => 0,
                    'discount' => 0,
                    'total' => $line['price'] * $line['quantity'],
                ]);
            }
            $order->load('items');
            return ['repaired' => 'line items rebuilt (' . count($srByProduct) . ')', 'discrepancy' => null];
        }

        $fixed = 0;
        $localByProduct = $order->items->keyBy('product_id');

        foreach ($srByProduct as $pid => $line) {
            $local = $localByProduct->get($pid);
            if (! $local) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $pid,
                    'product_name' => $line['product']->name,
                    'sku' => $line['product']->sku ?? '',
                    'quantity' => $line['quantity'],
                    'mrp' => $line['product']->mrp ?? $line['price'],
                    'price' => $line['price'],
                    'tax' => 0,
                    'discount' => 0,
                    'total' => $line['price'] * $line['quantity'],
                ]);
                $fixed++;
                continue;
            }
            if ((int) $local->quantity !== $line['quantity']) {
                $local->update([
                    'quantity' => $line['quantity'],
                    'total' => (float) $local->price * $line['quantity'],
                ]);
                $fixed++;
            }
        }

        $extra = $localByProduct->keys()->diff(array_keys($srByProduct));

        return [
            'repaired' => $fixed > 0 ? "item lines corrected ({$fixed})" : null,
            'discrepancy' => $extra->isNotEmpty()
                ? 'local has ' . $extra->count() . ' line(s) not in the Shiprocket cart (product ids ' . $extra->join(', ') . ') — review manually'
                : null,
        ];
    }

    /**
     * Mirror the Checkout payments[] into the real payments ledger. Idempotent
     * on transaction_id. Only enum-safe method/status values are written.
     */
    private function repairTransactions(Order $order, array $sr): int
    {
        $created = 0;
        foreach ((array) ($sr['payments'] ?? []) as $pay) {
            $txnId = (string) ($pay['txn_id'] ?? $pay['pg_transaction_id'] ?? '');
            if ($txnId === '') {
                continue;
            }

            $status = $this->mapPaymentStatus((string) ($pay['payment_status'] ?? ''));
            $exists = Payment::where('transaction_id', $txnId)->exists();
            Payment::updateOrCreate(
                ['transaction_id' => $txnId],
                [
                    'order_id' => $order->id,
                    'gateway' => (string) ($pay['gateway'] ?? 'shiprocket'),
                    'gateway_transaction_id' => (string) ($pay['pg_transaction_id'] ?? $txnId),
                    'method' => $this->mapPaymentMethod((string) ($pay['payment_method'] ?? '')),
                    'amount' => (float) ($pay['amount_received'] ?? $pay['amount'] ?? 0),
                    'currency' => 'INR',
                    'status' => $status,
                    'gateway_response' => $pay,
                    'captured_at' => $status === 'captured' && ! empty($pay['created_at'])
                        ? Carbon::parse($pay['created_at'])
                        : null,
                ]
            );
            if (! $exists) {
                $created++;
            }
        }
        return $created;
    }

    /**
     * Fulfillment repair from a Shipping-API order row: AWB / courier /
     * shipment id / tracking url / ETD, the order_shipments record, raw
     * Shiprocket activities, and a forward-only order-status advance with the
     * REAL lifecycle timestamps (not now()) + an Activity Log entry.
     *
     * @return array<string> repairs applied
     */
    private function repairFulfillment(Order $order, array $ship): array
    {
        $repairs = [];

        $sh = (array) ($ship['shipments'] ?? []);
        if (isset($sh[0]) && is_array($sh[0])) {
            $sh = $sh[0];
        }

        $awb = (string) ($sh['awb'] ?? '') ?: (string) ($ship['last_mile_awb'] ?? '');
        $courier = (string) ($sh['courier'] ?? '') ?: (string) ($ship['last_mile_courier_name'] ?? '');
        $trackUrl = (string) ($ship['last_mile_awb_track_url'] ?? '');
        $etd = $sh['etd'] ?? $ship['etd_date'] ?? null;

        // Fill-if-blank identifiers. tracking_number/carrier matter beyond
        // display: the admin "fulfilled" card keys off them, so a
        // Shiprocket-shipped order stops rendering as "Unfulfilled".
        $updates = [];
        if (empty($order->shiprocket_awb) && $awb !== '') {
            $updates['shiprocket_awb'] = $awb;
        }
        if (empty($order->shiprocket_courier) && $courier !== '') {
            $updates['shiprocket_courier'] = $courier;
        }
        if (empty($order->shiprocket_shipment_id) && ! empty($sh['id'])) {
            $updates['shiprocket_shipment_id'] = (string) $sh['id'];
        }
        if (empty($order->tracking_number) && $awb !== '') {
            $updates['tracking_number'] = $awb;
        }
        if (empty($order->carrier) && $courier !== '') {
            $updates['carrier'] = $courier;
        }
        if (empty($order->tracking_url) && $trackUrl !== '') {
            $updates['tracking_url'] = $trackUrl;
        }
        if (empty($order->expected_delivery_date) && $etd) {
            try {
                $updates['expected_delivery_date'] = Carbon::parse($etd)->toDateString();
            } catch (\Throwable) {
            }
        }
        if ($updates) {
            $order->update($updates);
            $repairs[] = 'fulfillment (' . implode(', ', array_keys($updates)) . ')';
        }

        // Keep the raw source snapshot + Shiprocket's own activity list
        // (idempotent wholesale replace — the source is authoritative).
        $meta = is_array($order->metadata) ? $order->metadata : [];
        $shippingMeta = [
            'status' => (string) ($ship['status'] ?? ''),
            'status_code' => $ship['status_code'] ?? null,
            'awb' => $awb ?: null,
            'courier' => $courier ?: null,
            'etd' => $etd,
            'synced_at' => now()->toIso8601String(),
        ];
        $metaChanged = ($meta['sr_shipping']['status'] ?? null) !== $shippingMeta['status'];
        $meta['sr_shipping'] = $shippingMeta;
        if (! empty($ship['activities']) && is_array($ship['activities'])) {
            $meta['sr_activities'] = $ship['activities'];
        }
        $order->update(['metadata' => $meta]);
        if ($metaChanged && $shippingMeta['status'] !== '') {
            $repairs[] = "shiprocket status ({$shippingMeta['status']})";
        }

        // Forward-only status advance with real timestamps + Activity Log entry.
        $mapped = $this->mapShippingStatusToOrder((string) ($ship['status'] ?? ''));
        $currentRank = self::STATUS_RANK[$order->status] ?? 0;
        if ($mapped
            && (self::STATUS_RANK[$mapped] ?? 0) > $currentRank
            && ! in_array($order->status, ['cancelled', 'returned'], true)) {
            $stamp = [];
            $shippedAt = $sh['shipped_date'] ?? $sh['pickedup_timestamp'] ?? $ship['picked_up_date'] ?? null;
            $ofdAt = $ship['out_for_delivery_date'] ?? $ship['first_out_for_delivery_date'] ?? null;
            $deliveredAt = $sh['delivered_date'] ?? $ship['delivered_date'] ?? null;
            try {
                if (! $order->shipped_at && $shippedAt && (self::STATUS_RANK[$mapped] >= self::STATUS_RANK['shipped'])) {
                    $stamp['shipped_at'] = Carbon::parse($shippedAt);
                }
                if (! $order->out_for_delivery_at && $ofdAt && (self::STATUS_RANK[$mapped] >= self::STATUS_RANK['out_for_delivery'])) {
                    $stamp['out_for_delivery_at'] = Carbon::parse($ofdAt);
                }
                if (! $order->delivered_at && $deliveredAt && $mapped === 'delivered') {
                    $stamp['delivered_at'] = Carbon::parse($deliveredAt);
                }
                if ($mapped === 'cancelled' && ! $order->cancelled_at) {
                    $stamp['cancelled_at'] = now();
                }
            } catch (\Throwable) {
            }

            $order->update(['status' => $mapped] + $stamp);
            $order->statusHistory()->create([
                'status' => $mapped,
                'comment' => 'Synced from Shiprocket (source status: ' . ($ship['status'] ?? '?') . ')',
                'created_by' => null,
            ]);
            $repairs[] = "order status → {$mapped}";
        }

        // Upsert the order_shipments record (keyed on AWB; enum-safe status).
        if ($awb !== '') {
            $shipment = OrderShipment::firstOrNew(['order_id' => $order->id, 'tracking_number' => $awb]);
            $newStatus = $this->mapShipmentStatus((string) ($ship['status'] ?? ''));
            $dirty = ! $shipment->exists;
            if (! $shipment->exists) {
                $shipment->carrier = $courier ?: 'Shiprocket';
                $shipment->status = $newStatus;
            } elseif ($this->shipmentStatusRank($newStatus) > $this->shipmentStatusRank((string) $shipment->status)) {
                $shipment->status = $newStatus;
                $dirty = true;
            }
            try {
                if (! $shipment->shipped_at && ! empty($sh['shipped_date'])) {
                    $shipment->shipped_at = Carbon::parse($sh['shipped_date']);
                    $dirty = true;
                }
                if (! $shipment->delivered_at && ! empty($sh['delivered_date'])) {
                    $shipment->delivered_at = Carbon::parse($sh['delivered_date']);
                    $dirty = true;
                }
            } catch (\Throwable) {
            }
            if ($dirty) {
                $shipment->save();
                $repairs[] = 'shipment record';
            }
        }

        return $repairs;
    }

    /**
     * Import the AWB tracking timeline into order_shipments.tracking_history.
     * Append-only, deduped on timestamp+status — re-running never duplicates.
     *
     * @return int number of new events imported
     */
    private function repairTrackingTimeline(Order $order): int
    {
        $awb = (string) $order->shiprocket_awb;
        $track = $this->shipping->trackShipment($awb);
        $this->apiCalls++;
        if (empty($track['success'])) {
            return 0;
        }

        $data = (array) ($track['data'] ?? []);
        $activities = (array) ($data['shipment_track_activities'] ?? []);
        if (! $activities) {
            return 0;
        }

        $shipment = OrderShipment::firstOrCreate(
            ['order_id' => $order->id, 'tracking_number' => $awb],
            ['carrier' => $order->shiprocket_courier ?: 'Shiprocket', 'status' => 'created']
        );

        $history = is_array($shipment->tracking_history) ? $shipment->tracking_history : [];
        $seen = [];
        foreach ($history as $h) {
            $seen[($h['timestamp'] ?? '') . '|' . ($h['status'] ?? '')] = true;
        }

        $added = 0;
        foreach (array_reverse($activities) as $a) { // oldest first
            $ts = (string) ($a['date'] ?? '');
            $status = (string) ($a['sr-status-label'] ?? $a['status'] ?? '');
            $key = $ts . '|' . $status;
            if ($ts === '' || isset($seen[$key])) {
                continue;
            }
            $history[] = [
                'status' => $status,
                'location' => (string) ($a['location'] ?? ''),
                'description' => (string) ($a['activity'] ?? ''),
                'timestamp' => $ts,
            ];
            $seen[$key] = true;
            $added++;
        }

        if ($added > 0) {
            $shipment->update(['tracking_history' => $history]);
        }
        return $added;
    }

    // ─── Enum-safe mappers ──────────────────────────────────────────────────

    /** Shiprocket shipping status string → local order status (null = no change). */
    private function mapShippingStatusToOrder(string $srStatus): ?string
    {
        $s = strtoupper($srStatus);
        return match (true) {
            str_contains($s, 'RTO') => 'returned',
            str_contains($s, 'CANCEL') => 'cancelled',
            $s === 'DELIVERED' || str_contains($s, 'DELIVERED') && ! str_contains($s, 'UNDELIVERED') => 'delivered',
            str_contains($s, 'OUT FOR DELIVERY') => 'out_for_delivery',
            str_contains($s, 'IN TRANSIT'), str_contains($s, 'SHIPPED'),
            str_contains($s, 'PICKED UP'), str_contains($s, 'DISPATCH') => 'shipped',
            default => null, // NEW / INVOICED / READY TO SHIP / PICKUP SCHEDULED / OUT FOR PICKUP … — pre-ship, leave as-is
        };
    }

    /** → order_shipments.status ENUM('created','picked_up','in_transit','out_for_delivery','delivered','failed'). */
    private function mapShipmentStatus(string $srStatus): string
    {
        $s = strtoupper($srStatus);
        return match (true) {
            str_contains($s, 'RTO'), str_contains($s, 'CANCEL'), str_contains($s, 'UNDELIVERED'), str_contains($s, 'LOST') => 'failed',
            str_contains($s, 'DELIVERED') => 'delivered',
            str_contains($s, 'OUT FOR DELIVERY') => 'out_for_delivery',
            str_contains($s, 'IN TRANSIT'), str_contains($s, 'SHIPPED'), str_contains($s, 'DISPATCH') => 'in_transit',
            str_contains($s, 'PICKED UP'), str_contains($s, 'PICKUP COMPLETE') => 'picked_up',
            default => 'created',
        };
    }

    private function shipmentStatusRank(string $status): int
    {
        return ['created' => 0, 'picked_up' => 1, 'in_transit' => 2, 'out_for_delivery' => 3, 'delivered' => 4, 'failed' => 4][$status] ?? 0;
    }

    /** → payments.status ENUM('pending','authorized','captured','failed','refunded'). */
    private function mapPaymentStatus(string $srStatus): string
    {
        $s = strtolower($srStatus);
        return match (true) {
            str_contains($s, 'success'), str_contains($s, 'captur'), str_contains($s, 'paid') => 'captured',
            str_contains($s, 'refund') => 'refunded',
            str_contains($s, 'fail'), str_contains($s, 'cancel') => 'failed',
            str_contains($s, 'authoriz') => 'authorized',
            default => 'pending',
        };
    }

    /** → payments.method ENUM('card','upi','netbanking','wallet','cod','emi','bnpl'). */
    private function mapPaymentMethod(string $srMethod): string
    {
        $s = strtolower($srMethod);
        return match (true) {
            str_contains($s, 'upi') => 'upi',
            str_contains($s, 'card'), str_contains($s, 'credit'), str_contains($s, 'debit') => 'card',
            str_contains($s, 'net') => 'netbanking',
            str_contains($s, 'wallet') => 'wallet',
            str_contains($s, 'cod'), str_contains($s, 'cash') => 'cod',
            str_contains($s, 'emi') => 'emi',
            str_contains($s, 'bnpl'), str_contains($s, 'later') => 'bnpl',
            default => 'upi',
        };
    }
}
