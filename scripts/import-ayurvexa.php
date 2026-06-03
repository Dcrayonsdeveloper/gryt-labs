<?php
/**
 * Import Ayurvexa products from scraped data.
 * Run: cd /var/www/jikra && sudo php scripts/import-ayurvexa.php
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tenant = App\Models\Tenant::find('ayurvexa');
tenancy()->initialize($tenant);

// 1. Create Categories
$categories = [
    ['name' => 'Liver Care', 'slug' => 'liver-care', 'description' => 'Advanced liver detox and support supplements', 'position' => 1],
    ['name' => 'Skin Radiance', 'slug' => 'skin-radiance', 'description' => 'Supplements for glowing, healthy skin from within', 'position' => 2],
    ['name' => 'Healthy Heart', 'slug' => 'healthy-heart', 'description' => 'Heart health and cardiovascular wellness supplements', 'position' => 3],
    ['name' => 'General Wellness', 'slug' => 'general-wellness', 'description' => 'Daily wellness and immunity boosting supplements', 'position' => 4],
    ['name' => 'High On Energy', 'slug' => 'high-on-energy', 'description' => 'Natural energy and stamina boosting supplements', 'position' => 5],
];

$catMap = [];
foreach ($categories as $cat) {
    $c = App\Models\Category::firstOrCreate(['slug' => $cat['slug']], array_merge($cat, ['is_active' => true, 'is_featured' => true, 'level' => 0, 'path' => $cat['slug']]));
    $catMap[$cat['name']] = $c->id;
    echo "Category: {$c->name} (ID: {$c->id})\n";
}

// 2. Create Products
$products = [
    [
        'name' => 'Ayurvexa Liver Support – Advanced Liver Detox Supplement',
        'slug' => 'ayurvexa-liver-support',
        'category' => 'Liver Care',
        'price' => 779, 'mrp' => 999,
        'short_description' => '11-ingredient liver detox formula combining clinical antioxidants (NAC and L-Glutathione) with Ayurvedic herbs to rejuvenate liver function.',
        'description' => '<h3>Advanced Liver Detox Formula</h3><p>Ayurvexa Liver Support combines clinical antioxidants like <strong>NAC (N-Acetyl L-Cysteine)</strong> and <strong>L-Glutathione</strong> with powerful Ayurvedic herbs including Turmeric, Ginger, and Milk Thistle.</p><h4>Key Ingredients</h4><ul><li><strong>Milk Thistle</strong> – Protects liver cells from toxin damage</li><li><strong>NAC</strong> – Boosts glutathione, the body\'s master antioxidant</li><li><strong>L-Glutathione</strong> – Direct antioxidant support</li><li><strong>Turmeric (Curcumin)</strong> – Anti-inflammatory support</li><li><strong>Dandelion Extract</strong> – Supports bile production</li><li><strong>Beetroot Extract</strong> – Rich in nitrates for detox</li></ul><h4>Benefits</h4><ul><li>Supports healthy liver function</li><li>Improves digestion and gut comfort</li><li>Boosts metabolism naturally</li><li>Helps manage toxin overload</li></ul><h4>Dosage</h4><p>Take 2 tablets daily after meals with water. Recommended 90-day duration.</p><p><em>100% Vegetarian | Non-GMO | GMP-certified</em></p>',
        'sku' => 'AYV-LIVER-01', 'stock' => 500, 'rating' => 4.2,
        'images' => ['liver-support/1.jpeg', 'liver-support/2.jpeg', 'liver-support/3.jpeg', 'liver-support/4.jpeg'],
    ],
    [
        'name' => 'Ayurvexa Skin Sculpt – Skin Radiance Supplement',
        'slug' => 'ayurvexa-skin-sculpt',
        'category' => 'Skin Radiance',
        'price' => 479, 'mrp' => 699,
        'short_description' => 'Advanced 5-in-1 formula with Collagen, Glutathione, Hyaluronic Acid, Vitamin C & E for radiant, youthful skin.',
        'description' => '<h3>5-in-1 Skin Radiance Formula</h3><p>Transform your complexion from within with this advanced supplement targeting skin elasticity and cellular health.</p><h4>Key Ingredients</h4><ul><li><strong>Collagen Type-1</strong> – Maintains firmness, reduces wrinkles</li><li><strong>L-Glutathione</strong> – Master antioxidant</li><li><strong>Hyaluronic Acid</strong> – Deep moisture for plump skin</li><li><strong>Vitamin C</strong> – Boosts collagen production</li><li><strong>Vitamin E</strong> – Environmental damage protection</li></ul><h4>Dosage</h4><p>1 tablet daily after breakfast or lunch. Use 3-6 months for best results.</p>',
        'sku' => 'AYV-SKIN-01', 'stock' => 500, 'rating' => 4.5,
        'images' => ['skin-sculpt/1.jpeg', 'skin-sculpt/2.jpeg', 'skin-sculpt/3.jpeg', 'skin-sculpt/4.jpeg'],
    ],
    [
        'name' => 'Ayurvexa Spirulina Bliss – Spirulina For Skin Health',
        'slug' => 'ayurvexa-spirulina-bliss',
        'category' => 'General Wellness',
        'price' => 379, 'mrp' => 549,
        'short_description' => 'High-potency spirulina with 60%+ protein, all 9 essential amino acids. Boosts immunity, metabolism and skin health.',
        'description' => '<h3>Pure Spirulina Superfood</h3><p>High-potency spirulina protein supplement to boost metabolism, enhance immunity, and support skin health.</p><h4>Key Nutrients</h4><ul><li><strong>60%+ Protein</strong> – All 9 essential amino acids</li><li><strong>Phycocyanin</strong> – Potent antioxidant</li><li><strong>Vitamin A, E & B-complex</strong> – Skin and hair health</li><li><strong>Iron & B-vitamins</strong> – Combat fatigue</li></ul><h4>Dosage</h4><p>2 tablets daily, morning or afternoon. Combine with lemon water for iron absorption.</p><p><em>100% Pure Spirulina | Zero fillers | Lab tested</em></p>',
        'sku' => 'AYV-SPIRU-01', 'stock' => 500, 'rating' => 4.3,
        'images' => ['spirulina-bliss/1.jpeg', 'spirulina-bliss/2.jpeg', 'spirulina-bliss/3.jpeg', 'spirulina-bliss/4.jpeg'],
    ],
    [
        'name' => 'CoQ10Life – CoQ10 Tablets for Heart Health',
        'slug' => 'coq10life-heart-health',
        'category' => 'Healthy Heart',
        'price' => 879, 'mrp' => 1199,
        'short_description' => 'Comprehensive cellular energy booster with CoQ10, Omega-3, L-Carnitine, and Lycopene for heart health.',
        'description' => '<h3>Comprehensive Heart Health Formula</h3><p>CoQ10Life combines Coenzyme Q10 with amino acids and antioxidants for heart function and energy.</p><h4>Key Ingredients</h4><ul><li><strong>Coenzyme Q10</strong> – Cellular energy production</li><li><strong>Omega-3</strong> – Heart and brain health</li><li><strong>L-Carnitine</strong> – Fat metabolism</li><li><strong>L-Taurine</strong> – Cardiovascular support</li><li><strong>Lycopene & Selenium</strong> – Antioxidant defense</li></ul><h4>Dosage</h4><p>1 tablet daily with lunch. Recommended 3-6 months.</p><p><em>100% Vegetarian | Free from heavy metals</em></p>',
        'sku' => 'AYV-COQ10-01', 'stock' => 500, 'rating' => 4.1,
        'images' => ['coq10life/1.jpeg', 'coq10life/2.jpeg', 'coq10life/3.jpeg', 'coq10life/4.jpeg'],
    ],
    [
        'name' => 'Energize Q – Natural Stamina Booster',
        'slug' => 'energize-q-stamina-booster',
        'category' => 'High On Energy',
        'price' => 639, 'mrp' => 899,
        'short_description' => 'Premium Ayurvedic stamina booster with Shilajit, Ashwagandha, Swarna Bhasma, and 10+ herbs.',
        'description' => '<h3>Premium Ayurvedic Energy Formula</h3><p>Rare Ayurvedic ingredients for sustained energy without stimulant crashes.</p><h4>Key Ingredients</h4><ul><li><strong>Shilajit</strong> – Fulvic acid for stamina</li><li><strong>Ashwagandha</strong> – Adaptogenic stress reduction</li><li><strong>Swarna Bhasma</strong> – Traditional vitality booster</li><li><strong>Kauncha Beej & Black Musli</strong> – Physical strength</li><li><strong>Gokshura & Saffron</strong> – Antioxidant support</li></ul><h4>Dosage</h4><p>1-2 capsules daily with warm milk before bedtime or post-workout.</p><p><em>100% Vegetarian | Pure herbal extracts</em></p>',
        'sku' => 'AYV-ENRG-01', 'stock' => 500, 'rating' => 3.7,
        'images' => ['energize-q/1.jpg'],
    ],
];

$imgBase = 'tenants/ayurvexa/products/';

foreach ($products as $p) {
    $product = App\Models\Product::firstOrCreate(
        ['slug' => $p['slug']],
        [
            'name' => $p['name'],
            'category_id' => $catMap[$p['category']],
            'short_description' => $p['short_description'],
            'description' => $p['description'],
            'sku' => $p['sku'],
            'price' => $p['price'],
            'mrp' => $p['mrp'],
            'stock_quantity' => $p['stock'],
            'stock_status' => 'in_stock',
            'is_active' => true,
            'is_featured' => true,
            'is_taxable' => true,
            'rating' => $p['rating'],
            'review_count' => 0,
            'status' => 'approved',
            'published_at' => now(),
        ]
    );

    foreach ($p['images'] as $i => $img) {
        App\Models\ProductImage::firstOrCreate(
            ['product_id' => $product->id, 'url' => $imgBase . $img],
            ['alt_text' => $p['name'], 'position' => $i + 1, 'is_primary' => $i === 0]
        );
    }

    echo "Product: {$product->name} (ID: {$product->id}, Rs{$p['price']}, " . count($p['images']) . " images)\n";
}

// Update homepage carousel
$carousel = App\Models\PageSection::where('type', 'product_carousel')->first();
if ($carousel) {
    $carousel->update(['settings' => json_encode(['heading' => 'Our Products', 'source' => 'featured', 'count' => 12])]);
    echo "Homepage carousel updated\n";
}

tenancy()->end();
echo "\nDONE!\n";
