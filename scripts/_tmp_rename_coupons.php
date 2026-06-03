<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('ayurvexa'));

// Rename SAVE10 -> AYURVEXA10
$c = App\Models\Coupon::where('code', 'SAVE10')->first();
if ($c) { $c->update(['code' => 'AYURVEXA10']); echo "SAVE10 -> AYURVEXA10\n"; }

// Rename AYUR20 -> AYURVEXA20
$c = App\Models\Coupon::where('code', 'AYUR20')->first();
if ($c) { $c->update(['code' => 'AYURVEXA20']); echo "AYUR20 -> AYURVEXA20\n"; }

// Rename MEGA25 -> AYURVEXA25
$c = App\Models\Coupon::where('code', 'MEGA25')->first();
if ($c) { $c->update(['code' => 'AYURVEXA25']); echo "MEGA25 -> AYURVEXA25\n"; }

// Verify
echo "\n=== Updated coupons ===\n";
$coupons = App\Models\Coupon::whereIn('code', ['WELCOME10', 'AYURVEXA10', 'AYURVEXA20', 'AYURVEXA25'])->get();
foreach ($coupons as $c) {
    echo "{$c->code} | {$c->value}% | min {$c->min_order_amount}\n";
}
