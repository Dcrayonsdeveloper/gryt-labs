<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Resets the catalog to the 5 GRYT Health Labs products.
 *
 * - Soft-deletes every existing (non-trashed) product so the storefront and
 *   admin list only show the 5 below.
 * - Stores Flavor / Type, Net Quantity and Servings as product "attributes",
 *   which renders them as editable fields on the admin product edit page.
 *
 * Re-runnable: brand, category, attributes and products are matched by slug/sku.
 */
class GrytCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Brand --------------------------------------------------------
        $brand = Brand::firstOrCreate(
            ['slug' => 'gryt-health-labs'],
            ['name' => 'GRYT Health Labs', 'is_active' => true, 'is_featured' => true],
        );

        // 2. Category -----------------------------------------------------
        $category = Category::firstOrCreate(
            ['slug' => 'supplements'],
            ['name' => 'Supplements', 'is_active' => true, 'is_featured' => true, 'position' => 1],
        );

        // 3. Editable attributes (shown on the admin product form) --------
        // Keys here MUST match the keys used in each product's `attributes`.
        foreach (['Flavor / Type', 'Net Quantity', 'Servings'] as $attrName) {
            Attribute::firstOrCreate(
                ['name' => $attrName],
                ['type' => 'text', 'is_filterable' => true, 'is_visible' => true],
            );
        }

        // 4. The 5 products ----------------------------------------------
        $products = [
            [
                'sku'      => 'GRYT-WHEY-VAN-1KG',
                'name'     => 'Whey Protein Concentrate',
                'flavor'   => 'Vanilla',
                'quantity' => '1 Kg',
                'servings' => '30 Servings',
                'mrp'      => 2999, 'price' => 1999,
            ],
            [
                'sku'      => 'GRYT-CREA-100G',
                'name'     => 'Creatine Monohydrate',
                'flavor'   => 'Unflavoured',
                'quantity' => '100 g',
                'servings' => '33 Servings',
                'mrp'      => 999, 'price' => 699,
            ],
            [
                'sku'      => 'GRYT-PRE-GA-200G',
                'name'     => 'Pre Workout',
                'flavor'   => 'Green Apple',
                'quantity' => '200 g',
                'servings' => '20 Servings',
                'mrp'      => 1499, 'price' => 1099,
            ],
            [
                'sku'      => 'GRYT-ASH-60CAP',
                'name'     => 'Ashwagandha Capsules',
                'flavor'   => 'Capsules',
                'quantity' => '60 Capsules',
                'servings' => '30 Servings',
                'mrp'      => 799, 'price' => 549,
            ],
            [
                'sku'      => 'GRYT-FLAX-60SG',
                'name'     => 'Flaxseed Oil Omega 3-6-9',
                'flavor'   => 'Softgel Capsules',
                'quantity' => '60 Capsules',
                'servings' => '30 Servings',
                'mrp'      => 899, 'price' => 649,
            ],
        ];

        $keepSkus = array_column($products, 'sku');

        // 5. Remove every other product (soft delete) --------------------
        Product::whereNotIn('sku', $keepSkus)->get()->each->delete();

        // 6. Create / update the 5 ---------------------------------------
        foreach ($products as $i => $p) {
            $product = Product::updateOrCreate(
                ['sku' => $p['sku']],
                [
                    'name'              => $p['name'],
                    'slug'              => Str::slug($p['name']),
                    'brand_id'          => $brand->id,
                    'category_id'       => $category->id,
                    'short_description' => "{$p['name']} — {$p['flavor']}, {$p['quantity']} ({$p['servings']}).",
                    'mrp'               => $p['mrp'],
                    'price'             => $p['price'],
                    'stock_quantity'    => 100,
                    'low_stock_threshold' => 10,
                    'is_active'         => true,
                    'is_featured'       => $i < 3,
                    'is_new_arrival'    => true,
                    'is_taxable'        => true,
                    'attributes'        => [
                        'Flavor / Type' => $p['flavor'],
                        'Net Quantity'  => $p['quantity'],
                        'Servings'      => $p['servings'],
                    ],
                    'status'            => 'approved',
                    'published_at'      => now(),
                ],
            );

            // Keep the many-to-many category pivot in sync with the primary category.
            $product->categories()->syncWithoutDetaching([$category->id]);
        }

        $this->command?->info('GRYT catalog seeded: '.count($products).' products, others soft-deleted.');
    }
}
