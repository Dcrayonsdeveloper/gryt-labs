<?php
/**
 * Import full product descriptions and blog content from Urban India SQL dump
 * Run on PRODUCTION: cd /var/www/jikra && sudo -u www-data php scripts/_tmp_import_full_content.php
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;
use App\Models\Product;
use App\Models\BlogPost;
use App\Models\Setting;

$tenant = Tenant::find('urbanindia');
if (!$tenant) die("Tenant not found\n");
tenancy()->initialize($tenant);

$sqlFile = __DIR__ . '/_tmp_urbanindia.sql';
if (!file_exists($sqlFile)) die("SQL file not found at: $sqlFile\n");
$sql = file_get_contents($sqlFile);

// ============================================================
// 1. PRODUCT DESCRIPTIONS
// ============================================================
echo "--- Product Descriptions ---\n";

// Map old IDs to Jikra slugs
$productMap = [
    7 => 'the-bliss-plan',
    8 => 'the-family-cycle',
    11 => 'happy-period-box',
    12 => 'pouch-instant-kit',
];

// Parse products using a state machine approach
$productSection = '';
$inProducts = false;
foreach (explode("\n", $sql) as $line) {
    if (str_contains($line, "INSERT INTO `products`")) {
        $inProducts = true;
    }
    if ($inProducts) {
        $productSection .= $line . "\n";
        if (str_ends_with(trim($line), ';')) {
            $inProducts = false;
        }
    }
}

// For each product, extract description using position-based parsing
foreach ($productMap as $oldId => $slug) {
    // Find "($oldId, '" in the product section
    $marker = "($oldId, '";
    $pos = strpos($productSection, $marker);
    if ($pos === false) {
        echo "  $slug: NOT FOUND (ID $oldId)\n";
        continue;
    }

    // Skip past the name field
    $nameStart = $pos + strlen($marker);
    $nameEnd = findUnescapedQuote($productSection, $nameStart);
    $name = substr($productSection, $nameStart, $nameEnd - $nameStart);

    // Skip ", '" to get to description
    $descStart = $nameEnd + 4; // skip "', '"
    $descEnd = findUnescapedQuote($productSection, $descStart);
    $desc = substr($productSection, $descStart, $descEnd - $descStart);

    // Unescape
    $desc = str_replace("\\'", "'", $desc);
    $desc = str_replace("\\\"", "\"", $desc);
    $desc = str_replace("\\n", "\n", $desc);
    $desc = str_replace("\\r", "\r", $desc);

    // Fix UTF-8 encoding issues (source is latin1)
    $desc = mb_convert_encoding($desc, 'UTF-8', 'UTF-8');

    $product = Product::where('slug', $slug)->first();
    if ($product) {
        $product->description = $desc;
        $product->save();
        echo "  $slug: " . strlen($desc) . " chars\n";
    }
}

// ============================================================
// 2. BLOG CONTENT
// ============================================================
echo "\n--- Blog Content ---\n";

// Collect all blog INSERT lines
$blogLines = '';
$inBlogs = false;
foreach (explode("\n", $sql) as $line) {
    if (str_contains($line, "INSERT INTO `blogs`")) {
        $inBlogs = true;
    }
    if ($inBlogs) {
        $blogLines .= $line . "\n";
        if (str_ends_with(trim($line), ';')) {
            $inBlogs = false;
        }
    }
}

// Map source blog slugs to their full content
// We need to find each blog entry by its slug field
$blogSlugs = BlogPost::pluck('slug')->toArray();

$updated = 0;
foreach ($blogSlugs as $slug) {
    // Find the slug in the SQL
    $slugMarker = "'" . $slug . "'";
    $slugPos = strpos($blogLines, $slugMarker);
    if ($slugPos === false) continue;

    // Walk backward from slug to find the content field
    // Blog format: (id, 'title', 'content', 'seo_title', 'meta_title', 'meta_description', 'slug', ...)
    // slug is the 7th field, content is the 3rd field

    // Find the start of this INSERT row - search backward for "(\d+, '"
    $rowStart = strrpos(substr($blogLines, 0, $slugPos), '(');
    if ($rowStart === false) continue;

    // Parse fields: skip ID and title, get content
    $afterParen = $rowStart + 1;

    // Skip ID
    $commaPos = strpos($blogLines, ',', $afterParen);
    if ($commaPos === false) continue;

    // Skip to title field start (after ", '")
    $titleStart = $commaPos + 3;
    $titleEnd = findUnescapedQuote($blogLines, $titleStart);

    // Skip to content field start (after "', '")
    $contentStart = $titleEnd + 4;
    $contentEnd = findUnescapedQuote($blogLines, $contentStart);
    $content = substr($blogLines, $contentStart, $contentEnd - $contentStart);

    // Unescape
    $content = str_replace("\\'", "'", $content);
    $content = str_replace("\\\"", "\"", $content);
    $content = str_replace("\\n", "\n", $content);
    $content = str_replace("\\r", "\r", $content);

    if (strlen($content) > 100) {
        $blog = BlogPost::where('slug', $slug)->first();
        if ($blog) {
            $blog->content = $content;
            $blog->save();
            $updated++;
            echo "  $slug: " . strlen($content) . " chars\n";
        }
    }
}
echo "  Updated: $updated blogs\n";

echo "\n=== DONE ===\n";

/**
 * Find the next unescaped single quote in a string
 */
function findUnescapedQuote(string $str, int $offset): int
{
    $len = strlen($str);
    for ($i = $offset; $i < $len; $i++) {
        if ($str[$i] === "'" && ($i === 0 || $str[$i - 1] !== '\\')) {
            // Check for escaped backslash before quote: \\'
            if ($i >= 2 && $str[$i - 1] === '\\' && $str[$i - 2] === '\\') {
                return $i; // This is actually unescaped: \\' means literal backslash + end quote
            }
            if ($str[$i - 1] !== '\\') {
                return $i;
            }
        }
    }
    return $len;
}
