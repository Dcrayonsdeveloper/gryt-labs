<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('natually'));

// Check recent Shiprocket checkout events (last 2 hours)
echo "=== Recent Shiprocket Checkout Events ===\n";
$events = App\Models\ShiprocketCheckoutEvent::where('received_at', '>=', now()->subHours(4))
    ->orderByDesc('received_at')
    ->limit(30)
    ->get();

foreach ($events as $e) {
    $payload = $e->raw_payload['payload'] ?? $e->raw_payload ?? [];
    $paymentMethod = $payload['payment_method'] ?? $payload['payment_type'] ?? '(none)';
    echo "{$e->received_at} | {$e->stage} | cart={$e->cart_id} | name={$e->full_name} | phone={$e->phone} | payment={$paymentMethod}\n";
}

echo "\nTotal events in last 4h: " . $events->count() . "\n";

// Check the most recent failed/incomplete ones
echo "\n=== Events with PAYMENT_INITIATED but no SUCCESS (potential PhonePe failures) ===\n";
$initiated = App\Models\ShiprocketCheckoutEvent::where('received_at', '>=', now()->subHours(4))
    ->where('stage', 'PAYMENT_INITIATED')
    ->get();

foreach ($initiated as $pi) {
    $hasSuccess = App\Models\ShiprocketCheckoutEvent::where('cart_id', $pi->cart_id)
        ->whereIn('stage', ['SUCCESS', 'ORDER_PLACED', 'Payment Complete'])
        ->exists();
    if (!$hasSuccess) {
        $payload = $pi->raw_payload['payload'] ?? $pi->raw_payload ?? [];
        $paymentMethod = $payload['payment_method'] ?? $payload['payment_type'] ?? '(none)';
        echo "  INCOMPLETE: cart={$pi->cart_id} | {$pi->full_name} | {$pi->phone} | payment={$paymentMethod} | {$pi->received_at}\n";
    }
}
