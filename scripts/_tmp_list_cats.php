<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tenant = App\Models\Tenant::find('natually');
$tenant->run(function() {
    $cats = DB::table('categories')->select('id','name','slug')->orderBy('name')->get();
    echo "=== CATEGORIES (" . $cats->count() . ") ===" . PHP_EOL;
    foreach($cats as $c) {
        $count = DB::table('category_product')->where('category_id', $c->id)->count();
        echo $c->id . '|' . $c->slug . '|' . $c->name . '|products:' . $count . PHP_EOL;
    }

    echo PHP_EOL . "=== PRODUCTS (" . DB::table('products')->count() . ") ===" . PHP_EOL;
    $products = DB::table('products')->select('id','slug','name')->orderBy('name')->get();
    foreach($products as $p) {
        $catIds = DB::table('category_product')->where('product_id', $p->id)->pluck('category_id')->toArray();
        echo $p->id . '|' . $p->slug . '|' . implode(',', $catIds) . PHP_EOL;
    }
});
