<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('natually'));

$keys = [
    'shiprocket_checkout_enabled',
    'shiprocket_checkout_api_key',
    'shiprocket_checkout_api_secret',
    'shiprocket_checkout_channel_id',
    'razorpay_enabled',
    'razorpay_key_id',
    'razorpay_key_secret',
    'cashfree_enabled',
];

echo "=== Natually Checkout Settings ===\n";
foreach ($keys as $k) {
    $val = App\Models\Setting::get($k, '');
    $display = $val ? (strlen($val) > 10 ? substr($val, 0, 6) . '...' : $val) : 'EMPTY';
    echo "  {$k} = {$display}\n";
}

// Check redirect URL generation
echo "\nRedirect URL: " . url('/checkout/success/shiprocket') . "\n";

// Check if the callback route exists
try {
    echo "Callback route: " . route('checkout.shiprocket.callback') . "\n";
} catch (Exception $e) {
    echo "Callback route ERROR: " . $e->getMessage() . "\n";
}

// Check recent token generation logs
echo "\n=== Recent Shiprocket token logs ===\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES);
    $count = 0;
    foreach (array_reverse($lines) as $line) {
        if (stripos($line, 'ShiprocketCheckout') !== false && stripos($line, 'token') !== false) {
            echo "  " . $line . "\n";
            if (++$count >= 5) break;
        }
    }
}
