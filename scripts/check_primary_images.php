<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->handleRequest(Illuminate\Http\Request::capture());

use App\Models\Tenant;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

$tenant = Tenant::find('natually');
if (!$tenant) { echo "Tenant not found.\n"; exit(1); }
tenancy()->initialize($tenant);

// Find products that HAVE images but NONE marked as primary
$hasBrokenPrimary = DB::select("
    SELECT p.id, p.slug, p.name,
           COUNT(pi.id) as total_images,
           COUNT(CASE WHEN pi.is_primary = true THEN 1 END) as primary_count
    FROM products p
    JOIN product_images pi ON pi.product_id = p.id
    WHERE p.is_active = true AND p.deleted_at IS NULL
    GROUP BY p.id, p.slug, p.name
    HAVING COUNT(CASE WHEN pi.is_primary = true THEN 1 END) = 0
    ORDER BY p.name
");

echo "=== Products with images but NO primary flag: " . count($hasBrokenPrimary) . " ===\n";
foreach ($hasBrokenPrimary as $row) {
    echo "  ID={$row->id} | imgs={$row->total_images} | {$row->slug}\n";
}

// Also check: products where primaryImage eager-load returns empty but images exist
$products = Product::where('is_active', true)
    ->with(['primaryImage', 'images'])
    ->get();

$mismatch = 0;
foreach ($products as $p) {
    $hasPrimary = $p->primaryImage->isNotEmpty();
    $hasAny = $p->images->isNotEmpty();
    if ($hasAny && !$hasPrimary) {
        echo "  MISMATCH: {$p->slug} — has {$p->images->count()} images but primaryImage is empty\n";
        $mismatch++;
    }
}
echo "\nTotal mismatches: $mismatch\n";

// Fix: set is_primary=true on the first image for products that have images but no primary
if ($mismatch > 0) {
    echo "\nFixing...\n";
    $fixed = 0;
    foreach ($products as $p) {
        if ($p->images->isNotEmpty() && $p->primaryImage->isEmpty()) {
            $firstImage = $p->images->sortBy('position')->first();
            $firstImage->update(['is_primary' => true]);
            echo "  FIXED: {$p->slug} → image #{$firstImage->id}\n";
            $fixed++;
        }
    }
    echo "Fixed $fixed products\n";
}
