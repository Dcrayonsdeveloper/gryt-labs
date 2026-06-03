<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use Illuminate\Support\Str;

$tenant = Tenant::find('keratine');
if (!$tenant) { echo "Tenant not found!\n"; exit(1); }

tenancy()->initialize($tenant);
echo "Initialized keratine tenant\n";

// Step 1: Seed admin user + seller if not exists
$adminEmail = 'admin@keratine.com';
$admin = \App\Models\User::where('email', $adminEmail)->first();
if (!$admin) {
    $admin = \App\Models\User::create([
        'first_name' => 'Keratine',
        'last_name' => 'Admin',
        'email' => $adminEmail,
        'password' => bcrypt('changeme123'),
        'role' => 'admin',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    echo "Created admin user: {$adminEmail}\n";

    try {
        $roleId = DB::table('roles')->where('name', 'admin')->value('id');
        if ($roleId) {
            DB::table('model_has_roles')->insert([
                'role_id' => $roleId,
                'model_type' => 'App\\Models\\User',
                'model_id' => $admin->id,
            ]);
            echo "Assigned admin role\n";
        }
    } catch (Exception $e) {
        echo "Role note: " . $e->getMessage() . "\n";
    }
} else {
    echo "Admin already exists\n";
}

// Step 2: Create seller if not exists
$seller = \App\Models\Seller::first();
if (!$seller) {
    $seller = \App\Models\Seller::create([
        'user_id' => $admin->id,
        'business_name' => 'Keratine Professional',
        'slug' => 'keratine-professional',
        'legal_name' => 'Keratine Professional',
        'description' => 'Official Keratine Professional Store',
        'status' => 'approved',
        'commission_rate' => 0,
        'approved_at' => now(),
    ]);
    echo "Created seller (ID: {$seller->id})\n";
} else {
    echo "Seller already exists (ID: {$seller->id})\n";
}

// Step 3: Parse CSV
$file = '/tmp/keratine_products.csv';
if (!file_exists($file)) { echo "CSV not found!\n"; exit(1); }

$handle = fopen($file, 'r');
$headers = fgetcsv($handle);
$products = [];
$current = null;

while (($row = fgetcsv($handle)) !== false) {
    $data = array_combine($headers, array_pad($row, count($headers), ''));

    if (!empty($data['Title'])) {
        if ($current) $products[] = $current;
        $current = [
            'handle' => $data['Handle'],
            'title' => $data['Title'],
            'body' => $data['Body (HTML)'],
            'vendor' => $data['Vendor'],
            'category_path' => $data['Product Category'],
            'type' => $data['Type'] ?? '',
            'tags' => $data['Tags'] ?? '',
            'price' => (float) $data['Variant Price'],
            'compare_price' => (float) ($data['Variant Compare At Price'] ?: 0),
            'sku' => $data['Variant SKU'],
            'weight' => (float) $data['Variant Grams'],
            'status' => $data['Status'] ?? 'active',
            'published' => ($data['Published'] ?? '') === 'true',
            'seo_title' => $data['SEO Title'] ?? '',
            'seo_description' => $data['SEO Description'] ?? '',
            'images' => [],
        ];
        if (!empty($data['Image Src'])) {
            $current['images'][] = [
                'url' => $data['Image Src'],
                'alt' => $data['Image Alt Text'] ?? '',
                'position' => (int) ($data['Image Position'] ?? 1),
            ];
        }
    } else {
        if ($current && !empty($data['Image Src'])) {
            $current['images'][] = [
                'url' => $data['Image Src'],
                'alt' => $data['Image Alt Text'] ?? '',
                'position' => (int) ($data['Image Position'] ?? 1),
            ];
        }
    }
}
if ($current) $products[] = $current;
fclose($handle);

echo "Parsed " . count($products) . " products from CSV\n";

// Step 4: Create categories
echo "Creating categories...\n";
$categoryMap = [];

DB::statement('TRUNCATE TABLE categories CASCADE');

$categoryPaths = collect($products)->pluck('category_path')->filter()->unique();
$parentCategories = [];
$position = 1;

foreach ($categoryPaths as $path) {
    $parts = array_map('trim', explode('>', $path));
    $topLevel = $parts[0] ?? '';
    if (empty($topLevel) || isset($parentCategories[$topLevel])) continue;

    $parent = \App\Models\Category::create([
        'name' => $topLevel,
        'slug' => Str::slug($topLevel),
        'description' => "Shop {$topLevel}",
        'position' => $position++,
        'is_active' => true,
        'is_featured' => $position <= 7,
    ]);
    $parentCategories[$topLevel] = $parent;
}

// Sub-categories
$subMap = [];
foreach ($categoryPaths as $path) {
    $parts = array_map('trim', explode('>', $path));
    if (count($parts) < 2) continue;

    $prevParent = $parentCategories[$parts[0]] ?? null;
    if (!$prevParent) continue;

    for ($i = 1; $i < count($parts); $i++) {
        $sub = trim($parts[$i]);
        if (empty($sub)) continue;

        $key = implode('>', array_slice($parts, 0, $i + 1));
        if (isset($subMap[$key])) {
            $prevParent = (object)['id' => $subMap[$key]];
            continue;
        }

        $slug = Str::slug($sub);
        $existingCat = \App\Models\Category::where('slug', $slug)->first();
        if ($existingCat) {
            $subMap[$key] = $existingCat->id;
            $prevParent = $existingCat;
            continue;
        }

        $cat = \App\Models\Category::create([
            'parent_id' => $prevParent->id,
            'name' => $sub,
            'slug' => $slug,
            'description' => "Shop {$sub}",
            'position' => 1,
            'is_active' => true,
        ]);
        $subMap[$key] = $cat->id;
        $prevParent = $cat;
    }
}

// Map full paths to deepest category
foreach ($categoryPaths as $path) {
    $parts = array_map('trim', explode('>', $path));
    for ($depth = count($parts); $depth >= 1; $depth--) {
        $tryKey = implode('>', array_slice($parts, 0, $depth));
        if (isset($subMap[$tryKey])) {
            $categoryMap[$path] = $subMap[$tryKey];
            break;
        }
    }
    if (!isset($categoryMap[$path]) && isset($parentCategories[$parts[0]])) {
        $categoryMap[$path] = $parentCategories[$parts[0]]->id;
    }
}

// "Uncategorized" → Hair Care category
if (!isset($categoryMap['Uncategorized'])) {
    $hairCare = \App\Models\Category::where('slug', 'hair-care')->first();
    if (!$hairCare) {
        // Check if "Personal Care" exists, use its Hair Care child
        $hairCare = \App\Models\Category::where('slug', 'hair-care')->first();
    }
    if (!$hairCare) {
        $hairCare = \App\Models\Category::create([
            'name' => 'Hair Care',
            'slug' => 'hair-care-general',
            'description' => 'Hair Care Products',
            'position' => $position++,
            'is_active' => true,
            'is_featured' => true,
        ]);
    }
    $categoryMap['Uncategorized'] = $hairCare->id;
    $categoryMap[''] = $hairCare->id;
}

echo "Created " . \App\Models\Category::count() . " categories\n";

// Step 5: Setup brands
$vendors = collect($products)->pluck('vendor')->filter()->unique();
foreach ($vendors as $vendor) {
    \App\Models\Brand::firstOrCreate(
        ['slug' => Str::slug($vendor)],
        [
            'name' => $vendor,
            'description' => $vendor,
            'is_active' => true,
            'is_featured' => true,
            'position' => \App\Models\Brand::max('position') + 1,
        ]
    );
}
echo "Brands: " . \App\Models\Brand::count() . "\n";

// Step 6: Import products
$imported = 0;
$skipped = 0;

foreach ($products as $p) {
    if ($p['status'] === 'archived') { $skipped++; continue; }

    $slug = Str::slug($p['handle']);
    if (\App\Models\Product::where('sku', $p['sku'])->exists()) { $skipped++; continue; }
    if (\App\Models\Product::where('slug', $slug)->exists()) { $skipped++; continue; }

    $categoryId = $categoryMap[$p['category_path']] ?? $categoryMap['Uncategorized'] ?? null;
    $brand = \App\Models\Brand::where('slug', Str::slug($p['vendor']))->first();

    $seoDesc = $p['seo_description'] ?: Str::limit(strip_tags($p['body']), 155);
    $seoTitle = $p['seo_title'] ?: $p['title'];

    $product = \App\Models\Product::create([
        'uuid' => Str::uuid(),
        'seller_id' => $seller->id,
        'brand_id' => $brand?->id,
        'category_id' => $categoryId,
        'name' => $p['title'],
        'slug' => $slug,
        'short_description' => Str::limit(strip_tags($p['body']), 200),
        'description' => $p['body'],
        'sku' => $p['sku'],
        'mrp' => $p['compare_price'] > 0 ? $p['compare_price'] : $p['price'],
        'price' => $p['price'],
        'stock_quantity' => 100,
        'stock_status' => 'in_stock',
        'low_stock_threshold' => 5,
        'weight' => $p['weight'] > 0 ? $p['weight'] / 1000 : null,
        'weight_unit' => 'kg',
        'is_active' => true,
        'is_featured' => false,
        'is_taxable' => true,
        'tax_rate' => 18.00,
        'seo_data' => [
            'title' => $seoTitle,
            'description' => $seoDesc,
            'keywords' => strtolower($p['title']) . ', keratine professional, hair care',
        ],
        'status' => 'approved',
        'published_at' => now(),
    ]);

    foreach ($p['images'] as $i => $img) {
        \App\Models\ProductImage::create([
            'product_id' => $product->id,
            'url' => $img['url'],
            'alt_text' => $img['alt'] ?: $p['title'],
            'position' => $img['position'],
            'is_primary' => $i === 0,
        ]);
    }

    $imported++;
    if ($imported % 10 === 0) echo "Imported {$imported}...\n";
}

echo "\n=== IMPORT COMPLETE ===\n";
echo "Imported: {$imported}\n";
echo "Skipped: {$skipped}\n";
echo "Total products: " . \App\Models\Product::count() . "\n";
echo "Total images: " . \App\Models\ProductImage::count() . "\n";
echo "Total categories: " . \App\Models\Category::count() . "\n";
echo "Total brands: " . \App\Models\Brand::count() . "\n";
