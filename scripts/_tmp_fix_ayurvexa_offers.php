<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('ayurvexa'));

// 1. Create SAVE10 - 10% off on orders 499 to 999
App\Models\Coupon::updateOrCreate(
    ['code' => 'SAVE10'],
    [
        'name' => 'Save 10%',
        'type' => 'percentage',
        'value' => 10,
        'min_order_amount' => 499,
        'max_discount' => 100,
        'description' => '10% off on orders above Rs.499',
        'is_active' => true,
        'usage_per_user' => 99,
    ]
);
echo "Created/Updated SAVE10\n";

// 2. Create AYUR20 - 20% off on orders above 999
App\Models\Coupon::updateOrCreate(
    ['code' => 'AYUR20'],
    [
        'name' => 'Ayurvexa 20% Off',
        'type' => 'percentage',
        'value' => 20,
        'min_order_amount' => 999,
        'max_discount' => 500,
        'description' => '20% off on orders above Rs.999',
        'is_active' => true,
        'usage_per_user' => 99,
    ]
);
echo "Created/Updated AYUR20\n";

// 3. Create MEGA25 - 25% off on orders above 1500
App\Models\Coupon::updateOrCreate(
    ['code' => 'MEGA25'],
    [
        'name' => 'Mega 25% Off',
        'type' => 'percentage',
        'value' => 25,
        'min_order_amount' => 1500,
        'max_discount' => 750,
        'description' => '25% off on orders above Rs.1500',
        'is_active' => true,
        'usage_per_user' => 99,
    ]
);
echo "Created/Updated MEGA25\n";

// Verify all 4
echo "\n=== Final 4 public offers ===\n";
$coupons = App\Models\Coupon::whereIn('code', ['WELCOME10', 'SAVE10', 'AYUR20', 'MEGA25'])->get();
foreach ($coupons as $c) {
    echo sprintf("%-12s | %s%% | min Rs.%s | max Rs.%s | %s | %s\n",
        $c->code, $c->value, $c->min_order_amount, $c->max_discount ?? 'none', $c->is_active ? 'active' : 'inactive', $c->description);
}
