<?php
/**
 * Import scraped Keratine pages + policies into the keratine tenant DB.
 * Run on production: cd /var/www/jikra && sudo -u www-data php scripts/import_keratine_pages.php
 */
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

$jsonFile = '/tmp/keratine_pages_scraped.json';
if (!file_exists($jsonFile)) { echo "JSON not found: {$jsonFile}\n"; exit(1); }

$pages = json_decode(file_get_contents($jsonFile), true);
echo "Loaded " . count($pages) . " pages from JSON\n\n";

$imported = 0;
$updated = 0;
$skipped = 0;

foreach ($pages as $i => $page) {
    $num = $i + 1;
    $slug = $page['slug'];
    $title = $page['title'] ?? ucwords(str_replace('-', ' ', $slug));
    // Clean title — remove " | Keratine Professional" suffix
    $title = preg_replace('/\s*\|\s*Keratine Professional.*$/', '', $title);

    $content = $page['content'] ?? '';
    // Rewrite internal links
    $content = preg_replace('#https?://keratine\.in/products/([^"\s<]+)#', '/products/$1', $content);
    $content = preg_replace('#https?://keratine\.in/collections/([^"\s<]+)#', '/categories', $content);
    $content = preg_replace('#https?://keratine\.in/pages/([^"\s<]+)#', '/pages/$1', $content);
    $content = preg_replace('#https?://keratine\.in/?(["\s<])#', '/$1', $content);

    $seoDesc = $page['seo_description'] ?? Str::limit(strip_tags($content), 160);
    $seoData = [
        'title' => $title,
        'description' => $seoDesc,
        'keywords' => strtolower($title) . ', keratine professional, hair care',
    ];

    $existing = DB::table('pages')->where('slug', $slug)->first();

    if ($existing) {
        // Only update if we have new content
        if (strlen($content) > strlen($existing->content ?? '')) {
            DB::table('pages')->where('slug', $slug)->update([
                'title' => $title,
                'content' => $content,
                'seo_data' => json_encode($seoData),
                'updated_at' => now(),
            ]);
            echo "[{$num}] UPDATED: {$slug}\n";
            $updated++;
        } else {
            echo "[{$num}] SKIP (exists): {$slug}\n";
            $skipped++;
        }
        continue;
    }

    DB::table('pages')->insert([
        'title' => $title,
        'slug' => $slug,
        'content' => $content,
        'seo_data' => json_encode($seoData),
        'is_published' => true,
        'published_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $imported++;
    echo "[{$num}] OK: {$title}\n";
}

echo "\n=== IMPORT COMPLETE ===\n";
echo "Imported: {$imported}\n";
echo "Updated: {$updated}\n";
echo "Skipped: {$skipped}\n";
echo "Total pages: " . DB::table('pages')->count() . "\n";
