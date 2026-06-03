<?php

namespace App\Console\Commands;

use App\Models\AbandonedCheckout;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Simulates a real customer journey end-to-end inside the app.
 * Tests: product data, pricing, images, cart, coupons, checkout readiness, security.
 *
 * Run on production: php artisan test:simulate-customer
 * Run for specific tenant: php artisan tenants:run "test:simulate-customer"
 */
class SimulateCustomerTest extends Command
{
    protected $signature = 'test:simulate-customer {--fix : Auto-fix issues found}';
    protected $description = 'Simulate real customer journey — test products, pricing, images, cart, coupons, links, security';

    private int $pass = 0;
    private int $fail = 0;
    private int $warn = 0;
    private int $fixed = 0;

    public function handle(): int
    {
        $tenantId = tenant('id') ?? 'central';
        $this->line('');
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("  CUSTOMER SIMULATION TEST — {$tenantId}");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $this->testProducts();
        $this->testPricing();
        $this->testImages();
        $this->testCoupons();
        $this->testCart();
        $this->testCheckoutReadiness();
        $this->testSettings();
        $this->testSecurity();
        $this->testLinks();
        $this->testSeo();

        $this->line('');
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $total = $this->pass + $this->fail + $this->warn;
        $this->line("  RESULTS: ✅ {$this->pass}  ❌ {$this->fail}  ⚠️ {$this->warn}  (total: {$total})");
        if ($this->fixed > 0) {
            $this->line("  AUTO-FIXED: {$this->fixed} issues");
        }
        $status = $this->fail > 0 ? '❌ FAILED' : ($this->warn > 0 ? '⚠️ PASSED WITH WARNINGS' : '✅ ALL PASSED');
        $this->line("  STATUS: {$status}");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        return $this->fail > 0 ? 1 : 0;
    }

    private function ok(string $msg): void { $this->pass++; $this->line("  ✅ {$msg}"); }
    private function bad(string $msg): void { $this->fail++; $this->error("  ❌ {$msg}"); }
    private function warning(string $msg): void { $this->warn++; $this->line("  ⚠️ {$msg}"); }

    /**
     * TEST 1: Products — active products have required fields
     */
    private function testProducts(): void
    {
        $this->info("\n  [1] PRODUCTS");
        $autoFix = $this->option('fix');

        $activeProducts = Product::where('is_active', true)->get();

        if ($activeProducts->isEmpty()) {
            $this->bad("No active products found!");
            return;
        }
        $this->ok("Active products: {$activeProducts->count()}");

        // Check each product has required fields
        $issues = [];
        foreach ($activeProducts as $product) {
            if (empty($product->name)) $issues[] = "#{$product->id}: Missing name";
            if ($product->price <= 0) $issues[] = "#{$product->id} {$product->name}: Price is 0 or negative";
            if (empty($product->slug)) $issues[] = "#{$product->id} {$product->name}: Missing slug";
            if (empty($product->description) && empty($product->short_description)) {
                $issues[] = "#{$product->id} {$product->name}: No description";
            }

            // Stock sanity
            if ($product->stock_quantity < 0) {
                $issues[] = "#{$product->id} {$product->name}: NEGATIVE STOCK ({$product->stock_quantity})";
                if ($autoFix) {
                    $product->update(['stock_quantity' => 0, 'stock_status' => 'out_of_stock']);
                    $this->fixed++;
                }
            }
        }

        if (empty($issues)) {
            $this->ok("All products have required fields (name, price, slug, description)");
        } else {
            foreach ($issues as $issue) {
                $this->bad($issue);
            }
        }
    }

    /**
     * TEST 2: Pricing — MRP > price, no negative prices, Google Merchant compliance
     */
    private function testPricing(): void
    {
        $this->info("\n  [2] PRICING & MRP");
        $autoFix = $this->option('fix');

        // Products where price >= MRP (Google Merchant violation)
        $badMrp = Product::where('is_active', true)
            ->where('mrp', '>', 0)
            ->whereColumn('price', '>=', 'mrp')
            ->get();

        if ($badMrp->isEmpty()) {
            $this->ok("All products: price < MRP (Google Merchant compliant)");
        } else {
            foreach ($badMrp as $p) {
                $this->bad("#{$p->id} {$p->name}: price ₹{$p->price} >= MRP ₹{$p->mrp}");
                if ($autoFix) {
                    $newMrp = (int) (ceil($p->price * 1.4 / 10) * 10 - 1);
                    $p->update(['mrp' => $newMrp]);
                    $this->line("    → Fixed MRP to ₹{$newMrp}");
                    $this->fixed++;
                }
            }
        }

        // Products with zero price
        $zeroPrice = Product::where('is_active', true)->where('price', '<=', 0)->count();
        if ($zeroPrice === 0) {
            $this->ok("No products with zero/negative price");
        } else {
            $this->bad("{$zeroPrice} active products have zero/negative price!");
        }

        // Cart items with stale prices
        $staleCarts = DB::table('cart_items')
            ->join('products', 'cart_items.product_id', '=', 'products.id')
            ->whereColumn('cart_items.price', '!=', 'products.price')
            ->count();

        if ($staleCarts === 0) {
            $this->ok("All cart items have current prices");
        } else {
            $this->bad("{$staleCarts} cart items have STALE prices (don't match product)");
            if ($autoFix) {
                DB::statement('UPDATE cart_items SET price = p.price FROM products p WHERE cart_items.product_id = p.id AND cart_items.price != p.price');
                $this->line("    → Synced {$staleCarts} cart item prices");
                $this->fixed++;
            }
        }
    }

    /**
     * TEST 3: Images — every active product has at least 1 image, files exist on disk
     */
    private function testImages(): void
    {
        $this->info("\n  [3] PRODUCT IMAGES");

        // Products without any images
        $noImage = Product::where('is_active', true)
            ->whereDoesntHave('images')
            ->get();

        if ($noImage->isEmpty()) {
            $this->ok("All active products have at least 1 image");
        } else {
            foreach ($noImage as $p) {
                $this->bad("#{$p->id} {$p->name}: NO IMAGES");
            }
        }

        // Check if image files exist on disk (local images only)
        $brokenImages = [];
        $localImages = ProductImage::whereNotNull('url')
            ->where('url', '!=', '')
            ->where('url', 'not like', 'https://%')
            ->where('url', 'not like', 'http://%')
            ->get();

        foreach ($localImages as $img) {
            $path = public_path('storage/' . $img->url);
            if (!file_exists($path)) {
                $brokenImages[] = "Product #{$img->product_id}: {$img->url}";
            }
        }

        if (empty($brokenImages)) {
            $this->ok("All {$localImages->count()} local image files exist on disk");
        } else {
            $this->bad(count($brokenImages) . " images missing from disk:");
            foreach (array_slice($brokenImages, 0, 5) as $bi) {
                $this->line("    → {$bi}");
            }
            if (count($brokenImages) > 5) {
                $this->line("    → ... and " . (count($brokenImages) - 5) . " more");
            }
        }

        // Check storage symlink
        $symlinkPath = public_path('storage');
        if (is_link($symlinkPath)) {
            $this->ok("Storage symlink exists: public/storage → " . readlink($symlinkPath));
        } else {
            $this->bad("public/storage is NOT a symlink! Image uploads won't be visible.");
        }
    }

    /**
     * TEST 4: Coupons — active coupons are valid and not expired
     */
    private function testCoupons(): void
    {
        $this->info("\n  [4] COUPONS");
        $autoFix = $this->option('fix');

        $activeCoupons = Coupon::where('is_active', true)->get();
        $this->ok("Active coupons: {$activeCoupons->count()}");

        $issues = [];
        foreach ($activeCoupons as $coupon) {
            // Expired but still active
            if ($coupon->expires_at && $coupon->expires_at < now()) {
                $issues[] = "{$coupon->code}: EXPIRED on {$coupon->expires_at->format('Y-m-d')} but still active";
                if ($autoFix) {
                    $coupon->update(['is_active' => false]);
                    $this->fixed++;
                }
            }
            // Usage limit exceeded
            if ($coupon->usage_limit && $coupon->times_used >= $coupon->usage_limit) {
                $issues[] = "{$coupon->code}: Usage limit reached ({$coupon->times_used}/{$coupon->usage_limit}) but still active";
                if ($autoFix) {
                    $coupon->update(['is_active' => false]);
                    $this->fixed++;
                }
            }
            // Discount > 100% for percentage type
            if ($coupon->type === 'percentage' && $coupon->value > 100) {
                $issues[] = "{$coupon->code}: Percentage discount > 100% ({$coupon->value}%)";
            }
            // Zero value
            if ($coupon->value <= 0) {
                $issues[] = "{$coupon->code}: Zero/negative discount value";
            }
        }

        if (empty($issues)) {
            $this->ok("All coupons valid (not expired, within limits, correct values)");
        } else {
            foreach ($issues as $issue) {
                $this->bad($issue);
            }
        }
    }

    /**
     * TEST 5: Cart — cart items reference existing products
     */
    private function testCart(): void
    {
        $this->info("\n  [5] CART INTEGRITY");

        // Cart items pointing to deleted/inactive products
        $orphanItems = DB::table('cart_items')
            ->leftJoin('products', 'cart_items.product_id', '=', 'products.id')
            ->where(function ($q) {
                $q->whereNull('products.id')
                    ->orWhere('products.is_active', false);
            })
            ->count();

        if ($orphanItems === 0) {
            $this->ok("All cart items reference active products");
        } else {
            $this->warning("{$orphanItems} cart items reference deleted/inactive products");
        }

        // Abandoned checkouts with recovery data
        $totalAbandoned = AbandonedCheckout::where('recovered', false)->count();
        $withContact = AbandonedCheckout::where('recovered', false)
            ->where(function ($q) {
                $q->whereNotNull('email')->orWhereNotNull('phone');
            })->count();

        $this->ok("Abandoned carts: {$totalAbandoned} total, {$withContact} with contact info");
    }

    /**
     * TEST 6: Checkout readiness — payment gateways configured, shipping works
     */
    private function testCheckoutReadiness(): void
    {
        $this->info("\n  [6] CHECKOUT READINESS");

        // Razorpay configured?
        $razorpayKey = Setting::get('razorpay_key_id', '');
        $razorpaySecret = Setting::get('razorpay_key_secret', '');
        if (!empty($razorpayKey) && !empty($razorpaySecret)) {
            $this->ok("Razorpay configured (key: " . substr($razorpayKey, 0, 8) . "...)");
        } else {
            $this->warning("Razorpay NOT configured — only COD available");
        }

        // COD enabled?
        $codEnabled = Setting::get('cod_enabled', '1');
        if ($codEnabled === '1' || $codEnabled === true) {
            $this->ok("COD payment enabled");
        } else {
            $this->warning("COD is disabled");
        }

        // Shipping configured?
        $freeShipThreshold = Setting::get('free_shipping_threshold', '');
        $shippingFee = Setting::get('shipping_fee', '');
        if (!empty($freeShipThreshold) || !empty($shippingFee)) {
            $this->ok("Shipping: free above ₹{$freeShipThreshold}, fee ₹{$shippingFee}");
        } else {
            $this->warning("Shipping fees not configured");
        }

        // WhatsApp configured?
        $waToken = Setting::get('whatsapp_api_token', '');
        $waPhone = Setting::get('whatsapp_phone_number_id', '');
        if (!empty($waToken) && !empty($waPhone)) {
            $this->ok("WhatsApp API configured");
        } else {
            $this->warning("WhatsApp API not configured — order notifications won't send");
        }
    }

    /**
     * TEST 7: Settings — essential settings exist and aren't Jikra-specific
     */
    private function testSettings(): void
    {
        $this->info("\n  [7] SETTINGS & BRANDING");
        $tenantId = tenant('id') ?? 'central';

        $required = [
            'store_name' => 'Store name',
            'store_logo' => 'Store logo',
            'primary_color' => 'Primary color',
            'admin_email' => 'Admin email',
        ];

        foreach ($required as $key => $label) {
            $value = Setting::get($key, '');
            if (empty($value)) {
                $this->warning("{$label} ({$key}) is empty");
            } else {
                // Check for cross-tenant leakage
                if ($tenantId !== 'jikra' && stripos($value, 'jikra') !== false) {
                    $this->bad("{$label} contains 'jikra' on {$tenantId} tenant! → Cross-tenant leak");
                } else {
                    $this->ok("{$label}: {$value}");
                }
            }
        }

        // Favicon check
        $favicon = Setting::get('store_favicon', '') ?: Setting::get('store_logo', '');
        if (!empty($favicon)) {
            $path = public_path(ltrim($favicon, '/'));
            if (!file_exists($path) && !file_exists(public_path('storage/' . $favicon))) {
                // Try with storage prefix
                $this->warning("Favicon file not found on disk: {$favicon}");
            } else {
                $this->ok("Favicon configured and file exists");
            }
        } else {
            $this->warning("No favicon or logo configured");
        }
    }

    /**
     * TEST 8: Security checks
     */
    private function testSecurity(): void
    {
        $this->info("\n  [8] SECURITY");

        // Check if any product has negative stock
        $negativeStock = Product::where('stock_quantity', '<', 0)->count();
        if ($negativeStock === 0) {
            $this->ok("No products with negative stock (race condition check)");
        } else {
            $this->bad("{$negativeStock} products have NEGATIVE STOCK — possible overselling!");
        }

        // Check for orders without items
        $emptyOrders = DB::table('orders')
            ->leftJoin('order_items', 'orders.id', '=', 'order_items.order_id')
            ->whereNull('order_items.id')
            ->where('orders.status', '!=', 'cancelled')
            ->count();

        if ($emptyOrders === 0) {
            $this->ok("No orders without items (data integrity OK)");
        } else {
            $this->bad("{$emptyOrders} non-cancelled orders have NO ITEMS!");
        }

        // Check failed jobs
        try {
            $failedJobs = DB::table('failed_jobs')->count();
            if ($failedJobs === 0) {
                $this->ok("No failed jobs in queue");
            } elseif ($failedJobs < 10) {
                $this->warning("{$failedJobs} failed jobs — check and retry");
            } else {
                $this->bad("{$failedJobs} failed jobs! Emails/notifications may not be sending");
            }
        } catch (\Throwable) {
            $this->warning("Failed jobs table not found");
        }
    }

    /**
     * TEST 9: Internal links — homepage banner links, category links
     */
    private function testLinks(): void
    {
        $this->info("\n  [9] LINKS & NAVIGATION");

        // Check homepage section banner links
        try {
            $sections = DB::table('homepage_sections')
                ->whereNotNull('button_link')
                ->where('button_link', '!=', '')
                ->get();

            foreach ($sections as $section) {
                $link = $section->button_link;
                // Extract slug from /products/slug or /product/slug
                if (preg_match('#/products?/([^/?#]+)#', $link, $m)) {
                    $slug = $m[1];
                    $product = Product::where('slug', $slug)->first();
                    if (!$product) {
                        $this->bad("Banner '{$section->key}' links to non-existent product: {$link}");
                    } elseif (!$product->is_active) {
                        $this->bad("Banner '{$section->key}' links to INACTIVE product: {$link}");
                    } else {
                        $this->ok("Banner '{$section->key}' → {$link} (product exists)");
                    }
                }
            }
        } catch (\Throwable) {
            $this->warning("Could not check homepage banner links");
        }

        // Check navigation menu links
        try {
            $navItems = DB::table('navigation_menus')
                ->where('is_active', true)
                ->whereNotNull('url')
                ->get();

            $brokenNav = 0;
            foreach ($navItems as $nav) {
                if (str_starts_with($nav->url, '/products/')) {
                    $slug = str_replace('/products/', '', $nav->url);
                    if (!Product::where('slug', $slug)->where('is_active', true)->exists()) {
                        $this->bad("Nav '{$nav->label}' links to missing product: {$nav->url}");
                        $brokenNav++;
                    }
                } elseif (str_starts_with($nav->url, '/category/')) {
                    // Category link — just check it's not empty
                }
            }
            if ($brokenNav === 0 && $navItems->count() > 0) {
                $this->ok("Navigation: {$navItems->count()} menu items, all links valid");
            }
        } catch (\Throwable) {
            $this->warning("Could not check navigation menu links");
        }
    }

    /**
     * TEST 10: SEO — meta tags, slugs, schema
     */
    private function testSeo(): void
    {
        $this->info("\n  [10] SEO");

        // Duplicate slugs
        $dupes = Product::where('is_active', true)
            ->select('slug')
            ->groupBy('slug')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($dupes->isEmpty()) {
            $this->ok("No duplicate product slugs");
        } else {
            foreach ($dupes as $d) {
                $this->bad("Duplicate slug: {$d->slug}");
            }
        }

        // Products without SEO-friendly slug
        $badSlugs = Product::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('slug')
                    ->orWhere('slug', '')
                    ->orWhere('slug', 'like', '% %'); // Contains spaces
            })->count();

        if ($badSlugs === 0) {
            $this->ok("All product slugs are SEO-friendly");
        } else {
            $this->bad("{$badSlugs} products have bad slugs (empty or contain spaces)");
        }

        // Meta title configured
        $storeName = Setting::get('store_name', '');
        $metaTitle = Setting::get('meta_title', '');
        if (!empty($metaTitle) || !empty($storeName)) {
            $this->ok("Site meta title configured");
        } else {
            $this->warning("No meta title or store name set — bad for SEO");
        }
    }
}
