<?php
/**
 * Import full product descriptions and blog content - v2 (title-based matching)
 * Run on PRODUCTION: cd /var/www/jikra && sudo -u www-data php scripts/_tmp_import_full_content2.php
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;
use App\Models\Product;
use App\Models\BlogPost;

$tenant = Tenant::find('urbanindia');
if (!$tenant) die("Tenant not found\n");
tenancy()->initialize($tenant);

$sqlFile = __DIR__ . '/_tmp_urbanindia.sql';
if (!file_exists($sqlFile)) die("SQL file not found\n");
$sql = file_get_contents($sqlFile);

// ============================================================
// Parse all product rows from SQL
// ============================================================
echo "--- Product Descriptions ---\n";

// Collect product INSERT block
$productBlock = extractInsertBlock($sql, 'products');
if ($productBlock) {
    $rows = parseInsertRows($productBlock);
    // products columns: id, name, description, price, main_image, created_at, seo_title, meta_title, meta_description, slug, product_excerpt
    foreach ($rows as $row) {
        if (count($row) < 11) continue;
        $oldSlug = trim($row[9]);
        $desc = $row[2];
        $name = $row[1];

        // Try to find product by matching slug
        $product = Product::where('slug', $oldSlug)->first();
        if (!$product) {
            // Try fuzzy slug match (remove trailing spaces etc)
            $product = Product::where('slug', 'LIKE', '%' . substr($oldSlug, 0, 10) . '%')->first();
        }
        if (!$product) {
            // Try name match
            $product = Product::where('name', 'LIKE', '%' . trim(substr($name, 0, 15)) . '%')->first();
        }

        if ($product) {
            if (empty($product->description) || strlen($product->description) < 100) {
                $product->description = $desc;
                $product->save();
                echo "  {$product->slug}: " . strlen($desc) . " chars (matched from '$oldSlug')\n";
            } else {
                echo "  {$product->slug}: already has description, skipping\n";
            }
        } else {
            echo "  NOT MATCHED: $oldSlug ($name)\n";
        }
    }
}

// ============================================================
// Parse all blog rows from SQL
// ============================================================
echo "\n--- Blog Content ---\n";

$blogBlock = extractInsertBlock($sql, 'blogs');
if ($blogBlock) {
    $rows = parseInsertRows($blogBlock);
    // blogs columns: id, title, content, seo_title, meta_title, meta_description, slug, tags, image, created_at
    $updated = 0;
    $notFound = [];
    foreach ($rows as $row) {
        if (count($row) < 10) continue;
        $title = $row[1];
        $content = $row[2];
        $oldSlug = trim($row[6]);

        if (strlen($content) < 100) continue;

        // Try exact slug match first
        $blog = BlogPost::where('slug', $oldSlug)->first();

        // Try title match
        if (!$blog) {
            $blog = BlogPost::where('title', $title)->first();
        }

        // Try partial title match
        if (!$blog) {
            $shortTitle = substr($title, 0, 40);
            $blog = BlogPost::where('title', 'LIKE', $shortTitle . '%')->first();
        }

        if ($blog) {
            if (strlen($blog->content) < 200) {
                $blog->content = $content;
                $blog->save();
                $updated++;
                echo "  {$blog->slug}: " . strlen($content) . " chars\n";
            }
        } else {
            $notFound[] = $oldSlug;
        }
    }
    echo "  Updated: $updated blogs\n";
    if ($notFound) {
        echo "  Not matched (" . count($notFound) . "): " . implode(', ', array_slice($notFound, 0, 10)) . "\n";
    }
}

echo "\n=== DONE ===\n";
echo "Products with descriptions: " . Product::whereNotNull('description')->where('description', '!=', '')->count() . "/" . Product::count() . "\n";
echo "Blogs with content > 200 chars: " . BlogPost::whereRaw("LENGTH(content) > 200")->count() . "/" . BlogPost::count() . "\n";

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function extractInsertBlock(string $sql, string $table): string
{
    $result = '';
    $inBlock = false;
    foreach (explode("\n", $sql) as $line) {
        if (str_contains($line, "INSERT INTO `$table`")) {
            $inBlock = true;
        }
        if ($inBlock) {
            $result .= $line . "\n";
            if (str_ends_with(trim($line), ';')) {
                // Don't stop if we hit another INSERT INTO same table
                if (str_contains($line, "INSERT INTO `$table`")) {
                    continue;
                }
                $inBlock = false;
            }
        }
    }
    return $result;
}

function parseInsertRows(string $block): array
{
    $rows = [];
    $len = strlen($block);

    // Find "VALUES" keyword
    $valPos = stripos($block, 'VALUES');
    if ($valPos === false) return $rows;

    $pos = $valPos + 6;

    while ($pos < $len) {
        // Find next '('
        $parenPos = strpos($block, '(', $pos);
        if ($parenPos === false) break;

        // Parse this row
        $pos = $parenPos + 1;
        $fields = [];
        $currentField = '';
        $inQuote = false;

        while ($pos < $len) {
            $ch = $block[$pos];

            if ($inQuote) {
                if ($ch === '\\' && $pos + 1 < $len) {
                    // Escaped character
                    $next = $block[$pos + 1];
                    if ($next === "'") {
                        $currentField .= "'";
                        $pos += 2;
                        continue;
                    } elseif ($next === "\\") {
                        $currentField .= "\\";
                        $pos += 2;
                        continue;
                    } elseif ($next === "n") {
                        $currentField .= "\n";
                        $pos += 2;
                        continue;
                    } elseif ($next === "r") {
                        $currentField .= "\r";
                        $pos += 2;
                        continue;
                    } else {
                        $currentField .= $ch;
                        $pos++;
                        continue;
                    }
                }
                if ($ch === "'" && $pos + 1 < $len && $block[$pos + 1] === "'") {
                    // Doubled single quote
                    $currentField .= "'";
                    $pos += 2;
                    continue;
                }
                if ($ch === "'") {
                    $inQuote = false;
                    $pos++;
                    continue;
                }
                $currentField .= $ch;
                $pos++;
            } else {
                if ($ch === "'") {
                    $inQuote = true;
                    $pos++;
                    continue;
                }
                if ($ch === ',') {
                    $fields[] = trim($currentField);
                    $currentField = '';
                    $pos++;
                    continue;
                }
                if ($ch === ')') {
                    $fields[] = trim($currentField);
                    $rows[] = $fields;
                    $pos++;
                    break;
                }
                if ($ch === 'N' && substr($block, $pos, 4) === 'NULL') {
                    $currentField = '';
                    $pos += 4;
                    continue;
                }
                if (!ctype_space($ch)) {
                    $currentField .= $ch;
                }
                $pos++;
            }
        }
    }

    return $rows;
}
