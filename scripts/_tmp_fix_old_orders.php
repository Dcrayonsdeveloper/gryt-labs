<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('natually'));

// The SUCCESS events with full customer data
// #NAT5152 → cart_id 6a003b59a95d3612c158f08d (SUCCESS at 13:32:00)
// #NAT5153 → cart_id 6a0040d52d135e61ddc14165 (SUCCESS at 13:55:34)

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

    // Get SUCCESS or ORDER_PLACED event with full data
    $event = App\Models\ShiprocketCheckoutEvent::where('cart_id', $eventCartId)
        ->whereIn('stage', ['SUCCESS', 'ORDER_PLACED', 'PAYMENT_INITIATED'])
        ->whereNotNull('full_name')
        ->where('full_name', '!=', '')
        ->orderByDesc('received_at')
        ->first();

    if (!$event) {
        echo "SKIP {$orderNum} - no event with name found for cart {$eventCartId}\n";
        continue;
    }

    echo "Event found: cart={$event->cart_id} stage={$event->stage} name={$event->full_name} phone={$event->phone}\n";

    // Get address from raw_payload
    $payload = $event->raw_payload['payload'] ?? $event->raw_payload ?? [];
    $address = $payload['shipping_address'] ?? $payload['billing_address'] ?? [];

    echo "  Address from payload: " . json_encode($address) . "\n";

    // Also check address fields directly on event
    echo "  Event address: {$event->address_line_1}, {$event->address_line_2}, {$event->city}, {$event->state}, {$event->pincode}\n";

    // Build update data
    $update = [];
    if ($event->full_name) $update['guest_name'] = $event->full_name;
    if ($event->phone) $update['guest_phone'] = $event->phone;
    if ($event->email) $update['guest_email'] = $event->email;

    // Try address from event fields first, then payload
    $addr1 = $event->address_line_1;
    $addr2 = $event->address_line_2;
    $city = $event->city;
    $state = $event->state;
    $pincode = $event->pincode;

    if (empty($addr1) && !empty($address)) {
        $addr1 = $address['address'] ?? $address['address_1'] ?? $address['line1'] ?? '';
        $addr2 = $address['address_2'] ?? $address['line2'] ?? '';
        $city = $address['city'] ?? '';
        $state = $address['state'] ?? $address['province'] ?? '';
        $pincode = $address['pincode'] ?? $address['zip'] ?? '';
    }

    if ($addr1) {
        $fullAddr = $addr1;
        if ($addr2) $fullAddr .= ', ' . $addr2;
        if ($city) $fullAddr .= ', ' . $city;
        if ($state) $fullAddr .= ', ' . $state;
        if ($pincode) $fullAddr .= ' - ' . $pincode;
        $update['shipping_address'] = $fullAddr;
        $update['billing_address'] = $fullAddr;
    }

    if (empty($update)) {
        echo "SKIP {$orderNum} - no data to update\n";
        continue;
    }

    echo "  Updating {$orderNum} with: " . json_encode($update) . "\n";
    $order->update($update);
    echo "  DONE\n\n";
}

// Verify
echo "\n=== Verification ===\n";
foreach (['#NAT5152', '#NAT5153'] as $num) {
    $o = App\Models\Order::where('order_number', $num)->first();
    echo "{$num}: name={$o->guest_name} | phone={$o->guest_phone} | email={$o->guest_email} | addr={$o->shipping_address}\n";
}
