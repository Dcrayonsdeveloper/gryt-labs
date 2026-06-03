<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->handleRequest(Illuminate\Http\Request::capture());

tenancy()->initialize(App\Models\Tenant::find('natually'));

$noImg = App\Models\Product::whereDoesntHave('images')->where('is_active', true)->get(['id','slug','name','stock_quantity','stock_status']);
echo "ID | Stock | Status | Slug | Name\n";
foreach ($noImg as $p) {
    echo "{$p->id} | {$p->stock_quantity} | {$p->stock_status} | {$p->slug} | {$p->name}\n";
}

// Also check total out of stock active products
$oos = App\Models\Product::where('is_active', true)->where(function($q) {
    $q->where('stock_quantity', '<=', 0)->orWhere('stock_status', 'out_of_stock');
})->count();
echo "\nTotal active but out-of-stock: $oos\n";
