<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('ayurvexa'));

// Old tiers (14% and 24% discount)
$oldTiers = [
    ['qty' => 1, 'discount' => 0],
    ['qty' => 2, 'discount' => 14],
    ['qty' => 3, 'discount' => 24],
];

// New tiers (0% discount)
$newTiers = [
    ['qty' => 1, 'discount' => 0],
    ['qty' => 2, 'discount' => 0],
    ['qty' => 3, 'discount' => 0],
];

$products = App\Models\Product::where('is_active', true)
    ->where('mrp', '>', 0)
    ->orderBy('name')
    ->get(['id', 'name', 'price', 'mrp']);

echo str_pad('Product', 45) . " | " . str_pad('Pack', 6) . " | " . str_pad('Old Price', 10) . " | " . str_pad('New Price', 10) . " | Diff\n";
echo str_repeat('-', 95) . "\n";

foreach ($products as $p) {
    foreach ([1, 2, 3] as $i) {
        $qty = $oldTiers[$i - 1]['qty'];
        $oldDisc = $oldTiers[$i - 1]['discount'];
        $newDisc = $newTiers[$i - 1]['discount'];

        $oldPrice = (int) round($p->price * $qty * (1 - $oldDisc / 100));
        $newPrice = (int) round($p->price * $qty * (1 - $newDisc / 100));
        $diff = $newPrice - $oldPrice;

        $name = $i === 1 ? mb_substr($p->name, 0, 44) : '';
        echo str_pad($name, 45) . " | Pack {$qty} | " . str_pad("₹{$oldPrice}", 10) . " | " . str_pad("₹{$newPrice}", 10) . " | " . ($diff > 0 ? "+₹{$diff}" : "₹0") . "\n";
    }
    echo str_repeat('-', 95) . "\n";
}
