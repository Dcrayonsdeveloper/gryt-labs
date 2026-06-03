<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

App\Models\Tenant::find('natually')->run(function() {
    $products = DB::table('products')->select('id','slug','name','is_active')->orderBy('name')->get();

    // Find products that share a common base name (e.g. "carrot cream 180g" and "carrot cream 350g")
    $csvCount = 178;
    $dbActive = $products->where('is_active', true)->count();
    $dbInactive = $products->where('is_active', false)->count();

    echo "CSV active: {$csvCount}" . PHP_EOL;
    echo "DB active: {$dbActive}, inactive: {$dbInactive}, total: " . $products->count() . PHP_EOL . PHP_EOL;

    // Group by rough base slug (strip trailing size indicators)
    $groups = [];
    foreach ($products as $p) {
        // Strip common suffixes: -180g, -200g, -350g, -30ml, -50ml, -100ml, -500ml, etc.
        $base = preg_replace('/-?\d+(g|gm|gms|ml|ml2|oz)$/', '', $p->slug);
        // Also strip long natually- prefix descriptions
        $groups[$base][] = $p;
    }

    $dupeGroups = 0;
    $extraProducts = 0;
    echo "=== DUPLICATE GROUPS (same product, different sizes) ===" . PHP_EOL;
    foreach ($groups as $base => $items) {
        if (count($items) > 1) {
            $dupeGroups++;
            $extraProducts += count($items) - 1;
            $slugs = array_map(function($p) {
                return $p->slug . ($p->is_active ? '' : ' [INACTIVE]');
            }, $items);
            echo "  [{$base}] (" . count($items) . "): " . implode(' | ', $slugs) . PHP_EOL;
        }
    }

    echo PHP_EOL . "Duplicate groups: {$dupeGroups}" . PHP_EOL;
    echo "Extra products from dupes: {$extraProducts}" . PHP_EOL;
    echo "Unique base products: " . (count($groups)) . PHP_EOL;

    // Also count products NOT matching any CSV handle
    echo PHP_EOL . "=== PRODUCTS WITH NO CATEGORY ===" . PHP_EOL;
    $noCat = 0;
    foreach ($products as $p) {
        $cats = DB::table('category_product')->where('product_id', $p->id)->count();
        if ($cats == 0) {
            $noCat++;
            echo "  {$p->id}|{$p->slug}" . ($p->is_active ? '' : ' [INACTIVE]') . PHP_EOL;
        }
    }
    echo "Total with no category: {$noCat}" . PHP_EOL;
});
