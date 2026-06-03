<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(\App\Models\Tenant::find('natually'));

// Get all payment-confirmed events, deduplicated by cart_id
$events = \App\Models\ShiprocketCheckoutEvent::where('is_duplicate', false)
    ->whereIn('stage', ['SUCCESS', 'ORDER_PLACED', 'Payment Complete'])
    ->orderByDesc('received_at')
    ->get()
    ->groupBy('cart_id');

$allOrders = \App\Models\Order::all();
$allAcs = \App\Models\AbandonedCheckout::where('source', 'shiprocket_checkout')->get();

$missing = [];
$found = [];

foreach ($events as $cartId => $group) {
    $best = $group->first(fn ($e) => $e->stage === 'SUCCESS') ?? $group->first();
    $payload = $best->raw_payload['payload'] ?? $best->raw_payload ?? [];
    $platformOrderId = $payload['platform_order_id'] ?? $payload['order_id'] ?? null;
    $fastrOrderId = $payload['fastrr_order_id'] ?? null;

    // Search by ALL possible IDs
    $order = $allOrders->first(function ($o) use ($cartId, $platformOrderId, $fastrOrderId) {
        if ($o->shiprocket_order_id === $cartId) return true;
        if ($platformOrderId && $o->shiprocket_order_id === $platformOrderId) return true;
        $meta = $o->metadata ?? [];
        if (($meta['shiprocket_cart_id'] ?? '') === $cartId) return true;
        if (($meta['shiprocket_checkout_id'] ?? '') === $cartId) return true;
        if ($platformOrderId && ($meta['shiprocket_checkout_id'] ?? '') === $platformOrderId) return true;
        if ($fastrOrderId && ($meta['fastrr_order_id'] ?? '') === (string)$fastrOrderId) return true;
        return false;
    });

    // Also check via abandoned checkout -> order_id
    if (!$order) {
        $ac = $allAcs->first(function ($ac) use ($cartId, $platformOrderId) {
            if ($ac->shiprocket_order_id === $cartId) return true;
            if ($platformOrderId && $ac->shiprocket_order_id === $platformOrderId) return true;
            $meta = $ac->metadata ?? [];
            if (($meta['shiprocket_cart_id'] ?? '') === $cartId) return true;
            return false;
        });
        if ($ac && $ac->order_id) {
            $order = $allOrders->firstWhere('id', $ac->order_id);
        }
    }

    if ($order) {
        $found[] = $cartId;
    } else {
        $missing[] = [
            'cart_id' => $cartId,
            'platform_order_id' => $platformOrderId,
            'name' => $best->full_name ?: '(unknown)',
            'phone' => $best->phone ?: '(unknown)',
            'email' => $best->email ?: '',
            'total' => $payload['total_amount_payable'] ?? $best->total_price ?? 0,
            'subtotal' => $payload['subtotal_price'] ?? 0,
            'payment_type' => $payload['payment_type'] ?? '(unknown)',
            'payment_status' => $payload['payment_status'] ?? '(unknown)',
            'stage' => $best->stage,
            'date' => $best->received_at?->format('Y-m-d H:i'),
        ];
    }
}

echo "=== Natually: Shiprocket vs Website Order Match ===\n";
echo "Total payment events (unique cart_ids): " . $events->count() . "\n";
echo "Matched to orders: " . count($found) . "\n";
echo "MISSING from website: " . count($missing) . "\n\n";

if (!empty($missing)) {
    echo "=== MISSING ORDERS DETAIL ===\n";
    foreach ($missing as $i => $m) {
        $n = $i + 1;
        echo "{$n}. {$m['name']} | {$m['phone']} | ₹{$m['total']} | {$m['payment_type']} | {$m['payment_status']} | {$m['date']}\n";
        echo "   cart_id={$m['cart_id']} platform_id={$m['platform_order_id']}\n";
    }
}
