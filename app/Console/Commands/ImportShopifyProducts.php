<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportShopifyProducts extends Command
{
    protected $signature = 'products:import-shopify {file? : Path to CSV file}';
    protected $description = 'Import products from Shopify CSV export';

    private array $categoryMap = [];
    private array $metafieldCols = [];

    public function handle(): int
    {
        $file = $this->argument('file') ?: 'products_export_1 (2).csv';

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $this->info('Parsing CSV...');
        $products = $this->parseCSV($file);
        $this->info("Found " . count($products) . " products");

        // Step 1: Create categories from product data
        $this->info('Creating categories...');
        $this->createCategories($products);

        // Step 2: Get or create vendor as brand
        $this->info('Setting up brands...');
        $this->setupBrands($products);

        // Step 3: Import products
        $this->info('Importing products...');
        $bar = $this->output->createProgressBar(count($products));
        $imported = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($products as $p) {
            if ($p['status'] === 'archived') {
                $skipped++;
                $bar->advance();
                continue;
            }

            if ($this->importProduct($p)) {
                $imported++;
            } else {
                $updated++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Imported: {$imported} | Updated: {$updated} | Skipped (archived): {$skipped}");
        $this->info('Done!');

        return 0;
    }

    private function parseCSV(string $file): array
    {
        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle);
        $products = [];
        $current = null;

        // Discover metafield column names (Shopify exports use long names like
        // "Faq1 (product.metafields.custom.faq1)")
        $this->metafieldCols = $this->discoverMetafieldColumns($headers);

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
                    'qty' => (int) $data['Variant Inventory Qty'],
                    'weight' => (float) $data['Variant Grams'],
                    'status' => $data['Status'] ?? 'active',
                    'published' => $data['Published'] === 'true',
                    'seo_title' => $data['SEO Title'] ?? '',
                    'seo_description' => $data['SEO Description'] ?? '',
                    'images' => [],
                    'color' => $data['Color (product.metafields.shopify.color-pattern)'] ?? '',
                    'material' => $data['Material (product.metafields.shopify.material)'] ?? '',
                    'features' => $data['Features (product.metafields.shopify.features)'] ?? '',
                    'power_source' => $data['Power source (product.metafields.shopify.power-source)'] ?? '',
                    // Metafields: FAQs, rating, review count, heading, canonical
                    ...$this->extractMetafields($data),
                ];
                if (!empty($data['Image Src'])) {
                    $current['images'][] = [
                        'url' => $data['Image Src'],
                        'alt' => $data['Image Alt Text'] ?? '',
                        'position' => (int) ($data['Image Position'] ?? 1),
                    ];
                }
            } else {
                if ($current) {
                    if (!empty($data['Image Src'])) {
                        $current['images'][] = [
                            'url' => $data['Image Src'],
                            'alt' => $data['Image Alt Text'] ?? '',
                            'position' => (int) ($data['Image Position'] ?? 1),
                        ];
                    }
                    // Metafields may appear on variant rows — fill if empty
                    foreach ($this->extractMetafields($data) as $k => $v) {
                        if ($v && empty($current[$k])) {
                            $current[$k] = $v;
                        }
                    }
                }
            }
        }
        if ($current) $products[] = $current;
        fclose($handle);

        return $products;
    }

    private function discoverMetafieldColumns(array $headers): array
    {
        $map = [];
        foreach ($headers as $h) {
            if (str_contains($h, 'metafields.custom.faq1')) $map['faq1'] = $h;
            if (str_contains($h, 'metafields.custom.faq2')) $map['faq2'] = $h;
            if (str_contains($h, 'metafields.custom.faq3')) $map['faq3'] = $h;
            if (str_contains($h, 'metafields.custom.faq4')) $map['faq4'] = $h;
            if (str_contains($h, 'metafields.custom.faq5')) $map['faq5'] = $h;
            if (str_contains($h, 'metafields.custom.answer_1')) $map['answer1'] = $h;
            if (str_contains($h, 'metafields.custom.answer_2')) $map['answer2'] = $h;
            if (str_contains($h, 'metafields.custom.answer_3')) $map['answer3'] = $h;
            if (str_contains($h, 'metafields.custom.answer_4')) $map['answer4'] = $h;
            if (str_contains($h, 'metafields.custom.answer_5')) $map['answer5'] = $h;
            if (str_contains($h, 'metafields.custom.rating_value')) $map['rating_value'] = $h;
            if (str_contains($h, 'metafields.custom.review_count')) $map['review_count'] = $h;
            if (str_contains($h, 'metafields.custom.heading')) $map['heading'] = $h;
            if (str_contains($h, 'metafields.custom.custom_canonical')) $map['custom_canonical'] = $h;
        }

        return $map;
    }

    private function extractMetafields(array $data): array
    {
        $result = [];
        foreach ($this->metafieldCols as $key => $col) {
            $result[$key] = trim($data[$col] ?? '');
        }

        return $result;
    }

    private function createCategories(array $products): void
    {
        $categoryPaths = collect($products)
            ->pluck('category_path')
            ->filter()
            ->unique();

        // Clear existing categories and create fresh ones from product data
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Category::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $parentCategories = [];
        $position = 1;

        foreach ($categoryPaths as $path) {
            $parts = array_map('trim', explode('>', $path));
            $topLevel = $parts[0] ?? '';

            if (empty($topLevel) || isset($parentCategories[$topLevel])) continue;

            $parent = Category::create([
                'name' => $topLevel,
                'slug' => Str::slug($topLevel),
                'description' => "Shop {$topLevel}",
                'position' => $position++,
                'is_active' => true,
                'is_featured' => $position <= 7,
            ]);
            $parentCategories[$topLevel] = $parent;
        }

        // Create sub-categories
        foreach ($categoryPaths as $path) {
            $parts = array_map('trim', explode('>', $path));
            $topLevel = $parts[0] ?? '';
            $subLevel = $parts[1] ?? '';

            if (empty($subLevel)) continue;

            $parentId = $parentCategories[$topLevel]->id ?? null;
            if (!$parentId) continue;

            $key = $topLevel . '>' . $subLevel;
            if (isset($this->categoryMap[$key])) continue;

            $cat = Category::create([
                'parent_id' => $parentId,
                'name' => $subLevel,
                'slug' => Str::slug($subLevel . '-' . Str::random(4)),
                'description' => "Shop {$subLevel}",
                'position' => 1,
                'is_active' => true,
            ]);
            $this->categoryMap[$key] = $cat->id;
        }

        // Map full paths to deepest category
        foreach ($categoryPaths as $path) {
            $parts = array_map('trim', explode('>', $path));
            $topLevel = $parts[0] ?? '';
            $subLevel = $parts[1] ?? '';
            $key = $topLevel . '>' . $subLevel;

            if (!empty($subLevel) && isset($this->categoryMap[$key])) {
                $this->categoryMap[$path] = $this->categoryMap[$key];
            } else {
                $this->categoryMap[$path] = $parentCategories[$topLevel]->id ?? null;
            }
        }

        $this->info("Created " . count($parentCategories) . " parent categories, " . Category::whereNotNull('parent_id')->count() . " sub-categories");
    }

    private function setupBrands(array $products): void
    {
        $vendors = collect($products)->pluck('vendor')->filter()->unique();

        foreach ($vendors as $vendor) {
            Brand::firstOrCreate(
                ['slug' => Str::slug($vendor)],
                [
                    'name' => $vendor,
                    'slug' => Str::slug($vendor),
                    'description' => $vendor,
                    'is_active' => true,
                    'is_featured' => false,
                    'position' => Brand::max('position') + 1,
                ]
            );
        }
    }

    /**
     * @return bool true if newly created, false if updated existing
     */
    private function importProduct(array $data): bool
    {
        $categoryId = $this->categoryMap[$data['category_path']] ?? null;
        $brand = Brand::where('slug', Str::slug($data['vendor']))->first();

        $storeName = \App\Models\Setting::get('store_name', config('app.name'));
        $seoDesc = $data['seo_description'] ?: Str::limit(strip_tags($data['body']), 155);
        $seoTitle = $data['seo_title'] ?: $data['title'] . ' - Buy Online at ' . $storeName;

        $specs = array_filter([
            'Color' => $data['color'],
            'Material' => $data['material'],
            'Features' => $data['features'],
            'Power Source' => $data['power_source'],
        ]);

        // Build FAQs array from CSV metafields
        $faqs = $this->buildFaqs($data);

        // Parse tags into array
        $tags = array_values(array_filter(
            array_map('trim', explode(',', $data['tags'] ?? ''))
        ));

        // SEO data with canonical support
        $seoData = [
            'title' => $seoTitle,
            'description' => $seoDesc,
            'keywords' => strtolower($data['title']) . ', buy online, ' . strtolower($storeName),
        ];
        if (!empty($data['custom_canonical'])) {
            $seoData['canonical'] = $data['custom_canonical'];
        }
        if (!empty($data['heading'])) {
            $seoData['heading'] = $data['heading'];
        }

        $productData = [
            'brand_id' => $brand?->id,
            'category_id' => $categoryId,
            'name' => $data['title'],
            'short_description' => Str::limit(strip_tags($data['body']), 200),
            'description' => $data['body'],
            'mrp' => $data['compare_price'] > 0 ? $data['compare_price'] : $data['price'],
            'price' => $data['price'],
            'stock_quantity' => max($data['qty'], 50),
            'stock_status' => 'in_stock',
            'low_stock_threshold' => 5,
            'weight' => $data['weight'] / 1000,
            'weight_unit' => 'kg',
            'is_active' => $data['status'] === 'active',
            'is_taxable' => true,
            'tax_rate' => 18.00,
            'rating' => $data['rating_value'] ? (float) $data['rating_value'] : null,
            'review_count' => $data['review_count'] ? (int) $data['review_count'] : 0,
            'tags' => !empty($tags) ? $tags : null,
            'faqs' => !empty($faqs) ? $faqs : null,
            'seo_data' => $seoData,
            'specifications' => !empty($specs) ? $specs : null,
            'status' => 'approved',
            'published_at' => now(),
        ];

        // Update existing product or create new
        $existing = Product::where('slug', Str::slug($data['handle']))
            ->orWhere('sku', $data['sku'])
            ->first();

        if ($existing) {
            $existing->update($productData);
            return false;
        }

        $product = Product::create([
            'uuid' => Str::uuid(),
            'seller_id' => 1,
            'slug' => Str::slug($data['handle']),
            'sku' => $data['sku'],
            'is_featured' => false,
            ...$productData,
        ]);

        // Import images
        foreach ($data['images'] as $i => $img) {
            ProductImage::create([
                'product_id' => $product->id,
                'url' => $img['url'],
                'alt_text' => $img['alt'] ?: $data['title'],
                'position' => $img['position'],
                'is_primary' => $i === 0,
            ]);
        }

        return true;
    }

    private function buildFaqs(array $data): array
    {
        $faqs = [];
        for ($i = 1; $i <= 5; $i++) {
            $q = $data["faq{$i}"] ?? '';
            $a = $data["answer{$i}"] ?? '';
            if ($q && $a) {
                $faqs[] = ['q' => $q, 'a' => $a];
            }
        }

        return $faqs;
    }
}
