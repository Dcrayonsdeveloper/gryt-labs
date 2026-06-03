<?php
/**
 * Import scraped Keratine blog posts into the keratine tenant DB.
 * Run on production: cd /var/www/jikra && sudo -u www-data php scripts/import_keratine_blogs.php
 */
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\BlogPost;
use Illuminate\Support\Str;

$tenant = Tenant::find('keratine');
if (!$tenant) { echo "Tenant 'keratine' not found!\n"; exit(1); }

tenancy()->initialize($tenant);
echo "Initialized keratine tenant\n";

// Get admin user for author_id
$admin = \App\Models\User::where('role', 'admin')->first();
if (!$admin) {
    echo "No admin user found — blogs will have null author_id\n";
}
$authorId = $admin?->id;

// Load scraped JSON
$jsonFile = '/tmp/keratine_blogs_scraped.json';
if (!file_exists($jsonFile)) {
    echo "JSON file not found: {$jsonFile}\n";
    exit(1);
}

$blogs = json_decode(file_get_contents($jsonFile), true);
echo "Loaded " . count($blogs) . " blogs from JSON\n\n";

$imported = 0;
$skipped = 0;

foreach ($blogs as $i => $blog) {
    $num = $i + 1;
    $slug = $blog['slug'];

    // Skip if already exists
    if (BlogPost::where('slug', $slug)->exists()) {
        echo "[{$num}] SKIP (exists): {$slug}\n";
        $skipped++;
        continue;
    }

    $title = $blog['title'] ?? $slug;
    $content = $blog['content'] ?? '';
    $excerpt = $blog['seo_description'] ?? Str::limit(strip_tags($content), 200);
    $featuredImage = $blog['featured_image'] ?? null;
    $publishedAt = isset($blog['published_at']) ? date('Y-m-d H:i:s', strtotime($blog['published_at'])) : now();
    $tags = $blog['tags'] ?? [];

    // Rewrite internal keratine.in links to relative paths
    $content = preg_replace('#https?://keratine\.in/products/([^"\s<]+)#', '/products/$1', $content);
    $content = preg_replace('#https?://keratine\.in/collections/([^"\s<]+)#', '/categories', $content);
    $content = preg_replace('#https?://keratine\.in/blogs/([^"\s<]+)#', '/blog/$1', $content);
    $content = preg_replace('#https?://keratine\.in/?(["\s<])#', '/$1', $content);

    BlogPost::create([
        'title' => $title,
        'slug' => $slug,
        'excerpt' => $excerpt,
        'content' => $content,
        'featured_image' => $featuredImage,
        'category' => 'Hair Care',
        'tags' => $tags ?: ['hair care', 'keratine'],
        'author_id' => $authorId,
        'is_published' => true,
        'published_at' => $publishedAt,
        'seo_data' => [
            'title' => $title,
            'description' => $blog['seo_description'] ?? $excerpt,
            'keywords' => strtolower($title) . ', keratine professional, hair care',
        ],
        'view_count' => 0,
    ]);

    $imported++;
    echo "[{$num}] OK: {$title}\n";
}

echo "\n=== IMPORT COMPLETE ===\n";
echo "Imported: {$imported}\n";
echo "Skipped: {$skipped}\n";
echo "Total blog_posts: " . BlogPost::count() . "\n";
