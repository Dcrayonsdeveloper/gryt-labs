<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tenant = App\Models\Tenant::find('natually');
$tenant->run(function () {
    $order = App\Models\Order::create([
        'guest_name'    => 'Sreelatha M',
        'guest_phone'   => '9491662727',
        'guest_email'   => 'metikalasreelatha2016@gmail.com',
        'subtotal'      => 279.00,
        'discount'      => 0,
        'tax'           => 0,
        'shipping_cost' => 0,
        'total'         => 279.00,
        'paid_amount'   => 99.00,
        'payment_status' => 'partial',
        'status'        => 'confirmed',
        'confirmed_at'  => '2026-05-06 12:06:07',
        'source'        => 'api',
        'shiprocket_order_id' => '69fb2e479b1b683f026255e5',
        'currency'      => 'INR',
        'shipping_address_snapshot' => [
            'name'           => 'Sreelatha M',
            'phone'          => '9491662727',
            'address_line_1' => 'House no 25, Jaya sai villas, Aditya nagar, Ulchala road',
            'address_line_2' => 'Ulchala road',
            'city'           => 'Kurnool',
            'state'          => 'Andhra Pradesh',
            'postal_code'    => '518003',
            'country'        => 'India',
        ],
        'metadata' => [
            'payment_method'        => 'UPI',
            'shiprocket_cart_id'    => '69fb2e479b1b683f026255e5',
            'cod_advance_amount'    => 99,
            'created_retroactively' => true,
            'original_order_time'   => '2026-05-06T17:36:07+05:30',
        ],
    ]);

    // Set created_at to original order time (5:36 PM IST = 12:06 UTC)
    $order->update(['created_at' => '2026-05-06 12:06:07']);

    // Create order item
    $order->items()->create([
        'product_id'   => 352,
        'product_name' => 'White Chocolate Wax for Smooth, Gentle Hair Removal and Soft Silky Skin',
        'sku'          => '4',
        'mrp'          => 315.00,
        'price'        => 279.00,
        'quantity'     => 1,
        'tax'          => 0,
        'discount'     => 0,
        'total'        => 279.00,
    ]);

    // Mark abandoned checkout as recovered
    Illuminate\Support\Facades\DB::table('abandoned_checkouts')
        ->where('id', 388)
        ->update([
            'recovered'    => true,
            'recovered_at' => now(),
            'order_id'     => $order->id,
            'step'         => 'completed',
        ]);

    // Link checkout events to this order
    Illuminate\Support\Facades\DB::table('shiprocket_checkout_events')
        ->where('cart_id', '69fb2e479b1b683f026255e5')
        ->whereNull('order_id')
        ->update(['order_id' => $order->id]);

    echo 'Order created: ' . $order->order_number . ' (id=' . $order->id . ')' . PHP_EOL;
    echo 'Total: ' . $order->total . ', Paid: ' . $order->paid_amount . ', Status: ' . $order->payment_status . PHP_EOL;
    echo 'Items: ' . $order->items()->count() . PHP_EOL;
});
