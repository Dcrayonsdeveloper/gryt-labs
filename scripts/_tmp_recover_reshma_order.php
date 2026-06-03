<?php
/**
 * One-off recovery — re-create Reshma khatoon's lost Shiprocket Checkout order.
 *
 * Payment confirmed (Cashfree email):
 *   Cashfree Order : Cudqtlvl1779170292800
 *   Transaction    : 5611497306  (UPI)
 *   Amount         : Rs.379.05   (subtotal 399 - discount 19.95)
 *   Paid           : 2026-05-19 11:28
 *
 * Root cause: createOrderFromWebhook() returned null because the ORDER_PLACED
 * webhook had no platform_order_id / order_id, so the order was never created.
 *
 * Tenant: ayurvexa.  Idempotent (safe to re-run).  Created 2026-05-19.
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('ayurvexa'));

$CART_ID   = '6a0bfb984bdd9724330391eb';   // Shiprocket webhook cart_id
$TOKEN_OID = '6a0bfb925724b50341713f18';   // Shiprocket "Client Order ID"

$existing = App\Models\Order::where('shiprocket_order_id', $CART_ID)->first();
if ($existing) {
    echo "Order already exists: id={$existing->id} {$existing->order_number} — nothing to do.\n";
    return;
}

$ac      = App\Models\AbandonedCheckout::find(498);
$product = App\Models\Product::find(2);

$order = Illuminate\Support\Facades\DB::transaction(function () use ($CART_ID, $TOKEN_OID, $ac, $product) {
    Illuminate\Support\Facades\DB::select('SELECT pg_advisory_xact_lock(hashtext(?))', [$CART_ID]);

    if ($dup = App\Models\Order::where('shiprocket_order_id', $CART_ID)->first()) {
        return $dup;
    }

    // Find or create the customer account
    $user = App\Models\User::where('email', 'isareshma786@gmail.com')
        ->orWhere('phone', '6202916037')->first();
    if (!$user) {
        $user = App\Models\User::create([
            'first_name' => 'Reshma',
            'last_name'  => 'khatoon',
            'phone'      => '6202916037',
            'email'      => 'isareshma786@gmail.com',
            'password'   => bcrypt(Illuminate\Support\Str::random(32)),
        ]);
    }

    $order = App\Models\Order::create([
        'user_id'             => $user->id,
        'guest_name'          => 'Reshma khatoon',
        'guest_email'         => 'isareshma786@gmail.com',
        'guest_phone'         => '6202916037',
        'status'              => 'confirmed',
        'payment_status'      => 'paid',
        'subtotal'            => 399.00,
        'discount'            => 19.95,
        'shipping_cost'       => 0,
        'tax'                 => 0,
        'total'               => 379.05,
        'paid_amount'         => 379.05,
        'source'              => 'api',
        'shiprocket_order_id' => $CART_ID,
        'shipping_address_snapshot' => [
            'name'           => 'Reshma khatoon',
            'phone'          => '6202916037',
            'address_line_1' => 'Vill Jalki, Ps Azamnagar Dist Katihar Near mazar',
            'address_line_2' => 'Sharif jalki',
            'city'           => 'Katihar',
            'state'          => 'Bihar',
            'postal_code'    => '855102',
            'country'        => 'India',
        ],
        'metadata' => [
            'payment_method'         => 'upi',
            'payment_gateway'        => 'Cashfree',
            'transaction_id'         => '5611497306',
            'cashfree_order_id'      => 'Cudqtlvl1779170292800',
            'shiprocket_checkout_id' => $CART_ID,
            'shiprocket_cart_id'     => $CART_ID,
            'shiprocket_token_oid'   => $TOKEN_OID,
            'shiprocket_ost'         => 'ORDER_PLACED',
            'created_from'           => 'manual_recovery',
            'recovery_note'          => 'Recovered 2026-05-19: createOrderFromWebhook returned null '
                                      . '(ORDER_PLACED webhook had no platform_order_id/order_id). '
                                      . 'Payment verified via Cashfree email Cudqtlvl1779170292800.',
        ],
    ]);

    App\Models\OrderItem::create([
        'order_id'     => $order->id,
        'product_id'   => $product?->id ?? 2,
        'product_name' => $product?->name ?? 'Ayurvexa Skin Sculpt – Skin Radiance Supplement',
        'sku'          => $product?->sku ?? 'AYV-SKIN-01',
        'quantity'     => 1,
        'mrp'          => $product?->mrp ?? 479,
        'price'        => 399.00,
        'tax'          => 0,
        'discount'     => 0,
        'total'        => 399.00,
    ]);

    // Decrement stock for the 1 unit sold
    if ($product) {
        $locked = App\Models\Product::where('id', $product->id)->lockForUpdate()->first();
        if ($locked && $locked->stock_quantity >= 1) {
            $locked->decrement('stock_quantity', 1);
            $locked->increment('sales_count', 1);
            if ($locked->fresh()->stock_quantity <= 0) {
                $locked->update(['stock_status' => 'out_of_stock']);
            }
        } elseif ($locked) {
            $locked->increment('sales_count', 1);
        }
    }

    // Close out the abandoned checkout
    if ($ac) {
        $ac->update([
            'step'         => 'completed',
            'recovered'    => true,
            'order_id'     => $order->id,
            'recovered_at' => now(),
        ]);
    }

    // Link the stored webhook events to the new order
    App\Models\ShiprocketCheckoutEvent::where('cart_id', $CART_ID)
        ->update(['order_id' => $order->id]);

    return $order;
});

echo "Created order id={$order->id} number={$order->order_number} "
   . "total={$order->total} {$order->status}/{$order->payment_status}\n";

// Fire OrderPlaced — same call createOrderFromWebhook makes.
// Sends customer + merchant emails; PushOrderToShiprocket & AutoBookBlueDart
// both skip because shiprocket_order_id is already set (no duplicate shipment).
$order->load('items.product', 'user');
App\Events\OrderPlaced::dispatch($order, 'shiprocket_checkout');
echo "OrderPlaced dispatched (source=shiprocket_checkout) — notifications queued.\n";
echo "DONE.\n";
