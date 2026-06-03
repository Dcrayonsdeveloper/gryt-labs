<?php
// Fix missing/broken Cashfree orders for Ayurvexa
tenancy()->initialize('ayurvexa');

$cf = app(\App\Services\CashfreeService::class);

// 1. Create missing external order: Cuptrrsa1776924534989 (PAID, ₹80, Lutfur Rahman)
echo "=== Creating missing order: Cuptrrsa1776924534989 ===\n";
$cfOrderId = 'Cuptrrsa1776924534989';

$existing = \App\Models\Order::where('razorpay_order_id', $cfOrderId)->first();
if ($existing) {
    echo "Already exists: {$existing->order_number}\n";
} else {
    $cfOrder = $cf->getOrder($cfOrderId);
    $payments = $cf->getOrderPayments($cfOrderId);
    $cfPaymentId = $payments[0]['cf_payment_id'] ?? null;
    $paymentMethodRaw = $payments[0]['payment_method'] ?? null;
    $methodStr = 'online';
    if (is_array($paymentMethodRaw)) {
        $methodStr = array_key_first($paymentMethodRaw) ?? 'online';
    }

    $customer = $cfOrder['customer_details'] ?? [];
    $amount = (float) ($cfOrder['order_amount'] ?? 0);

    $order = \App\Models\Order::create([
        'user_id' => null,
        'guest_email' => $customer['customer_email'] ?? null,
        'guest_name' => $customer['customer_name'] ?? 'Cashfree Customer',
        'guest_phone' => $customer['customer_phone'] ?? null,
        'status' => 'confirmed',
        'payment_status' => 'paid',
        'subtotal' => $amount,
        'discount' => 0,
        'shipping_cost' => 0,
        'tax' => 0,
        'total' => $amount,
        'paid_amount' => $amount,
        'razorpay_order_id' => $cfOrderId,
        'razorpay_payment_id' => $cfPaymentId,
        'shipping_address_snapshot' => ['name' => $customer['customer_name'] ?? '', 'phone' => $customer['customer_phone'] ?? ''],
        'billing_address_snapshot' => ['name' => $customer['customer_name'] ?? ''],
        'notes' => 'Synced from Cashfree — ' . ($cfOrder['order_note'] ?? 'external payment'),
        'source' => 'api',
        'metadata' => [
            'payment_method' => $methodStr,
            'payment_gateway' => 'cashfree',
            'cashfree_order_id' => $cfOrderId,
            'cashfree_payment_id' => $cfPaymentId,
            'created_by' => 'cashfree_sync',
            'original_created_at' => $cfOrder['created_at'] ?? null,
        ],
    ]);

    if ($cfPaymentId && $amount > 0) {
        \App\Models\Payment::create([
            'order_id' => $order->id,
            'transaction_id' => (string) $cfPaymentId,
            'gateway' => 'cashfree',
            'gateway_transaction_id' => (string) $cfPaymentId,
            'method' => $methodStr,
            'amount' => $amount,
            'currency' => 'INR',
            'status' => 'captured',
            'captured_at' => now(),
        ]);
    }

    echo "Created: {$order->order_number} (ID: {$order->id}) | ₹{$amount} | {$customer['customer_name']}\n";
}

// 2. Fix order #14 — ₹100 advance paid, payment_status still pending
echo "\n=== Fixing Order #14 payment status ===\n";
$o14 = \App\Models\Order::find(14);
if ($o14 && $o14->payment_status === 'pending' && $o14->paid_amount > 0) {
    $o14->payment_status = 'paid';
    $o14->save();
    echo "Fixed: {$o14->order_number} → payment_status = paid\n";
} else {
    echo "Order #14: no fix needed (status: {$o14->payment_status})\n";
}

// 3. Fix order #19 — ₹100 advance paid, delivered, payment_status still pending
echo "\n=== Fixing Order #19 payment status ===\n";
$o19 = \App\Models\Order::find(19);
if ($o19 && $o19->payment_status === 'pending' && $o19->paid_amount > 0) {
    $o19->payment_status = 'paid';
    $o19->save();
    echo "Fixed: {$o19->order_number} → payment_status = paid\n";
} else {
    echo "Order #19: no fix needed (status: {$o19->payment_status})\n";
}

echo "\nDone.\n";
