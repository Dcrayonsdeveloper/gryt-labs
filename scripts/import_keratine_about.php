<?php
/**
 * Import Keratine About Us page into tenant DB.
 * Run on production: cd /var/www/jikra && sudo -u www-data php scripts/import_keratine_about.php
 */
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;

$tenant = Tenant::find('keratine');
if (!$tenant) { echo "Tenant not found!\n"; exit(1); }
tenancy()->initialize($tenant);
echo "Initialized keratine tenant\n";

$content = <<<'HTML'
<div class="about-us">
<h3>Get to Know Us</h3>
<p>Keratine Professional is your trusted partner that provides a broad portfolio of beauty and personal care ingredients, focusing on consumer-centered sensory experiences. As we like to say, we add life to products.</p>
<p>As part of the Keratine Professional, we use advanced technologies and supply chain capabilities to develop specialized solutions for cosmetics and personal care.</p>
<p>Founded in 2016 as a trading firm, our company has undergone several transformations from Trading to Manufacturing, From Manufacturing to automated Manufacturing.</p>

<h2>Very Personal Hair</h2>
<p>Every woman wants exceptional hair, but the solution is not the same for everyone. Hair type, scalp concerns, and internal and external factors are some of the variables that can affect the health of the hair. It requires personal attention and expertise to resolve all of these factors into one very individual, yet perfect, head of hair. Keratine Professional creates innovative products and bespoke rituals for flawless results.</p>

<h2>An alliance between scientists &amp; hair stylists</h2>
<p>Keratine Professional was also the first line to endow the hairdresser with a more intimate role in hair care prescription, supported by the scientists of France. Iconic products continue to benefit from the unique Keratine Professional blend of expertise and innovation with a deeply sensorial, personalized experience.</p>
</div>
HTML;

$seoData = [
    'title' => 'About Us - Keratine Professional',
    'description' => 'Keratine Professional is your trusted partner in beauty and personal care. Founded in 2016, we create innovative hair care products with advanced technologies.',
    'keywords' => 'keratine professional, about us, hair care, beauty, personal care',
];

$existing = DB::table('pages')->where('slug', 'about-us')->first();

if ($existing) {
    DB::table('pages')->where('slug', 'about-us')->update([
        'content' => $content,
        'seo_data' => json_encode($seoData),
        'updated_at' => now(),
    ]);
    echo "Updated existing About Us page (ID: {$existing->id})\n";
} else {
    $id = DB::table('pages')->insertGetId([
        'title' => 'About Us',
        'slug' => 'about-us',
        'content' => $content,
        'seo_data' => json_encode($seoData),
        'is_published' => true,
        'published_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "Created About Us page (ID: {$id})\n";
}

echo "Done.\n";
