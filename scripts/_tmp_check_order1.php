<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(\App\Models\Tenant::find('natually'));

// Check today's missing order - Vempalla Sireesha
$platformId = '6a019cf2128315542de21c5d';
$cartId = '6a019cf39b1b683f027a2703';

echo "=== Checking Vempalla Sireesha order ===\n";
$o1 = \App\Models\Order::where('shiprocket_order_id', $platformId)->first();
$o2 = \App\Models\Order::where('shiprocket_order_id', $cartId)->first();
echo "By platform_id: " . ($o1 ? "FOUND #{$o1->order_number}" : "NOT FOUND") . "\n";
echo "By cart_id: " . ($o2 ? "FOUND #{$o2->order_number}" : "NOT FOUND") . "\n";

// Check the SUCCESS webhook raw payload for this order
$event = \App\Models\ShiprocketCheckoutEvent::where('cart_id', $cartId)
    ->where('stage', 'SUCCESS')
    ->first();
if ($event) {
    $payload = $event->raw_payload['payload'] ?? [];
    echo "\nSUCCESS webhook data:\n";
    echo "  payment_status: " . ($payload['payment_status'] ?? 'N/A') . "\n";
    echo "  payment_type: " . ($payload['payment_type'] ?? 'N/A') . "\n";
    echo "  total_amount_payable: " . ($payload['total_amount_payable'] ?? 'N/A') . "\n";
    echo "  subtotal: " . ($payload['subtotal_price'] ?? 'N/A') . "\n";
    echo "  platform_order_id: " . ($payload['platform_order_id'] ?? 'N/A') . "\n";
    echo "  fastrr_order_id: " . ($payload['fastrr_order_id'] ?? 'N/A') . "\n";
}

// Check why the order wasn't created - was there an abandoned checkout?
$ac = \App\Models\AbandonedCheckout::where('shiprocket_order_id', $cartId)
    ->orWhere('shiprocket_order_id', $platformId)
    ->orWhere(function($q) use ($cartId) {
        $q->whereJsonContains('metadata->shiprocket_cart_id', $cartId);
    })->first();
echo "\nAbandoned checkout: " . ($ac ? "id={$ac->id} order_id=" . ($ac->order_id ?: 'NULL') . " name=" . ($ac->name ?: '(empty)') : "NONE") . "\n";

// Check for #2-16: how many have NO abandoned checkout at all?
$missingCartIds = [
    '69fabf00fc9ff1320ab4afd6', '69fab35d2d135e61ddac5f23', '69faa59d2d135e61ddac3189',
    '69fa9ad14bdd972433c2329e', '69fa045dfc9ff1320ab2cd74', '69f6eca04bdd972433b5d1b9',
    '69f6a3a32d135e61dd9e9088', '69f5d7709b1b683f024ffce6', '69f586d34bdd972433b0e1c4',
    '69f583750e54003a6268504b', '69f39e204bdd972433aad794', '69f31d6a4bdd972433a8a473',
    '69f2ea5e2d135e61dd919ae7', '69f262422d135e61dd90988e', '69f20c4f0fc2b008eab23149',
];
$noAc = 0;
$hasAcNoOrder = 0;
foreach ($missingCartIds as $cid) {
    $ac = \App\Models\AbandonedCheckout::where('shiprocket_order_id', $cid)
        ->orWhere(function($q) use ($cid) {
            $q->whereJsonContains('metadata->shiprocket_cart_id', $cid);
        })->first();
    if (!$ac) {
        $noAc++;
    } else {
        $hasAcNoOrder++;
    }
}
echo "\nOf 15 older missing orders:\n";
echo "  No abandoned checkout at all: {$noAc}\n";
echo "  Has AC but no order: {$hasAcNoOrder}\n";
