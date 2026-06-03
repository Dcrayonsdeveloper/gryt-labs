<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(\App\Models\Tenant::find('natually'));

// Get all unique cart_ids from webhook events that had successful payment
$successEvents = \App\Models\ShiprocketCheckoutEvent::where('is_duplicate', false)
    ->whereIn('stage', ['SUCCESS', 'ORDER_PLACED', 'Payment Complete'])
    ->get();

echo "=== Shiprocket Checkout events with payment confirmation ===\n";
echo "Total payment-confirmed events: " . $successEvents->count() . "\n\n";

// Get all orders with shiprocket_order_id
$orders = \App\Models\Order::whereNotNull('shiprocket_order_id')->pluck('shiprocket_order_id')->toArray();
echo "Orders in DB with SR ID: " . count($orders) . "\n\n";

// Find events that have no matching order
$missing = [];
foreach ($successEvents as $event) {
    $cartId = $event->cart_id;
    // Check if any order has this cart_id
    $found = \App\Models\Order::where('shiprocket_order_id', $cartId)
        ->orWhereJsonContains('metadata->shiprocket_cart_id', $cartId)
        ->orWhereJsonContains('metadata->shiprocket_checkout_id', $cartId)
        ->exists();

    // Also check via bridge -> abandoned checkout -> order
    if (!$found) {
        $ac = \App\Models\AbandonedCheckout::where('shiprocket_order_id', $cartId)
            ->orWhere(function($q) use ($cartId) {
                $q->where('source', 'shiprocket_checkout')
                  ->whereJsonContains('metadata->shiprocket_cart_id', $cartId);
            })->first();
        if ($ac && $ac->order_id) {
            $found = true;
        }
    }

    if (!$found) {
        $payload = $event->raw_payload['payload'] ?? $event->raw_payload ?? [];
        $missing[] = [
            'cart_id' => $cartId,
            'stage' => $event->stage,
            'phone' => $event->phone ?? $payload['phone'] ?? '(unknown)',
            'name' => $event->full_name ?? '',
            'total' => $event->total_price ?? $payload['total_amount_payable'] ?? 0,
            'date' => $event->received_at,
            'payment_type' => $payload['payment_type'] ?? '(unknown)',
        ];
    }
}

echo "=== MISSING ORDERS (in Shiprocket but NOT in website) ===\n";
echo "Count: " . count($missing) . "\n\n";
foreach ($missing as $m) {
    echo "  cart_id={$m['cart_id']}\n";
    echo "    name={$m['name']} phone={$m['phone']} total=₹{$m['total']}\n";
    echo "    stage={$m['stage']} payment={$m['payment_type']} date={$m['date']}\n\n";
}

// Also check abandoned checkouts that were recovered but have no order
$recoveredNoOrder = \App\Models\AbandonedCheckout::where('source', 'shiprocket_checkout')
    ->where('recovered', true)
    ->whereNull('order_id')
    ->get();
if ($recoveredNoOrder->isNotEmpty()) {
    echo "=== Abandoned checkouts marked recovered but NO order ===\n";
    foreach ($recoveredNoOrder as $ac) {
        echo "  AC#{$ac->id} sr_id={$ac->shiprocket_order_id} name=" . ($ac->name ?: '(empty)') . " phone=" . ($ac->phone ?: '(empty)') . "\n";
    }
}
