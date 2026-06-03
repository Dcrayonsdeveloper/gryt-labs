<?php
// Temporary script to check Cashfree orders for Ayurvexa
// Run: php artisan tinker < scripts/check_cashfree_orders.php

tenancy()->initialize('ayurvexa');

$cf = app(\App\Services\CashfreeService::class);

// Check the 3 external Cashfree orders
$externalIds = [
    'Cunbtyus1776929703039',
    'Cuptrrsa1776924534989',
    'Curksjlx1776920660097',
];

echo "=== External Cashfree Orders ===\n";
foreach ($externalIds as $id) {
    $order = $cf->getOrder($id);
    if ($order) {
        echo "$id => {$order['order_status']} | INR {$order['order_amount']} | {$order['customer_details']['customer_name']}\n";
    } else {
        echo "$id => NOT FOUND\n";
    }
}

// Check all CART_* orders from DB against Cashfree
echo "\n=== Website Orders (CART_*) ===\n";
$dbOrders = \App\Models\Order::whereNotNull('razorpay_order_id')
    ->where('razorpay_order_id', 'like', 'CART_%')
    ->get(['id', 'order_number', 'total', 'payment_status', 'razorpay_order_id']);

foreach ($dbOrders as $o) {
    $cfOrder = $cf->getOrder($o->razorpay_order_id);
    $cfStatus = $cfOrder['order_status'] ?? 'API_FAIL';
    $mismatch = ($cfStatus === 'PAID' && $o->payment_status !== 'paid') ? ' *** MISMATCH ***' : '';
    echo "#{$o->id} {$o->order_number} | DB: {$o->payment_status} | CF: {$cfStatus} | {$o->total}{$mismatch}\n";
}

// Check orders without razorpay_order_id that have paid_amount > 0
echo "\n=== Orders with paid_amount but no CF ID ===\n";
$pendingPaid = \App\Models\Order::whereNull('razorpay_order_id')
    ->orWhere('razorpay_order_id', '')
    ->where('paid_amount', '>', 0)
    ->get(['id', 'order_number', 'total', 'paid_amount', 'payment_status']);

foreach ($pendingPaid as $o) {
    echo "#{$o->id} {$o->order_number} | total: {$o->total} | paid: {$o->paid_amount} | status: {$o->payment_status}\n";
}
