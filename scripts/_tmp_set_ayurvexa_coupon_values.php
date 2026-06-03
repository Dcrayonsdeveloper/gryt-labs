<?php
/**
 * Set Ayurvexa public coupon discount values so the codes match their numbers:
 *   AYURVEXA20  : value 15 -> 20 (min Rs.999 unchanged)
 *   AYURVEXA25  : value 20 -> 25 (min Rs.1500 unchanged)
 *
 * Tenant: ayurvexa.  Wrapped in a DB transaction.  Idempotent.  Created 2026-05-19.
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('ayurvexa'));

$show = function (string $label) {
    echo "=== {$label} ===\n";
    foreach (App\Models\Coupon::whereIn('code', ['AYURVEXA20', 'AYURVEXA25', 'AYURVEXA10', 'WELCOME10'])->orderByDesc('value')->orderBy('id')->get() as $c) {
        echo sprintf("  id %-3d %-12s | %2d%% | min Rs.%-5d | %-8s | %s\n",
            $c->id, $c->code, (int) $c->value, (int) $c->min_order_amount,
            $c->is_active ? 'active' : 'inactive', $c->description);
    }
};

$show('BEFORE');

Illuminate\Support\Facades\DB::transaction(function () {
    $c = App\Models\Coupon::where('code', 'AYURVEXA20')->first();
    if ($c && (float) $c->value !== 20.0) {
        $c->update([
            'value'       => 20,
            'name'        => 'Ayurvexa 20% Off',
            'description' => '20% off on orders above Rs.999',
        ]);
        echo "\nUpdated AYURVEXA20: value -> 20% (id {$c->id})\n";
    } else {
        echo "\nAYURVEXA20 already at 20% — skipped\n";
    }

    $c = App\Models\Coupon::where('code', 'AYURVEXA25')->first();
    if ($c && (float) $c->value !== 25.0) {
        $c->update([
            'value'       => 25,
            'name'        => 'Ayurvexa 25% Off',
            'description' => '25% off on orders above Rs.1500',
        ]);
        echo "Updated AYURVEXA25: value -> 25% (id {$c->id})\n";
    } else {
        echo "AYURVEXA25 already at 25% — skipped\n";
    }
});

echo "\n";
$show('AFTER');

echo "\nDONE.\n";
