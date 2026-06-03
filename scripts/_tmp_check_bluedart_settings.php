<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('natually'));

$keys = [
    'bluedart_enabled', 'bluedart_mode', 'bluedart_api_key', 'bluedart_api_token',
    'bluedart_client_id', 'bluedart_client_secret',
    'bluedart_login_id', 'bluedart_licence_key',
    'bluedart_customer_code', 'bluedart_origin_area', 'bluedart_origin_pin',
    'bluedart_pickup_location', 'bluedart_return_address', 'bluedart_return_phone',
    'bluedart_contact_person', 'bluedart_product_type', 'bluedart_sub_product_type',
];

echo "=== Natually BlueDart Settings ===\n";
$missing = [];
foreach ($keys as $k) {
    $val = App\Models\Setting::get($k, '');
    if (!$val) {
        echo "  {$k} = ** EMPTY **\n";
        $missing[] = $k;
    } elseif (strlen($val) > 15) {
        echo "  {$k} = " . substr($val, 0, 8) . "... (" . strlen($val) . " chars)\n";
    } else {
        echo "  {$k} = {$val}\n";
    }
}

echo "\n=== Missing Required Settings ===\n";
$required = ['bluedart_client_id','bluedart_client_secret','bluedart_login_id','bluedart_licence_key',
             'bluedart_customer_code','bluedart_origin_area','bluedart_origin_pin',
             'bluedart_return_address','bluedart_return_phone'];
foreach ($required as $r) {
    if (in_array($r, $missing)) {
        echo "  MISSING: {$r}\n";
    }
}

// Test a booking dry-run with debug logging
echo "\n=== Test Booking (dry-run with debug) ===\n";
$service = new App\Services\BlueDartService();
echo "isConfigured: " . ($service->isConfigured() ? 'YES' : 'NO') . "\n";

// Get a recent order to test with
$order = App\Models\Order::whereNotNull('shipping_address_snapshot')
    ->where('status', '!=', 'cancelled')
    ->latest()
    ->first();

if ($order) {
    echo "Test order: {$order->order_number} | {$order->guest_name}\n";
    $snapshot = $order->shipping_address_snapshot;
    echo "Address: " . ($snapshot['address_line_1'] ?? $snapshot['address'] ?? 'none') . ", " . ($snapshot['city'] ?? '') . " " . ($snapshot['postal_code'] ?? $snapshot['zip'] ?? '') . "\n";

    // Don't actually book, just show what would be sent
    echo "\nWould send to BlueDart:\n";
    echo "  Consignee Pin: " . ($snapshot['postal_code'] ?? $snapshot['zip'] ?? 'MISSING') . "\n";
    echo "  Origin Pin: " . App\Models\Setting::get('bluedart_origin_pin', 'MISSING') . "\n";
    echo "  Customer Code: " . App\Models\Setting::get('bluedart_customer_code', 'MISSING') . "\n";
    echo "  Origin Area: " . App\Models\Setting::get('bluedart_origin_area', 'MISSING') . "\n";
    echo "  Return Address: " . (App\Models\Setting::get('bluedart_return_address', '') ?: 'MISSING') . "\n";
    echo "  Return Phone: " . (App\Models\Setting::get('bluedart_return_phone', '') ?: 'MISSING') . "\n";
} else {
    echo "No orders with shipping address found\n";
}
