<?php
/**
 * Update products from JSON data (run on production)
 * Usage: sudo php /var/www/jikra/scripts/update_products_production.php
 */

require '/var/www/jikra/vendor/autoload.php';
$app = require '/var/www/jikra/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductImage;

$json = file_get_contents('/var/www/jikra/temp/product_updates.json');
$products = json_decode($json, true);

$updated = 0;
$created_images = 0;
$activated = 0;
$skipped = 0;

foreach ($products as $data) {
    $slug = $data['slug'];
    $product = Product::where('slug', $slug)->first();

    if (!$product) {
        echo "SKIP (not found): {$slug}\n";
        $skipped++;
        continue;
    }

    // Update name if Amazon title is better (longer, more descriptive)
    $amazonTitle = $data['amazon_title'] ?? null;
    if ($amazonTitle && strlen($amazonTitle) > strlen($product->name)) {
        // Clean up Amazon title - remove brand prefixes, trim
        $cleanTitle = $amazonTitle;
        // Remove common junk patterns
        $cleanTitle = preg_replace('/\s*\(Pack of \d+\)\s*$/', '', $cleanTitle);
        $cleanTitle = trim($cleanTitle, ' .,');

        // Only update if meaningfully different, max 191 chars
        if (strlen($cleanTitle) > 10 && $cleanTitle !== $product->name) {
            $product->name = mb_substr($cleanTitle, 0, 191);
        }
    }

    // Update price if provided and different
    if ($data['price'] && $data['price'] > 0) {
        $product->price = $data['price'];
    }

    // Activate product
    if (!$product->is_active) {
        $product->is_active = true;
        $activated++;
    }

    $product->save();

    // Handle images
    if (!empty($data['images'])) {
        // Get existing image URLs
        $existingUrls = $product->images()->pluck('url')->toArray();

        foreach ($data['images'] as $idx => $imagePath) {
            // Build full URL
            $fullUrl = 'https://jikra.in/storage/' . $imagePath;

            // Skip if this image URL already exists (or similar filename)
            $filename = basename($imagePath);
            $alreadyExists = false;
            foreach ($existingUrls as $eu) {
                if ($eu === $fullUrl || str_contains($eu, $filename)) {
                    $alreadyExists = true;
                    break;
                }
            }
            if ($alreadyExists) continue;

            // Check if file actually exists on disk
            $diskPath = '/var/www/jikra/public/storage/' . $imagePath;
            if (!file_exists($diskPath)) {
                echo "  IMAGE MISSING: {$diskPath}\n";
                continue;
            }

            ProductImage::create([
                'product_id' => $product->id,
                'url' => $fullUrl,
                'is_primary' => ($idx === 0 && $product->images()->count() === 0),
                'position' => $idx + 10, // After existing images
            ]);
            $created_images++;
        }

        // If product had no primary image, set first new one as primary
        if ($product->images()->where('is_primary', true)->count() === 0) {
            $firstImg = $product->images()->orderBy('position')->first();
            if ($firstImg) {
                $firstImg->update(['is_primary' => true]);
            }
        }
    }

    $updated++;
    if ($updated % 50 === 0) {
        echo "Updated {$updated} products, {$created_images} images...\n";
    }
}

echo "\n=== DONE ===\n";
echo "Products updated: {$updated}\n";
echo "Products activated: {$activated}\n";
echo "Images created: {$created_images}\n";
echo "Products skipped (not found): {$skipped}\n";
