<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('ayurvexa'));

// AYURVEXA20 -> 15%
$c = App\Models\Coupon::where('code', 'AYURVEXA20')->first();
if ($c) {
    $c->update(['value' => 15, 'description' => '15% off on orders above Rs.999']);
    echo "AYURVEXA20: 20% -> 15%\n";
}

// AYURVEXA25 -> 20%
$c = App\Models\Coupon::where('code', 'AYURVEXA25')->first();
if ($c) {
    $c->update(['value' => 20, 'description' => '20% off on orders above Rs.1500']);
    echo "AYURVEXA25: 25% -> 20%\n";
}

echo "\n=== Updated ===\n";
$coupons = App\Models\Coupon::whereIn('code', ['WELCOME10', 'AYURVEXA10', 'AYURVEXA20', 'AYURVEXA25'])->get();
foreach ($coupons as $c) {
    echo "{$c->code} | {$c->value}% | min {$c->min_order_amount} | {$c->description}\n";
}
