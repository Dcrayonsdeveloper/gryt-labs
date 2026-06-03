<?php
/**
 * Create the AYURVEXA25 coupon (25% off, min Rs.2000) on the Ayurvexa tenant.
 * Idempotent — if a coupon with code AYURVEXA25 already exists, only its value
 * is corrected to 25% (other fields left untouched so admin edits aren't lost).
 * Created 2026-05-20.
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('ayurvexa'));

$show = function (string $label) {
    echo "=== {$label} — public coupons ===\n";
    foreach (App\Models\Coupon::whereIn('code', ['AYURVEXA25', 'AYURVEXA20', 'AYURVEXA15', 'AYURVEXA10', 'WELCOME10'])->orderByDesc('value')->orderBy('id')->get() as $c) {
        echo sprintf("  id %-3d %-12s | %2d%% | min Rs.%-5d | %-8s | %s\n",
            $c->id, $c->code, (int) $c->value, (int) $c->min_order_amount,
            $c->is_active ? 'active' : 'inactive', $c->description);
    }
};

$show('BEFORE');

Illuminate\Support\Facades\DB::transaction(function () {
    $c = App\Models\Coupon::where('code', 'AYURVEXA25')->first();
    if (!$c) {
        $c = App\Models\Coupon::create([
            'code'             => 'AYURVEXA25',
            'name'             => 'Ayurvexa 25% Off',
            'description'      => '25% off on orders above Rs.2000',
            'type'             => 'percentage',
            'value'            => 25,
            'min_order_amount' => 2000,
            'max_discount'     => 1000,
            'usage_per_user'   => 99,
            'is_active'        => true,
        ]);
        echo "\nCreated AYURVEXA25 (id {$c->id}): 25% off, min Rs.2000, max Rs.1000\n";
    } elseif ((float) $c->value !== 25.0) {
        $c->update(['value' => 25]);
        echo "\nUpdated existing AYURVEXA25 (id {$c->id}): value -> 25%\n";
    } else {
        echo "\nAYURVEXA25 already at 25% — skipped (id {$c->id})\n";
    }
});

echo "\n";
$show('AFTER');
echo "\nDONE.\n";
