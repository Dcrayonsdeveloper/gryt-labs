<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->handleRequest(Illuminate\Http\Request::capture());

use App\Models\Tenant;
use App\Models\Product;

$tenant = Tenant::find('natually');
tenancy()->initialize($tenant);

// Check all products — compare primaryImage vs images
$products = Product::where('is_active', true)
    ->with(['primaryImage', 'images'])
    ->get();

$issues = [];
foreach ($products as $p) {
    $pi = $p->primaryImage->first();
    $anyImg = $p->images->first();

    $piUrl = $pi ? $pi->url : 'NONE';
    $imgUrl = $anyImg ? $anyImg->url : 'NONE';
    $attrUrl = $p->primary_image_url;

    // Flag if primary_image_url shows placeholder but images exist
    if ($anyImg && str_contains($attrUrl, 'no-product-image')) {
        $issues[] = [
            'slug' => $p->slug,
            'primary_image_url' => $attrUrl,
            'primaryImage_url' => $piUrl,
            'first_image_url' => $imgUrl,
            'images_count' => $p->images->count(),
            'primary_is_primary' => $pi ? $pi->is_primary : 'N/A',
        ];
    }
}

echo "Products where card shows placeholder but has images: " . count($issues) . "\n";
foreach ($issues as $i) {
    echo json_encode($i) . "\n";
}

// Also check specific product
$bb = Product::where('slug', 'like', 'bubblegum%')->with(['primaryImage', 'images'])->first();
if ($bb) {
    echo "\n=== Bubblegum debug ===\n";
    echo "ID: {$bb->id}\n";
    echo "primary_image_url: {$bb->primary_image_url}\n";
    echo "primaryImage count: {$bb->primaryImage->count()}\n";
    echo "images count: {$bb->images->count()}\n";
    foreach ($bb->images as $img) {
        echo "  IMG #{$img->id}: is_primary={$img->is_primary} | raw_url={$img->getRawOriginal('url')} | url={$img->url}\n";
    }
    // Check file existence
    foreach ($bb->images as $img) {
        $raw = $img->getRawOriginal('url');
        $diskPath = '/var/www/jikra/storage/app/public/' . $raw;
        echo "  FILE: $diskPath → " . (file_exists($diskPath) ? 'EXISTS' : 'MISSING') . "\n";
    }
}
