<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(\App\Models\Tenant::find('natually'));

$dupeIds = [5176, 5177, 5183, 5182, 5179, 5180, 5186, 5184, 5185];

$deleted = 0;
foreach ($dupeIds as $id) {
    $order = \App\Models\Order::find($id);
    if (!$order) {
        echo "SKIP id={$id} — not found\n";
        continue;
    }

    // Revert sales_count increments
    $items = \App\Models\OrderItem::where('order_id', $id)->get();
    foreach ($items as $item) {
        if ($item->product_id) {
            $product = \App\Models\Product::find($item->product_id);
            if ($product && $product->sales_count >= $item->quantity) {
                $product->decrement('sales_count', $item->quantity);
            }
        }
    }

    // Delete order items
    \App\Models\OrderItem::where('order_id', $id)->delete();

    // Unlink from abandoned checkout if linked
    \App\Models\AbandonedCheckout::where('order_id', $id)->update([
        'order_id' => null,
        'recovered' => false,
        'recovered_at' => null,
    ]);

    // Delete the order
    $order->delete();
    $deleted++;
    echo "DELETED {$order->order_number} (id={$id}) | {$order->guest_name} | ₹{$order->total}\n";
}

echo "\nDeleted: {$deleted}\n";
echo "Total orders now: " . \App\Models\Order::whereNotNull('shiprocket_order_id')->count() . "\n";
