<?php

namespace Database\Seeders;

use App\Http\Middleware\CacheResponse;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Lists GRYT Multivitamin Tablets for Men and for Women (60 tablets each).
 *
 * - Content follows the live listings: "About this item" lines in
 *   short_description, Quill-style HTML description, the three product attributes.
 * - Images ship in database/seeders/assets/multivitamin-tablets/{men,women} and are
 *   copied to the public disk under products/, next to admin-uploaded images.
 * - Each product is created inside a transaction, so ProductObserver's afterCommit
 *   Shiprocket push runs once the images are attached and carries the main image.
 *
 * Create-only: a SKU that already exists (even trashed) is left untouched, so
 * running this again never overwrites prices or copy edited in admin since.
 *
 * Prod: php artisan tenants:seed --class=MultivitaminTabletsSeeder --tenants=gryt --force
 *
 * Don't run it against a local copy that still holds the live Shiprocket key: the
 * push would send local product ids to the live catalog, where those ids may belong
 * to other products.
 */
class MultivitaminTabletsSeeder extends Seeder
{
    /** MRP printed on the bottle label (incl. of all taxes). */
    private const MRP = 499;

    /** Selling price. Kept at MRP until a launch discount is decided. */
    private const PRICE = 499;

    public function run(): void
    {
        $brand = Brand::where('slug', 'gryt-health-labs')->firstOrFail();
        $category = Category::where('slug', 'supplements')->firstOrFail();
        $disk = Storage::disk('public');

        foreach ($this->listings() as $listing) {
            if (Product::withTrashed()->where('sku', $listing['sku'])->exists()) {
                $this->command?->warn("{$listing['sku']} already exists - left unchanged.");
                continue;
            }

            $files = glob(database_path("seeders/assets/multivitamin-tablets/{$listing['assets']}/*.jpg"));
            sort($files);

            DB::transaction(function () use ($listing, $brand, $category, $disk, $files) {
                $product = Product::create([
                    'sku'                 => $listing['sku'],
                    'name'                => $listing['name'],
                    'slug'                => $listing['slug'],
                    'brand_id'            => $brand->id,
                    'category_id'         => $category->id,
                    'short_description'   => $listing['short_description'],
                    'description'         => $listing['description'],
                    'mrp'                 => self::MRP,
                    'price'               => self::PRICE,
                    'stock_quantity'      => 100,
                    'low_stock_threshold' => 10,
                    'is_active'           => true,
                    'is_featured'         => false,
                    'is_new_arrival'      => true,
                    'is_taxable'          => true,
                    'attributes'          => [
                        'Flavor / Type' => 'Tablets',
                        'Net Quantity'  => '60 Tablets',
                        'Servings'      => '60 Servings',
                    ],
                    'faqs'                => $listing['faqs'],
                    'tags'                => $listing['tags'],
                    // The storefront reads seo_data; the admin form pre-fills from the columns.
                    'seo_data'            => [
                        'meta_title'       => $listing['meta_title'],
                        'meta_description' => $listing['meta_description'],
                    ],
                    'meta_title'          => $listing['meta_title'],
                    'meta_description'    => $listing['meta_description'],
                    'status'              => 'approved',
                    'published_at'        => now(),
                ]);

                $product->categories()->syncWithoutDetaching([$category->id]);

                foreach ($files as $i => $file) {
                    $path = sprintf('products/%s-%02d.jpg', $listing['slug'], $i + 1);
                    $disk->put($path, file_get_contents($file));

                    $product->images()->create([
                        'url'        => $path,
                        'alt_text'   => $listing['image_alts'][$i] ?? "{$listing['name']} - Image " . ($i + 1),
                        'position'   => $i,
                        'is_primary' => $i === 0,
                    ]);
                }
            });

            $this->command?->info("Listed {$listing['sku']} with " . count($files) . ' images.');
        }

        CacheResponse::bustAll();
    }

    private function listings(): array
    {
        return [
            [
                'sku'               => 'GRYT-MVM-MEN-60TAB',
                'name'              => 'Multivitamin Tablets for Men with Energy & Immunity Support',
                'slug'              => 'multivitamin-tablets-for-men',
                'assets'            => 'men',
                'short_description' => $this->aboutThisItem('Men'),
                'description'       => $this->description(
                    'Men',
                    'Fuel your everyday performance with <strong>GRYT Multivitamin Tablets for Men</strong>. Each tablet delivers a <strong>daily multivitamin and mineral complex</strong> made to support your <strong>energy, stamina, immunity and gut health</strong>, helping you stay active and ready for every challenge, from long workdays to your next training session.',
                    'Consult your physician before use if you are on medication or have a medical condition.',
                ),
                'faqs'              => $this->faqs(
                    'Men',
                    'Can I take it if I am on medication or have a medical condition?',
                    'Please consult your physician before use if you are taking any medication or have a medical condition.',
                ),
                'tags'              => ['multivitamin', 'multivitamin for men', 'multivitamin tablets', "men's health", 'immunity', 'energy', 'stamina', 'gut health'],
                'meta_title'        => 'Multivitamin Tablets for Men | Energy, Stamina & Immunity',
                'meta_description'  => 'GRYT Multivitamin Tablets for Men: a daily multivitamin and mineral complex for energy, stamina, immunity and gut health. 1 tablet a day, 60 tablets.',
                'image_alts'        => $this->imageAlts('Men', 'Man stretching at the gym with GRYT Multivitamin Tablets for Men'),
            ],
            [
                'sku'               => 'GRYT-MVM-WOMEN-60TAB',
                'name'              => 'Multivitamin Tablets for Women with Energy & Immunity Support',
                'slug'              => 'multivitamin-tablets-for-women',
                'assets'            => 'women',
                'short_description' => $this->aboutThisItem('Women'),
                'description'       => $this->description(
                    'Women',
                    'Fuel your everyday performance with <strong>GRYT Multivitamin Tablets for Women</strong>. Each tablet delivers a <strong>daily multivitamin and mineral complex</strong> made to support your <strong>energy, stamina, immunity and gut health</strong>, helping you stay active and ready for every challenge, from busy workdays to your next workout.',
                    'Consult your physician before use if you are pregnant or breastfeeding, on medication or have a medical condition.',
                ),
                'faqs'              => $this->faqs(
                    'Women',
                    'Can I take it during pregnancy or while breastfeeding?',
                    'Please consult your physician before use if you are pregnant or breastfeeding, taking any medication, or have a medical condition.',
                ),
                'tags'              => ['multivitamin', 'multivitamin for women', 'multivitamin tablets', "women's health", 'immunity', 'energy', 'stamina', 'gut health'],
                'meta_title'        => 'Multivitamin Tablets for Women | Energy, Stamina & Immunity',
                'meta_description'  => 'GRYT Multivitamin Tablets for Women: a daily multivitamin and mineral complex for energy, stamina, immunity and gut health. 1 tablet a day, 60 tablets.',
                'image_alts'        => $this->imageAlts('Women', 'Woman stretching at the gym with GRYT Multivitamin Tablets for Women'),
            ],
        ];
    }

    /**
     * One line per "About this item" bullet. The product page splits on newlines
     * and full stops, so no line may contain a full stop.
     */
    private function aboutThisItem(string $for): string
    {
        return implode("\n", [
            "Formulation: Daily Multivitamin & Mineral Complex for {$for}",
            'Strength: 1000 mg per serving',
            'Key Benefits: Supports Energy, Stamina, Immunity and Gut Health',
            'Daily Use: 1 tablet a day after a meal',
            'Net Quantity: 60 Tablets (60-day supply)',
            'Dietary Info: Vegetarian, Gluten Free and Non-GMO',
        ]);
    }

    /** Same section layout as the live listings: Benefits, Ingredients, How to Use, Specifications. */
    private function description(string $for, string $intro, string $physicianNote): string
    {
        $list = fn (array $items) => '<ol>' . implode('', array_map(
            fn ($label, $text) => is_int($label)
                ? "<li>{$text}</li>"
                : "<li><strong>{$label}:</strong> {$text}</li>",
            array_keys($items),
            $items,
        )) . '</ol>';

        return "<p>{$intro}</p>"
            . '<h2><strong>Benefits</strong></h2>' . $list([
                'Daily Energy'                     => 'Essential vitamins and minerals in one tablet to help you stay energetic through busy days.',
                'Stamina Support'                  => 'Supports stamina for workouts, sport and an active lifestyle.',
                'Immunity Support'                 => "Everyday micronutrient support for your body's natural defences.",
                'Gut Health'                       => 'Supports digestive wellness as part of your daily routine.',
                'Stress Support &amp; Mental Focus' => 'Helps you stay calm and focused, so you can find your calm and power your day.',
                'Fills Nutrition Gaps'             => 'A simple way to top up the vitamins and minerals a busy diet can miss.',
            ])
            . '<h2><strong>Ingredients</strong></h2>' . $list([
                'Multivitamin &amp; Mineral Complex' => 'A blend of essential vitamins and minerals, with % RDA calculated as per ICMR 2020 guidelines.',
                '1000 mg Per Serving'               => 'One tablet is one full daily serving.',
                'Clean Formula'                     => 'Vegetarian, gluten free and non-GMO.',
                'Full Composition'                  => 'The complete nutrient list with % RDA per tablet is printed on the bottle label.',
            ])
            . '<h3><strong>How to Use</strong></h3>' . $list([
                'Serving Size' => 'Take 1 tablet daily.',
                'Timing'       => 'Take it after a meal with a glass of water, or as directed by your physician.',
                'Consistency'  => 'Take it at the same time every day to make it part of your routine. Do not exceed the recommended daily dose.',
            ])
            . '<h3><strong>Specifications</strong></h3>' . $list([
                'Brand Name'             => 'GRYT Health Labs',
                'Product Classification' => 'Dietary Supplement (Not for Medicinal Use)',
                'Formulation'            => 'Multivitamin &amp; Mineral Complex',
                'Strength'               => '1000 mg per serving',
                'Form Factor'            => 'Oral Tablets',
                'Net Quantity'           => '60 Tablets',
                'Total Servings'         => '60 Servings (1 tablet per serving)',
                'Suitable For'           => "Adult {$for}",
                'Dietary Preference'     => 'Vegetarian, Gluten Free, Non-GMO',
                'Manufacturing Standard' => 'GMP and ISO Certified Facility',
                'Country of Origin'      => 'India',
                'Storage Instructions'   => 'Keep the container tightly closed. Store in a cool, dry place away from direct sunlight.',
            ])
            . '<h3><strong>Safety Information</strong></h3>' . $list([
                'Do not use if the seal is broken or missing.',
                'Do not exceed the recommended daily dose. In case of accidental overdose, consult your physician.',
                $physicianNote,
                'Keep out of reach of children.',
                'Not to be used as a substitute for a varied diet.',
                'Do not use if you are allergic or sensitive to any of its ingredients.',
            ])
            . '<p><em>Not for medicinal use. This product is not intended to diagnose, treat, cure or prevent any disease.</em></p>';
    }

    /** Product-specific FAQs; the FAQ section pads these up to six with the store-wide ones. */
    private function faqs(string $for, string $safetyQuestion, string $safetyAnswer): array
    {
        return [
            [
                'q' => "How do I take GRYT Multivitamin Tablets for {$for}?",
                'a' => 'Take one tablet a day after a meal with a glass of water, or as directed by your physician. Do not exceed the recommended daily dose.',
            ],
            [
                'q' => 'How long will one bottle last?',
                'a' => 'Each bottle contains 60 tablets. At one tablet a day, one bottle lasts 60 days, or about two months.',
            ],
            [
                'q' => 'Is it vegetarian and gluten free?',
                'a' => 'Yes. The tablets are vegetarian, gluten free and non-GMO.',
            ],
            [
                'q' => $safetyQuestion,
                'a' => $safetyAnswer . ' This is a dietary supplement, not a medicine, and is not intended to diagnose, treat, cure or prevent any disease.',
            ],
        ];
    }

    /** Alt text in the same order as the image files (01.jpg ... 10.jpg). */
    private function imageAlts(string $for, string $lifestyleAlt): array
    {
        return [
            "GRYT Multivitamin Tablets for {$for}, 60 tablet bottle",
            "GRYT Multivitamin Tablets for {$for} back label with usage, storage and safety information",
            $lifestyleAlt,
            "What's inside GRYT Multivitamin Tablets for {$for}: multivitamin and mineral complex, 1000 mg per serving",
            "Fuel your everyday performance with GRYT Multivitamin Tablets for {$for}",
            'Daily nutrient support, 60 tablets',
            'Stress support, daily energy and mental focus',
            'The GRYT difference: GRYT Multivitamin compared with others',
            'Supports energy, stamina, immunity and gut health',
            'Simple daily routine: one tablet with water after a meal',
        ];
    }
}
