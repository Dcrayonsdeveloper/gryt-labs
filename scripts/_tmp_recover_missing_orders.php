<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(\App\Models\Tenant::find('natually'));

$missingCartIds = [
    '6a019cf39b1b683f027a2703',
    '69fabf00fc9ff1320ab4afd6',
    '69fab35d2d135e61ddac5f23',
    '69faa59d2d135e61ddac3189',
    '69fa9ad14bdd972433c2329e',
    '69fa045dfc9ff1320ab2cd74',
    '69f6eca04bdd972433b5d1b9',
    '69f6a3a32d135e61dd9e9088',
    '69f5d7709b1b683f024ffce6',
    '69f586d34bdd972433b0e1c4',
    '69f583750e54003a6268504b',
    '69f39e204bdd972433aad794',
    '69f31d6a4bdd972433a8a473',
    '69f2ea5e2d135e61dd919ae7',
    '69f262422d135e61dd90988e',
    '69f20c4f0fc2b008eab23149',
];

$created = 0;
$skipped = 0;
$failed = 0;

foreach ($missingCartIds as $cartId) {
    // Get the best event — prefer SUCCESS, then ORDER_PLACED
    $successEvent = \App\Models\ShiprocketCheckoutEvent::where('cart_id', $cartId)
        ->where('is_duplicate', false)
        ->where('stage', 'SUCCESS')
        ->first();
    $orderPlacedEvent = \App\Models\ShiprocketCheckoutEvent::where('cart_id', $cartId)
        ->where('is_duplicate', false)
        ->where('stage', 'ORDER_PLACED')
        ->first();

    $event = $successEvent ?? $orderPlacedEvent;
    if (!$event) {
        echo "SKIP {$cartId} — no event data\n";
        $skipped++;
        continue;
    }

    // Check if order already exists
    $exists = \App\Models\Order::where('shiprocket_order_id', $cartId)->exists();
    if ($exists) {
        echo "SKIP {$cartId} — order already exists\n";
        $skipped++;
        continue;
    }

    $payload = $event->raw_payload['payload'] ?? $event->raw_payload ?? [];
    $platformOrderId = $payload['platform_order_id'] ?? $cartId;

    // Also check by platform_order_id
    if ($platformOrderId !== $cartId) {
        $exists2 = \App\Models\Order::where('shiprocket_order_id', $platformOrderId)->exists();
        if ($exists2) {
            echo "SKIP {$cartId} — order exists by platform_id {$platformOrderId}\n";
            $skipped++;
            continue;
        }
    }

    // Extract customer data
    $shipping = $payload['shipping_address'] ?? [];
    $name = $event->full_name
        ?: trim(($shipping['first_name'] ?? '') . ' ' . ($shipping['last_name'] ?? ''))
        ?: 'Guest';
    $phone = $event->phone ?? $payload['phone'] ?? $payload['phone_number'] ?? $shipping['phone'] ?? '';
    $email = $event->email ?? $payload['email'] ?? $shipping['email'] ?? '';

    // Extract items from event or payload
    $items = $event->items;
    if (empty($items)) {
        $items = $payload['cart_data']['items'] ?? $payload['items'] ?? [];
        $items = array_map(fn($i) => [
            'product_id' => $i['product_id'] ?? $i['variant_id'] ?? null,
            'variant_id' => $i['variant_id'] ?? null,
            'quantity' => (int)($i['quantity'] ?? 1),
            'price' => (float)($i['price'] ?? 0),
            'name' => $i['name'] ?? $i['title'] ?? 'Product',
        ], $items);
    }

    if (empty($items)) {
        echo "FAIL {$cartId} ({$name}) — no items found\n";
        $failed++;
        continue;
    }

    // Calculate pricing
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += ((float)($item['price'] ?? 0)) * ((int)($item['quantity'] ?? 1));
    }

    $discount = (float)($payload['coupon_discount'] ?? $payload['total_discount'] ?? $event->total_discount ?? 0);
    $shippingCost = (float)($payload['shipping_charges'] ?? $event->shipping_price ?? 0);
    $totalPayable = (float)($payload['total_amount_payable'] ?? 0);
    $total = $totalPayable > 0 ? $totalPayable : ($subtotal - $discount + $shippingCost);

    // Payment info
    $payments = $payload['payments'] ?? [];
    $firstPayment = $payments[0] ?? [];
    $paymentType = strtolower($payload['payment_type'] ?? $event->payment_mode ?? 'prepaid');
    $isCod = in_array($paymentType, ['cod', 'cash_on_delivery', 'partial_paid']);
    $onlinePaid = (float)($firstPayment['amount'] ?? $event->payment_amount ?? 0);

    if ($paymentType === 'partial_paid' || ($isCod && $onlinePaid > 0 && $onlinePaid < $total)) {
        $paidAmount = $onlinePaid;
        $paymentStatus = 'partial';
    } elseif ($isCod && $onlinePaid <= 0) {
        $paidAmount = 0;
        $paymentStatus = 'pending';
    } else {
        $paidAmount = $total;
        $paymentStatus = 'paid';
    }

    // Determine order date from event
    $orderDate = $payload['order_created_date'] ?? null;
    $createdAt = $orderDate ? \Carbon\Carbon::parse($orderDate)->setTimezone('Asia/Kolkata') : $event->received_at;

    try {
        \Illuminate\Support\Facades\DB::transaction(function () use (
            $cartId, $platformOrderId, $name, $phone, $email, $items,
            $subtotal, $discount, $shippingCost, $total, $paidAmount, $paymentStatus,
            $paymentType, $firstPayment, $payload, $createdAt, $shipping, $event,
            &$created
        ) {
            $order = \App\Models\Order::create([
                'user_id' => null,
                'guest_name' => $name,
                'guest_email' => $email ?: null,
                'guest_phone' => preg_replace('/\D/', '', $phone),
                'status' => 'confirmed',
                'payment_status' => $paymentStatus,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_cost' => $shippingCost,
                'tax' => 0,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'source' => 'api',
                'shiprocket_order_id' => $platformOrderId ?: $cartId,
                'shipping_address_snapshot' => array_filter([
                    'name' => $name,
                    'phone' => preg_replace('/\D/', '', $phone),
                    'address_line_1' => $shipping['line1'] ?? $event->address_line_1 ?? '',
                    'address_line_2' => $shipping['line2'] ?? $event->address_line_2 ?? '',
                    'city' => $shipping['city'] ?? $event->city ?? '',
                    'state' => $shipping['state'] ?? $event->state ?? '',
                    'postal_code' => $shipping['pincode'] ?? $event->pincode ?? '',
                    'country' => $shipping['country'] ?? 'India',
                ]),
                'metadata' => array_filter([
                    'payment_method' => $paymentType,
                    'payment_gateway' => $firstPayment['gateway'] ?? $event->payment_gateway ?? 'shiprocket',
                    'transaction_id' => $firstPayment['pg_transaction_id'] ?? $firstPayment['txn_id'] ?? $event->transaction_id ?? null,
                    'shiprocket_checkout_id' => $platformOrderId,
                    'shiprocket_cart_id' => $cartId,
                    'fastrr_order_id' => $payload['fastrr_order_id'] ?? null,
                    'created_from' => 'recovery_script',
                    'coupon_codes' => $payload['coupon_codes'] ?? null,
                ]),
                'created_at' => $createdAt,
                'updated_at' => now(),
            ]);

            // Create order items + update stock
            foreach ($items as $item) {
                $product = \App\Models\Product::find($item['product_id'] ?? null)
                    ?? \App\Models\Product::find($item['variant_id'] ?? null);

                \App\Models\OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product?->id,
                    'product_name' => $product?->name ?? $item['name'] ?? 'Product',
                    'sku' => $product?->sku ?? '',
                    'quantity' => (int)($item['quantity'] ?? 1),
                    'mrp' => $product?->mrp ?? $item['price'] ?? 0,
                    'price' => (float)($item['price'] ?? 0),
                    'tax' => 0,
                    'discount' => 0,
                    'total' => ((float)($item['price'] ?? 0)) * ((int)($item['quantity'] ?? 1)),
                ]);

                if ($product) {
                    $qty = (int)($item['quantity'] ?? 1);
                    $product->increment('sales_count', $qty);
                }
            }

            // Link to user account if exists
            $cleanPhone = preg_replace('/\D/', '', $phone);
            $shortPhone = strlen($cleanPhone) > 10 ? substr($cleanPhone, -10) : $cleanPhone;
            $user = \App\Models\User::where(function ($q) use ($email, $cleanPhone, $shortPhone) {
                if ($email) $q->orWhere('email', $email);
                if ($cleanPhone) $q->orWhere('phone', $cleanPhone);
                if ($shortPhone && $shortPhone !== $cleanPhone) $q->orWhere('phone', $shortPhone);
            })->first();
            if ($user) {
                $order->update(['user_id' => $user->id]);
            }

            // Link abandoned checkout if exists
            $ac = \App\Models\AbandonedCheckout::where('shiprocket_order_id', $cartId)
                ->orWhere(function($q) use ($cartId) {
                    $q->whereJsonContains('metadata->shiprocket_cart_id', $cartId);
                })->first();
            if ($ac) {
                $ac->update([
                    'order_id' => $order->id,
                    'recovered' => true,
                    'recovered_at' => now(),
                    'step' => 'completed',
                ]);
            }

            echo "CREATED {$order->order_number} | {$name} | {$cleanPhone} | ₹{$total} ({$paymentStatus}) | {$createdAt}\n";
            $created++;
        });
    } catch (\Throwable $e) {
        echo "FAIL {$cartId} ({$name}) — " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n=== RECOVERY COMPLETE ===\n";
echo "Created: {$created}\n";
echo "Skipped: {$skipped}\n";
echo "Failed: {$failed}\n";
echo "Total orders now: " . \App\Models\Order::whereNotNull('shiprocket_order_id')->count() . "\n";
