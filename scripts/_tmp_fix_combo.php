<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

App\Models\Tenant::find('natually')->run(function() {
    // Find the lip-care-combo product
    $product = DB::table('products')->where('slug', 'like', '%lip-care-combo%')->first();
    if (!$product) {
        echo "lip-care-combo product NOT FOUND" . PHP_EOL;
        return;
    }
    echo "Product: id={$product->id}, slug={$product->slug}, name={$product->name}" . PHP_EOL;
    echo "Active: " . ($product->is_active ? 'yes' : 'no') . PHP_EOL;
    echo "Deleted: " . ($product->deleted_at ? 'yes' : 'no') . PHP_EOL;

    // Current images
    $images = DB::table('product_images')->where('product_id', $product->id)->get();
    echo PHP_EOL . "Current images (" . $images->count() . "):" . PHP_EOL;
    foreach ($images as $img) {
        echo "  id={$img->id} pos={$img->position} primary=" . ($img->is_primary ? 'Y' : 'N') . " {$img->image_path}" . PHP_EOL;
    }

    // Current categories
    $cats = DB::table('category_product')
        ->join('categories', 'categories.id', '=', 'category_product.category_id')
        ->where('product_id', $product->id)
        ->select('categories.id', 'categories.slug', 'categories.name')
        ->get();
    echo PHP_EOL . "Current categories:" . PHP_EOL;
    foreach ($cats as $c) {
        echo "  id={$c->id} {$c->slug} ({$c->name})" . PHP_EOL;
    }

    // Check lip-care category
    $lipCare = DB::table('categories')->where('slug', 'lip-care')->first();
    echo PHP_EOL . "Lip Care category: id={$lipCare->id}, slug={$lipCare->slug}" . PHP_EOL;

    // Check combos category
    $combos = DB::table('categories')->where('slug', 'combos-kits')->first();
    echo "Combos & Kits category: id={$combos->id}, slug={$combos->slug}" . PHP_EOL;

    // Check product_images table columns
    $cols = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'product_images' ORDER BY ordinal_position");
    echo PHP_EOL . "product_images columns: " . implode(', ', array_map(fn($c) => $c->column_name, $cols)) . PHP_EOL;
});
