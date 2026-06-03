<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

foreach (['natually', 'ayurvexa', 'jikra', 'getsetnova'] as $tid) {
    $t = \App\Models\Tenant::find($tid);
    if ($t) {
        tenancy()->initialize($t);
        $email = \App\Models\Setting::get('shiprocket_email', '') ?: '(empty)';
        $pass = empty(\App\Models\Setting::get('shiprocket_password', '')) ? '(empty)' : '***set***';
        $ck = empty(\App\Models\Setting::get('shiprocket_checkout_api_key', '')) ? '(empty)' : '***set***';
        $cs = empty(\App\Models\Setting::get('shiprocket_checkout_api_secret', '')) ? '(empty)' : '***set***';
        $en = \App\Models\Setting::get('shiprocket_checkout_enabled', '') ?: '(empty)';
        echo "{$tid}: email={$email} pass={$pass} checkout_key={$ck} checkout_secret={$cs} enabled={$en}\n";
    }
}

// Check if shipping API token works for natually
echo "\n=== Testing Shipping API token for natually ===\n";
tenancy()->initialize(\App\Models\Tenant::find('natually'));
$sr = new \App\Services\ShiprocketService();
echo "isConfigured: " . ($sr->isConfigured() ? 'YES' : 'NO') . "\n";

// Try fetching shipping orders
$result = $sr->getShippingOrders(1, 5);
if ($result) {
    echo "getShippingOrders: SUCCESS — got " . count($result['orders']) . " orders (total: {$result['total']})\n";
} else {
    echo "getShippingOrders: FAILED\n";
}

// Also test for ayurvexa
echo "\n=== Testing Shipping API token for ayurvexa ===\n";
tenancy()->initialize(\App\Models\Tenant::find('ayurvexa'));
$sr2 = new \App\Services\ShiprocketService();
echo "isConfigured: " . ($sr2->isConfigured() ? 'YES' : 'NO') . "\n";
$result2 = $sr2->getShippingOrders(1, 5);
if ($result2) {
    echo "getShippingOrders: SUCCESS — got " . count($result2['orders']) . " orders (total: {$result2['total']})\n";
} else {
    echo "getShippingOrders: FAILED\n";
}
