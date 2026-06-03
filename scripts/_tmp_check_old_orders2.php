<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('natually'));

// Check abandoned checkouts for these orders - full data
$ids = ['6a003b1d9132d21c48c5e615', '6a0040c5af3b592749f5a100'];
foreach ($ids as $srId) {
    $ac = App\Models\AbandonedCheckout::where('shiprocket_order_id', $srId)->first();
    if ($ac) {
        echo "AC for {$srId}:\n";
        echo "  id={$ac->id} name={$ac->name} phone={$ac->phone} email={$ac->email}\n";
        echo "  cart_snapshot=" . json_encode($ac->cart_snapshot) . "\n";
        echo "  metadata=" . json_encode($ac->metadata) . "\n";
        echo "  source={$ac->source} step={$ac->step}\n\n";
    }
}

// Check shiprocket_checkout_ids table
$checkoutIds = DB::table('shiprocket_checkout_ids')->whereIn('shiprocket_id', $ids)->get();
echo "Checkout ID mappings: " . $checkoutIds->count() . "\n";
foreach ($checkoutIds as $row) {
    echo "  sr_id={$row->shiprocket_id} | ac_id=" . ($row->abandoned_checkout_id ?? 'null') . " | source=" . ($row->source ?? '') . "\n";
}

// Check all recent webhook events around that time (May 10, 1-2pm)
echo "\nAll webhook events on May 10 between 13:00-14:00:\n";
$events = App\Models\ShiprocketCheckoutEvent::whereBetween('received_at', ['2026-05-10 13:00:00', '2026-05-10 14:30:00'])
    ->orderBy('received_at')
    ->get();
foreach ($events as $e) {
    echo "  cart_id={$e->cart_id} | stage={$e->stage} | name={$e->full_name} | phone={$e->phone} | time={$e->received_at}\n";
}

// Check order items for product info
echo "\nOrder items:\n";
foreach (['#NAT5152', '#NAT5153'] as $num) {
    $order = App\Models\Order::where('order_number', $num)->first();
    if ($order) {
        $items = App\Models\OrderItem::where('order_id', $order->id)->get();
        echo "{$num} items: " . $items->count() . "\n";
        foreach ($items as $item) {
            $product = App\Models\Product::find($item->product_id);
            $title = $product ? $product->title : 'unknown';
            echo "  product={$item->product_id} ({$title}) qty={$item->quantity} price={$item->price}\n";
        }
    }
}
