<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

foreach (['natually', 'ayurvexa'] as $tid) {
    $t = \App\Models\Tenant::find($tid);
    if (!$t) continue;
    tenancy()->initialize($t);

    // Count orders missing customer details
    $missing = \App\Models\Order::whereNotNull('shiprocket_order_id')
        ->whereNull('user_id')
        ->where(function ($q) {
            $q->whereNull('guest_name')->orWhere('guest_name', '');
        })
        ->count();

    // Count total shiprocket orders
    $total = \App\Models\Order::whereNotNull('shiprocket_order_id')->count();

    // Count webhook events stored
    $events = \App\Models\ShiprocketCheckoutEvent::count();

    // Recent orders with missing data
    $recentMissing = \App\Models\Order::whereNotNull('shiprocket_order_id')
        ->whereNull('user_id')
        ->where(function ($q) {
            $q->whereNull('guest_name')->orWhere('guest_name', '');
        })
        ->orderByDesc('created_at')
        ->limit(5)
        ->get(['id', 'order_number', 'shiprocket_order_id', 'guest_name', 'guest_phone', 'created_at']);

    echo "=== {$tid} ===\n";
    echo "Total SR orders: {$total}\n";
    echo "Missing customer data: {$missing}\n";
    echo "Webhook events stored: {$events}\n";

    if ($recentMissing->isNotEmpty()) {
        echo "Recent orders missing data:\n";
        foreach ($recentMissing as $o) {
            echo "  #{$o->order_number} (id={$o->id}) sr_id={$o->shiprocket_order_id} name=" . ($o->guest_name ?: '(empty)') . " phone=" . ($o->guest_phone ?: '(empty)') . " created={$o->created_at}\n";
        }
    }
    echo "\n";
}
