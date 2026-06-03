<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('natually'));

// Check the 2 old orders
$orders = App\Models\Order::whereIn('order_number', ['#NAT5152','#NAT5153'])->get();
foreach ($orders as $o) {
    echo "ORDER: {$o->order_number}\n";
    echo "  name:  {$o->guest_name}\n";
    echo "  phone: {$o->guest_phone}\n";
    echo "  email: {$o->guest_email}\n";
    echo "  addr:  {$o->shipping_address}\n";
    echo "  sr_id: {$o->shiprocket_order_id}\n";
    echo "  meta:  " . json_encode($o->metadata) . "\n";
    echo "  created: {$o->created_at}\n\n";
}

// Check if any webhook events exist for these cart IDs
foreach ($orders as $o) {
    $events = App\Models\ShiprocketCheckoutEvent::where('cart_id', $o->shiprocket_order_id)->get();
    echo "Events for {$o->order_number} (cart_id={$o->shiprocket_order_id}): " . $events->count() . "\n";
    foreach ($events as $e) {
        echo "  stage={$e->stage} | name={$e->full_name} | phone={$e->phone} | date={$e->received_at}\n";
    }
}

// Check if Shiprocket Shipping API credentials exist
$email = App\Models\Setting::get('shiprocket_email', '');
$pass = App\Models\Setting::get('shiprocket_password', '');
echo "\nShiprocket Shipping API creds: email=" . ($email ? 'SET' : 'EMPTY') . " password=" . ($pass ? 'SET' : 'EMPTY') . "\n";

// Check abandoned checkouts for these
foreach ($orders as $o) {
    $acs = App\Models\AbandonedCheckout::where('shiprocket_order_id', $o->shiprocket_order_id)->get();
    echo "AbandonedCheckouts for {$o->order_number}: " . $acs->count() . "\n";
    foreach ($acs as $ac) {
        echo "  name={$ac->name} | phone={$ac->phone} | email={$ac->email}\n";
    }
}
