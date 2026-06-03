<?php
/**
 * Fix ALL Urban India gaps in one script.
 * DO NOT restart php-fpm or clear cache.
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

echo "========================================\n";
echo "  URBAN INDIA — FIX ALL GAPS\n";
echo "========================================\n\n";

// ──────────────────────────────────────────
// 1. CHECK PRODUCT STOCK (stock_quantity)
// ──────────────────────────────────────────
echo "--- 1. PRODUCT STOCK ---\n";
$products = Product::all();
foreach ($products as $p) {
    $sq = $p->stock_quantity;
    $ss = $p->stock_status;
    if ($sq === null || $sq == 0) {
        $p->stock_quantity = 100;
        $p->stock_status = 'in_stock';
        $p->save();
        echo "  {$p->slug}: stock_quantity → 100\n";
    } else {
        echo "  {$p->slug}: stock_quantity={$sq}, status={$ss} (OK)\n";
    }
}
echo "\n";

// ──────────────────────────────────────────
// 2. FIX BLOG is_published
// ──────────────────────────────────────────
echo "--- 2. BLOG is_published ---\n";
$unpublished = BlogPost::where('is_published', false)->orWhereNull('is_published')->count();
if ($unpublished > 0) {
    BlogPost::where('is_published', false)->orWhereNull('is_published')->update(['is_published' => true]);
    echo "  Set is_published=true on {$unpublished} blogs\n";
} else {
    echo "  All blogs already published\n";
}
// Ensure all have published_at
$nullPub = BlogPost::whereNull('published_at')->count();
if ($nullPub > 0) {
    DB::statement("UPDATE blog_posts SET published_at = created_at WHERE published_at IS NULL");
    echo "  Set published_at on {$nullPub} blogs\n";
}
echo "\n";

// ──────────────────────────────────────────
// 3. IMPORT MISSING BLOGS (source has 37, prod has 32)
// ──────────────────────────────────────────
echo "--- 3. MISSING BLOGS ---\n";
$sqlFile = __DIR__ . '/_tmp_urbanindia.sql';
if (!file_exists($sqlFile)) {
    echo "  SKIP: SQL file not found at $sqlFile\n\n";
} else {
    $sql = file_get_contents($sqlFile);
    $existingTitles = BlogPost::pluck('title')->map(fn($t) => strtolower(trim($t)))->toArray();
    echo "  Existing blogs: " . count($existingTitles) . "\n";

    // Parse all blog rows from SQL using the proven parser
    $allSourceBlogs = [];
    $offset = 0;
    while (($insPos = strpos($sql, "INSERT INTO `blogs`", $offset)) !== false) {
        $valPos = strpos($sql, 'VALUES', $insPos);
        if ($valPos === false) break;
        $p = strpos($sql, '(', $valPos);
        if ($p === false) break;

        $row = parseRow($sql, $p);
        if ($row && count($row) >= 7) {
            $allSourceBlogs[] = $row;
        }
        $semiPos = strpos($sql, ';', $p);
        $offset = $semiPos !== false ? $semiPos + 1 : $p + 1000;
    }

    echo "  Source blogs parsed: " . count($allSourceBlogs) . "\n";

    $imported = 0;
    $skipped = [];
    foreach ($allSourceBlogs as $row) {
        // columns: id, title, content, seo_title, meta_title, meta_description, slug, tags, image, created_at
        $title = trim($row[1] ?? '');
        if (!$title) continue;
        $titleLower = strtolower($title);

        if (in_array($titleLower, $existingTitles)) continue;

        $content = $row[2] ?? '';
        $content = @iconv('UTF-8', 'UTF-8//IGNORE', $content);
        if (strlen($content) < 100) {
            $skipped[] = $title . " (content too short: " . strlen($content) . ")";
            continue;
        }

        $slug = \Illuminate\Support\Str::slug($title);
        if (BlogPost::where('slug', $slug)->exists()) {
            $slug .= '-' . rand(100, 999);
        }

        $tags = $row[7] ?? '';
        $tagArray = $tags ? array_values(array_filter(array_map('trim', explode(',', $tags)))) : [];

        $image = $row[8] ?? '';
        $featuredImage = '';
        if ($image) {
            $featuredImage = 'blogs/urbanindia/' . basename($image);
        }

        $createdAt = $row[9] ?? now()->toDateTimeString();

        BlogPost::create([
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'featured_image' => $featuredImage,
            'tags' => $tagArray,
            'is_published' => true,
            'published_at' => $createdAt,
            'created_at' => $createdAt,
        ]);
        $existingTitles[] = $titleLower;
        $imported++;
        echo "  IMPORTED: {$slug}\n";
    }
    if ($skipped) {
        echo "  Skipped:\n";
        foreach ($skipped as $s) echo "    - $s\n";
    }
    echo "  New blogs imported: {$imported}\n";
    echo "  Total blogs now: " . BlogPost::count() . "\n\n";
}

// ──────────────────────────────────────────
// 4. FIX COUPONS (type + value)
// ──────────────────────────────────────────
echo "--- 4. COUPONS ---\n";
$coupons = DB::table('coupons')->get();
foreach ($coupons as $c) {
    $needsFix = false;
    $updates = [];
    if (empty($c->type)) {
        $updates['type'] = 'percentage';
        $needsFix = true;
    }
    if (empty($c->value) || $c->value == 0) {
        $updates['value'] = 10;
        $needsFix = true;
    }
    if ($needsFix) {
        DB::table('coupons')->where('id', $c->id)->update($updates);
        echo "  Coupon {$c->code}: set type=percentage, value=10\n";
    } else {
        echo "  Coupon {$c->code}: type={$c->type}, value={$c->value} (OK)\n";
    }
}
echo "\n";

// ──────────────────────────────────────────
// 5. INSTAGRAM REEL SHORTCODES
// ──────────────────────────────────────────
echo "--- 5. INSTAGRAM REELS ---\n";
$shortcodes = 'DTaD5HhEfxj,DVQsRjjjlnY,DVLaAOeCCfy,DU-tSZcD-JZ,DUspUW8kpjG,DUiWl4UEdr6,DUX6UF1kXio';

$existing = Setting::where('key', 'collab_reel_shortcodes')->first();
if ($existing && !empty($existing->value) && strlen($existing->value) > 10) {
    echo "  collab_reel_shortcodes already set: {$existing->value}\n";
} else {
    Setting::updateOrCreate(['key' => 'collab_reel_shortcodes'], ['value' => $shortcodes]);
    echo "  Set collab_reel_shortcodes: {$shortcodes}\n";
}

$igHandle = Setting::where('key', 'instagram_handle')->first();
if (!$igHandle || empty($igHandle->value)) {
    Setting::updateOrCreate(['key' => 'instagram_handle'], ['value' => 'happyperiod_box']);
    echo "  Set instagram_handle: happyperiod_box\n";
} else {
    echo "  instagram_handle: {$igHandle->value}\n";
}
echo "\n";

// ──────────────────────────────────────────
// 6. META PIXEL SYNC
// ──────────────────────────────────────────
echo "--- 6. META PIXEL ---\n";
$fbPixel = Setting::where('key', 'facebook_pixel_id')->first();
$metaPixel = Setting::where('key', 'meta_pixel_id')->first();
if ($fbPixel && $fbPixel->value && (!$metaPixel || empty($metaPixel->value))) {
    Setting::updateOrCreate(['key' => 'meta_pixel_id'], ['value' => $fbPixel->value]);
    echo "  Copied facebook_pixel_id → meta_pixel_id: {$fbPixel->value}\n";
} elseif ($metaPixel && $metaPixel->value) {
    echo "  meta_pixel_id already set: {$metaPixel->value}\n";
} else {
    echo "  No facebook_pixel_id found\n";
}
echo "\n";

// ──────────────────────────────────────────
// FINAL SUMMARY
// ──────────────────────────────────────────
echo "========================================\n";
echo "  FINAL STATE\n";
echo "========================================\n";
echo "Products: " . Product::count() . " (in_stock: " . Product::where('stock_status', 'in_stock')->count() . ")\n";
echo "Blogs: " . BlogPost::count() . " (published: " . BlogPost::where('is_published', true)->count() . ", with images: " . BlogPost::whereNotNull('featured_image')->where('featured_image', '!=', '')->count() . ")\n";
echo "Reviews: " . DB::table('reviews')->count() . "\n";
echo "Coupons: " . DB::table('coupons')->count() . " (with value: " . DB::table('coupons')->where('value', '>', 0)->count() . ")\n";
echo "Testimonials: " . DB::table('testimonials')->count() . "\n";
echo "Categories: " . DB::table('categories')->count() . "\n";
echo "Pages: " . DB::table('pages')->count() . "\n";
echo "Homepage sections: " . DB::table('homepage_sections')->count() . "\n";
echo "Navigation menus: " . DB::table('navigation_menus')->count() . "\n";
echo "Settings: " . Setting::count() . "\n";

echo "\n=== REMAINING GAPS (need client input) ===\n";
echo "- Razorpay API keys: Admin → Settings → Payments\n";
echo "- Shipping provider: Admin → Settings → Shipping\n";
echo "- WhatsApp Business API keys: Admin → Settings → Integrations\n";
echo "- Instagram API token: Admin → Settings → Integrations\n";
echo "- Meta CAPI access token: Admin → Settings → Integrations\n";

// ──────────────────────────────────────────
// HELPER
// ──────────────────────────────────────────
function parseRow(string $sql, int $start): ?array
{
    $len = strlen($sql);
    $pos = $start + 1;
    $fields = [];
    $current = '';
    $inQuote = false;

    while ($pos < $len) {
        $ch = $sql[$pos];
        if ($inQuote) {
            if ($ch === '\\' && $pos + 1 < $len) {
                $next = $sql[$pos + 1];
                if ($next === "'") { $current .= "'"; $pos += 2; continue; }
                if ($next === "\\") { $current .= "\\"; $pos += 2; continue; }
                if ($next === "n") { $current .= "\n"; $pos += 2; continue; }
                if ($next === "r") { $current .= "\r"; $pos += 2; continue; }
                if ($next === '"') { $current .= '"'; $pos += 2; continue; }
                $current .= $ch; $pos++; continue;
            }
            if ($ch === "'" && $pos + 1 < $len && $sql[$pos + 1] === "'") {
                $current .= "'"; $pos += 2; continue;
            }
            if ($ch === "'") { $inQuote = false; $pos++; continue; }
            $current .= $ch; $pos++;
        } else {
            if ($ch === "'") { $inQuote = true; $pos++; continue; }
            if ($ch === ',') { $fields[] = trim($current); $current = ''; $pos++; continue; }
            if ($ch === ')') { $fields[] = trim($current); return $fields; }
            if ($ch === 'N' && substr($sql, $pos, 4) === 'NULL') { $current = ''; $pos += 4; continue; }
            if (!ctype_space($ch)) $current .= $ch;
            $pos++;
        }
    }
    return null;
}
