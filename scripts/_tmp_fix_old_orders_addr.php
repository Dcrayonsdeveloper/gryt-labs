<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('natually'));

$mapping = [
    '#NAT5152' => '6a003b59a95d3612c158f08d',
    '#NAT5153' => '6a0040d52d135e61ddc14165',
];

foreach ($mapping as $orderNum => $eventCartId) {
    $order = App\Models\Order::where('order_number', $orderNum)->first();
    if (!$order) {
        echo "SKIP {$orderNum} - not found\n";
        continue;
    }

    $event = App\Models\ShiprocketCheckoutEvent::where('cart_id', $eventCartId)
        ->whereIn('stage', ['SUCCESS', 'ORDER_PLACED'])
        ->whereNotNull('full_name')
        ->where('full_name', '!=', '')
        ->first();

    if (!$event) {
        echo "SKIP {$orderNum} - no event\n";
        continue;
    }

    $payload = $event->raw_payload['payload'] ?? $event->raw_payload ?? [];
    $addr = $payload['shipping_address'] ?? $payload['billing_address'] ?? [];

    $snapshot = [
        'name'         => $addr['name'] ?? $event->full_name,
        'first_name'   => $addr['first_name'] ?? $event->first_name,
        'last_name'    => $addr['last_name'] ?? $event->last_name,
        'phone'        => $addr['phone'] ?? $event->phone,
        'address'      => $addr['address1'] ?? $event->address_line_1,
        'address_line_1' => $addr['address1'] ?? $event->address_line_1,
        'address_2'    => $addr['address2'] ?? $event->address_line_2,
        'city'         => $addr['city'] ?? $event->city,
        'state'        => $addr['state'] ?? $event->state,
        'zip'          => $addr['zip'] ?? $event->pincode,
        'postal_code'  => $addr['zip'] ?? $event->pincode,
        'country'      => $addr['country'] ?? $event->country ?? 'India',
    ];

    echo "{$orderNum} snapshot: " . json_encode($snapshot, JSON_PRETTY_PRINT) . "\n";

    $order->shipping_address_snapshot = $snapshot;
    $order->save();
    echo "SAVED {$orderNum}\n\n";
}

// Verify
echo "=== Verification ===\n";
foreach (['#NAT5152', '#NAT5153'] as $num) {
    $o = App\Models\Order::where('order_number', $num)->first();
    echo "{$num}: name={$o->guest_name} | phone={$o->guest_phone}\n";
    echo "  snapshot=" . json_encode($o->shipping_address_snapshot) . "\n";
}
