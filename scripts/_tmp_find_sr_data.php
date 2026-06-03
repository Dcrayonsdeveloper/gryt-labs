<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(\App\Models\Tenant::find('natually'));

$srIds = ['6a0040c5af3b592749f5a100', '6a003b1d9132d21c48c5e615'];

foreach ($srIds as $srId) {
    echo "=== SR ID: {$srId} ===\n";

    // Check events
    $events = \App\Models\ShiprocketCheckoutEvent::where('cart_id', $srId)->get();
    echo "Events: " . $events->count() . "\n";

    // Check abandoned checkouts
    $acs = \App\Models\AbandonedCheckout::where('shiprocket_order_id', $srId)
        ->orWhere(function($q) use ($srId) {
            $q->where('source', 'shiprocket_checkout')
              ->whereJsonContains('metadata->shiprocket_cart_id', $srId);
        })->get();
    echo "Abandoned checkouts: " . $acs->count() . "\n";
    foreach ($acs as $ac) {
        echo "  id={$ac->id} name=" . ($ac->name ?: '(empty)') . " phone=" . ($ac->phone ?: '(empty)') . " email=" . ($ac->email ?: '(empty)') . "\n";
        $meta = $ac->metadata ?? [];
        // Check all webhook data
        foreach ($meta as $k => $v) {
            if (str_starts_with($k, 'webhook_') && is_array($v) && !empty($v['customer'])) {
                echo "  {$k} customer: " . json_encode($v['customer']) . "\n";
            }
        }
    }

    // Check the order itself
    $order = \App\Models\Order::where('shiprocket_order_id', $srId)->first();
    if ($order) {
        echo "Order: {$order->order_number} meta=" . json_encode($order->metadata) . "\n";
    }

    echo "\n";
}
