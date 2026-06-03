<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(\App\Models\Tenant::find('natually'));

// Show recovered orders with their timestamps
$orders = \App\Models\Order::whereIn('order_number', [
    '#NAT5159','#NAT5160','#NAT5161','#NAT5162','#NAT5163','#NAT5164',
    '#NAT5165','#NAT5166','#NAT5167','#NAT5168','#NAT5169','#NAT5170',
    '#NAT5171','#NAT5172','#NAT5173','#NAT5174',
])->orderBy('created_at')->get(['id','order_number','guest_name','total','created_at','updated_at']);

echo "=== Recovered orders current state ===\n";
foreach ($orders as $o) {
    echo "{$o->order_number} | {$o->guest_name} | ₹{$o->total} | created_at={$o->created_at} | updated_at={$o->updated_at}\n";
}

// Show what the correct timestamps should be from webhook events
echo "\n=== Correct timestamps from Shiprocket webhooks ===\n";
$cartIds = [
    '6a019cf39b1b683f027a2703', '69fabf00fc9ff1320ab4afd6', '69fab35d2d135e61ddac5f23',
    '69faa59d2d135e61ddac3189', '69fa9ad14bdd972433c2329e', '69fa045dfc9ff1320ab2cd74',
    '69f6eca04bdd972433b5d1b9', '69f6a3a32d135e61dd9e9088', '69f5d7709b1b683f024ffce6',
    '69f586d34bdd972433b0e1c4', '69f583750e54003a6268504b', '69f39e204bdd972433aad794',
    '69f31d6a4bdd972433a8a473', '69f2ea5e2d135e61dd919ae7', '69f262422d135e61dd90988e',
    '69f20c4f0fc2b008eab23149',
];

foreach ($cartIds as $cid) {
    $event = \App\Models\ShiprocketCheckoutEvent::where('cart_id', $cid)
        ->where('is_duplicate', false)
        ->whereIn('stage', ['SUCCESS', 'ORDER_PLACED'])
        ->first();
    if (!$event) continue;

    $payload = $event->raw_payload['payload'] ?? $event->raw_payload ?? [];
    $srDate = $payload['order_created_date'] ?? null;

    // Find matching order
    $order = \App\Models\Order::where('shiprocket_order_id', $cid)
        ->orWhereJsonContains('metadata->shiprocket_cart_id', $cid)
        ->orWhereJsonContains('metadata->shiprocket_checkout_id', $payload['platform_order_id'] ?? 'x')
        ->first();

    $orderNum = $order ? $order->order_number : '(not found)';
    echo "cart={$cid} sr_date={$srDate} webhook_received={$event->received_at} order={$orderNum} current_created_at=" . ($order ? $order->created_at : 'N/A') . "\n";
}
