<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('natually'));

$service = new App\Services\BlueDartService();
echo "isConfigured: " . ($service->isConfigured() ? 'YES' : 'NO') . "\n";

// Test pincode serviceability first
echo "\n=== Test Pincode Serviceability ===\n";
$pin = $service->checkPincode('110055');
echo "110055 (Delhi): " . json_encode($pin) . "\n";

$pin2 = $service->checkPincode('500032');
echo "500032 (Hyderabad): " . json_encode($pin2) . "\n";

// Test shipping cost calculation
echo "\n=== Test Shipping Cost ===\n";
$cost = $service->calculateCost('110055', 500, 'Pre-paid');
echo "533101 → 110055 (500g prepaid): " . json_encode($cost) . "\n";

// Test with a recent order (dry booking)
echo "\n=== Test Booking on Recent Order ===\n";
$order = App\Models\Order::with('items.product')
    ->whereNotNull('shipping_address_snapshot')
    ->where('status', '!=', 'cancelled')
    ->latest()
    ->first();

if ($order) {
    echo "Order: {$order->order_number} | {$order->guest_name} | Total: {$order->total}\n";
    $result = $service->bookDelivery($order);
    echo "Booking result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "No eligible order found\n";
}
