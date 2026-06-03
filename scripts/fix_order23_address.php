<?php
tenancy()->initialize('ayurvexa');

$order = App\Models\Order::find(23);
if (!$order) {
    echo "Order 23 not found\n";
    return;
}

$address = [
    'name' => 'Lutfur Rahman',
    'phone' => '9435465733',
    'address_line_1' => 'Vill sarupather dist Nowgaonpo Rengbeng',
    'address_line_2' => '',
    'city' => 'Nagaon',
    'state' => 'Assam',
    'postal_code' => '782427',
    'country' => 'India',
];

$order->shipping_address_snapshot = $address;
$order->billing_address_snapshot = $address;
$order->save();

echo "Updated order #23 address: Nagaon, Assam 782427\n";
echo "Shipping: " . json_encode($order->shipping_address_snapshot) . "\n";
