<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(\App\Models\Tenant::find('natually'));

$cartIds = [
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

$fixed = 0;
foreach ($cartIds as $cid) {
    // Get the correct timestamp from webhook event
    $event = \App\Models\ShiprocketCheckoutEvent::where('cart_id', $cid)
        ->where('is_duplicate', false)
        ->whereIn('stage', ['SUCCESS', 'ORDER_PLACED'])
        ->first();
    if (!$event) continue;

    $payload = $event->raw_payload['payload'] ?? $event->raw_payload ?? [];
    $platformOrderId = $payload['platform_order_id'] ?? null;

    // The correct date: prefer Shiprocket's order_created_date, fallback to webhook received_at
    $srDate = $payload['order_created_date'] ?? null;
    $correctDate = $srDate
        ? \Carbon\Carbon::parse($srDate)->setTimezone('Asia/Kolkata')
        : $event->received_at;

    // Find the order
    $order = \App\Models\Order::where('shiprocket_order_id', $cid)->first();
    if (!$order && $platformOrderId) {
        $order = \App\Models\Order::where('shiprocket_order_id', $platformOrderId)->first();
    }
    if (!$order) {
        $order = \App\Models\Order::whereJsonContains('metadata->shiprocket_cart_id', $cid)->first();
    }

    if (!$order) {
        echo "SKIP {$cid} — order not found\n";
        continue;
    }

    // Update created_at directly via DB to bypass Eloquent timestamp protection
    \Illuminate\Support\Facades\DB::table('orders')
        ->where('id', $order->id)
        ->update(['created_at' => $correctDate]);

    $fixed++;
    echo "FIXED {$order->order_number} | {$order->guest_name} | created_at → {$correctDate}\n";
}

echo "\nFixed: {$fixed}\n";

// Verify final state
echo "\n=== All Natually orders sorted by date ===\n";
$all = \App\Models\Order::whereNotNull('shiprocket_order_id')
    ->orderBy('created_at')
    ->get(['order_number', 'guest_name', 'total', 'created_at']);
foreach ($all as $o) {
    echo "{$o->order_number} | {$o->guest_name} | ₹{$o->total} | {$o->created_at}\n";
}
