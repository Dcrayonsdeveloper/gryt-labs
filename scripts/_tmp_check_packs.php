<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('ayurvexa'));

$tiers = json_decode(App\Models\Setting::get('pack_tiers'), true);
$products = App\Models\Product::where('is_active', true)->where('mrp', '>', 0)->take(5)->get(['id', 'name', 'price', 'mrp']);

foreach ($products as $p) {
    echo $p->name . " | price={$p->price} mrp={$p->mrp}\n";
    foreach ($tiers as $t) {
        $qty = $t['qty'];
        $disc = $t['discount'];
        $packPrice = (int) round($p->price * $qty * (1 - $disc / 100));
        $packMrp = (int) round($p->mrp * $qty);
        $savings = $packMrp - $packPrice;
        echo "  Pack {$qty}: price={$packPrice} mrp={$packMrp} savings={$savings}\n";
    }
}
