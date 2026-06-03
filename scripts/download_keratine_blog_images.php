<?php
/**
 * Download all Keratine blog images from Shopify CDN to local storage,
 * then update blog_posts table with local paths.
 *
 * Run on production: cd /var/www/jikra && sudo -u www-data php scripts/download_keratine_blog_images.php
 */
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\BlogPost;

$tenant = Tenant::find('keratine');
if (!$tenant) { echo "Tenant not found!\n"; exit(1); }
tenancy()->initialize($tenant);
echo "Initialized keratine tenant\n";

$imgDir = public_path('tenants/keratine/blog-images');
if (!is_dir($imgDir)) {
    mkdir($imgDir, 0755, true);
    echo "Created: {$imgDir}\n";
}

$baseUrl = '/tenants/keratine/blog-images';
$downloaded = 0;
$failed = 0;
$urlMap = []; // old URL => new local path

/**
 * Download a single image URL and return local path, or false on failure.
 */
function downloadImage(string $url, string $imgDir, string $baseUrl, array &$urlMap, int &$downloaded, int &$failed): ?string
{
    // Already downloaded in this run
    if (isset($urlMap[$url])) return $urlMap[$url];

    // Build local filename from URL
    $parsed = parse_url($url);
    $pathPart = basename($parsed['path'] ?? 'image.png');
    // Remove query string from filename, keep extension
    $pathPart = preg_replace('/\?.*/', '', $pathPart);
    $ext = pathinfo($pathPart, PATHINFO_EXTENSION) ?: 'png';
    $name = pathinfo($pathPart, PATHINFO_FILENAME);
    // Sanitize
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
    $localFile = $name . '.' . $ext;

    // Handle duplicates
    if (file_exists("{$imgDir}/{$localFile}")) {
        $localPath = "{$baseUrl}/{$localFile}";
        $urlMap[$url] = $localPath;
        return $localPath;
    }

    // Ensure HTTPS
    $fetchUrl = preg_replace('/^http:/', 'https:', $url);

    $ch = curl_init($fetchUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
    ]);
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && strlen($data) > 100) {
        file_put_contents("{$imgDir}/{$localFile}", $data);
        $localPath = "{$baseUrl}/{$localFile}";
        $urlMap[$url] = $localPath;
        $downloaded++;
        return $localPath;
    }

    $failed++;
    echo "  FAIL ({$httpCode}): {$fetchUrl}\n";
    return null;
}

$posts = BlogPost::all();
echo "Processing " . $posts->count() . " blog posts...\n\n";

foreach ($posts as $i => $post) {
    $num = $i + 1;
    echo "[{$num}] {$post->slug}\n";

    // 1. Download featured image
    if ($post->featured_image && preg_match('#(keratine\.in/cdn|cdn\.shopify\.com)#', $post->featured_image)) {
        $newPath = downloadImage($post->featured_image, $imgDir, $baseUrl, $urlMap, $downloaded, $failed);
        if ($newPath) {
            $post->featured_image = $newPath;
            echo "  featured: OK\n";
        }
    }

    // 2. Replace all image URLs in content
    $content = $post->content;
    $changed = false;

    // Match src="https://cdn.shopify.com/..." and src="http://keratine.in/cdn/..."
    if (preg_match_all('/src="(https?:\/\/(?:cdn\.shopify\.com|keratine\.in\/cdn)[^"]+)"/', $content, $matches)) {
        foreach ($matches[1] as $oldUrl) {
            $newPath = downloadImage($oldUrl, $imgDir, $baseUrl, $urlMap, $downloaded, $failed);
            if ($newPath) {
                $content = str_replace($oldUrl, $newPath, $content);
                $changed = true;
            }
        }
        if ($changed) {
            $post->content = $content;
            echo "  body: " . count($matches[1]) . " images replaced\n";
        }
    }

    $post->save();
}

echo "\n=== DOWNLOAD COMPLETE ===\n";
echo "Downloaded: {$downloaded}\n";
echo "Failed: {$failed}\n";
echo "Total images on disk: " . count(glob("{$imgDir}/*")) . "\n";
