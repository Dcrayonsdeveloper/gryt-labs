<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Health check endpoint — ping from Uptime Robot / monitoring
Route::get('/health', function () {
    $checks = [];

    // Database
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (\Throwable) {
        $checks['database'] = 'down';
    }

    // Cache (Redis)
    try {
        \Illuminate\Support\Facades\Cache::put('health_check', true, 10);
        $checks['cache'] = \Illuminate\Support\Facades\Cache::get('health_check') ? 'ok' : 'down';
    } catch (\Throwable) {
        $checks['cache'] = 'down';
    }

    // Queue (check failed jobs count)
    try {
        $failedCount = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
        $checks['queue'] = $failedCount < 50 ? 'ok' : "failing ({$failedCount} failed jobs)";
    } catch (\Throwable) {
        $checks['queue'] = 'unknown';
    }

    // Disk space
    $freeBytes = disk_free_space('/');
    $freeMB = $freeBytes ? round($freeBytes / 1024 / 1024) : 0;
    $checks['disk'] = $freeMB > 500 ? "ok ({$freeMB}MB free)" : "low ({$freeMB}MB free)";

    $allOk = !in_array('down', $checks);
    return response()->json([
        'status' => $allOk ? 'healthy' : 'degraded',
        'checks' => $checks,
        'timestamp' => now()->toIso8601String(),
    ], $allOk ? 200 : 503);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// CSRF Token Refresh (for long-lived POS sessions)
Route::get('/csrf-token', fn () => response()->json(['token' => csrf_token()]))->name('csrf-token');

// WhatsApp Webhook
Route::get('/webhook/whatsapp', [App\Http\Controllers\WhatsAppWebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [App\Http\Controllers\WhatsAppWebhookController::class, 'handle'])
    ->middleware(['throttle:120,1', \App\Http\Middleware\VerifyMetaWebhookSignature::class]);
Route::post('/webhook/delhivery', [App\Http\Controllers\DelhiveryWebhookController::class, 'handle'])->middleware('throttle:120,1');
Route::post('/webhook/bluedart', [App\Http\Controllers\BlueDartWebhookController::class, 'handle'])->middleware('throttle:120,1');
Route::post('/webhook/shiprocket', [App\Http\Controllers\ShiprocketWebhookController::class, 'handle'])->middleware('throttle:120,1');
// /webhook/shipping-updates → moved to routes/shiprocket_checkout.php (ShiprocketCheckout\WebhookController)

// Dynamic robots.txt (per-tenant sitemap URL)
Route::get('/robots.txt', function () {
    $content = "User-agent: *\nDisallow: /admin/\nDisallow: /seller/\nDisallow: /pos/\nDisallow: /cart\nDisallow: /checkout\nDisallow: /account/\nDisallow: /api/\n\nSitemap: " . url('/sitemap.xml') . "\n";
    return response($content, 200)->header('Content-Type', 'text/plain');
});

// XML Sitemap
Route::get('/sitemap.xml', [App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap-pages.xml', [App\Http\Controllers\SitemapController::class, 'pages']);
Route::get('/sitemap-products.xml', [App\Http\Controllers\SitemapController::class, 'products']);
Route::get('/sitemap-categories.xml', [App\Http\Controllers\SitemapController::class, 'categories']);
Route::get('/sitemap-brands.xml', [App\Http\Controllers\SitemapController::class, 'brands']);
Route::get('/sitemap-blog.xml', [App\Http\Controllers\SitemapController::class, 'blog']);

// Facebook Catalog Feed
Route::get('/feeds/facebook-catalog.xml', App\Http\Controllers\FacebookCatalogController::class)->name('facebook.catalog');
Route::get('/feeds/google-merchant.xml', App\Http\Controllers\GoogleMerchantController::class)->name('google.merchant');

// PWA Routes (served via Laravel when nginx doesn't serve static files through symlinks)
Route::get('/offline', fn () => view('offline'))->name('offline');
Route::get('/manifest.json', function () {
    $storeName = \App\Models\Setting::get('store_name', config('app.name'));
    $tagline = \App\Models\Setting::get('site_tagline', 'Shop smart');
    $color = \App\Models\Setting::get('primary_color', '#205258');
    $favicon = \App\Models\Setting::get('store_favicon', '');
    $faviconUrl = $favicon ? '/' . $favicon : '/images/icons/favicon.png';
    $logo = '/' . \App\Models\Setting::get('store_logo', 'images/logo.png');
    return response()->json([
        'name' => $storeName . ' - ' . $tagline,
        'short_name' => $storeName,
        'description' => $tagline,
        'start_url' => '/', 'display' => 'standalone', 'orientation' => 'portrait',
        'background_color' => '#ffffff', 'theme_color' => $color,
        'categories' => ['shopping'], 'lang' => config('app.locale', 'en-IN'), 'scope' => '/',
        'icons' => [
            ['src' => $faviconUrl, 'sizes' => '32x32', 'type' => 'image/png'],
            ['src' => '/images/icons/icon-192x192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ['src' => $logo, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
        ],
    ], 200, ['Content-Type' => 'application/manifest+json']);
});
Route::get('/sw.js', fn () => response()->file(public_path('sw.js'), ['Content-Type' => 'application/javascript', 'Service-Worker-Allowed' => '/']));
Route::get('/admin-manifest.json', function () {
    $storeName = \App\Models\Setting::get('store_name', config('app.name'));
    $color = \App\Models\Setting::get('primary_color', '#1a1a1a');
    $favicon = \App\Models\Setting::get('store_favicon', '');
    $logo = '/' . \App\Models\Setting::get('store_logo', 'images/logo.png');
    $iconUrl = $favicon ? '/' . $favicon : $logo;
    return response()->json([
        'name' => $storeName . ' Admin',
        'short_name' => $storeName . ' Admin',
        'description' => $storeName . ' Store Management',
        'start_url' => '/admin', 'scope' => '/admin',
        'display' => 'standalone', 'orientation' => 'any',
        'theme_color' => '#1a1a1a', 'background_color' => '#f1f1f1',
        'icons' => [
            ['src' => $iconUrl, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ['src' => $logo, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
        ],
    ], 200, ['Content-Type' => 'application/manifest+json']);
});

// Storefront Routes (cached for guest users)
// Homepage is NOT response-cached — it must always reflect the latest admin
// changes (featured/new-arrivals products, sections) the instant a page is loaded.
Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Products
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [App\Http\Controllers\ProductController::class, 'index'])->name('index')->middleware('cache.response:5');
    Route::redirect('/new-arrivals', '/new-arrivals', 301);
    Route::redirect('/bestsellers', '/bestsellers', 301);
    Route::get('/{slug}', function (string $slug) {
        // Try active product first
        $product = \App\Models\Product::where('slug', $slug)->where('is_active', true)->first();
        if ($product) {
            return app(\App\Http\Controllers\ProductController::class)->show($product);
        }
        // Check if deleted → 410 Gone (tells Google to remove from index)
        $deleted = \App\Models\Product::withTrashed()->where('slug', $slug)->first();
        if ($deleted) {
            abort(410);
        }

        // Old slug redirect map (WordPress/Shopify migration)
        $oldSlugs = [
            'coq10life-heart-health' => 'coenzyme-q10-tablets',
            'skin-sculpt' => 'ayurvexa-skin-sculpt',
            'liver-support' => 'ayurvexa-liver-support',
            'spirulina' => 'ayurvexa-spirulina-bliss',
            'energize-q' => 'energize-q-stamina-booster',
            'coq10' => 'coenzyme-q10-tablets',
        ];
        if (isset($oldSlugs[$slug])) {
            return redirect("/products/{$oldSlugs[$slug]}", 301);
        }

        // Keyword fallback — try to match by keywords in the slug
        $keywords = array_filter(explode('-', $slug), fn($w) => strlen($w) > 3);
        if (count($keywords) >= 1) {
            $query = \App\Models\Product::where('is_active', true);
            foreach (array_slice($keywords, 0, 2) as $kw) {
                $query->where(fn($q) => $q->where('slug', 'ilike', "%{$kw}%")->orWhere('name', 'ilike', "%{$kw}%"));
            }
            $match = $query->first();
            if ($match) {
                return redirect("/products/{$match->slug}", 301);
            }
        }

        abort(404);
    })->name('show')->middleware('cache.response:5');
});

// Alias: /product/slug → 301 redirect to /products/slug OR 410 if deleted
Route::get('/product/{slug}', function (string $slug) {
    $product = \App\Models\Product::withTrashed()->where('slug', $slug)->first();
    if (!$product) abort(404);
    if ($product->trashed() || !$product->is_active) abort(410);
    return redirect()->route('products.show', $slug, 301);
})->name('product.show');

// Instagram Reels / Videos
Route::get('/reels', [App\Http\Controllers\ReelController::class, 'index'])->name('reels.index')->middleware('cache.response:5');
Route::get('/reels/{shortcode}', [App\Http\Controllers\ReelController::class, 'show'])->name('reels.show')->middleware('cache.response:5');
Route::get('/api/reels', [App\Http\Controllers\ReelController::class, 'apiLatest'])->name('reels.api')->middleware('cache.response:5');

// Quick View (AJAX)
Route::get('/product/{product}/quick-view', [App\Http\Controllers\ProductController::class, 'quickView'])->name('product.quick-view');

// Product Reviews (public JSON for load-more)
Route::get('/products/{product}/reviews', [App\Http\Controllers\ProductController::class, 'reviewsJson'])
    ->name('product.reviews');

// Guest Reviews
Route::post('/products/{product}/guest-review', [App\Http\Controllers\GuestReviewController::class, 'store'])
    ->name('product.guest-review')
    ->middleware('throttle:3,60');

// Product Questions
Route::post('/products/{product}/ask-question', [App\Http\Controllers\ProductController::class, 'askQuestion'])
    ->name('product.ask-question')
    ->middleware('throttle:5,60');

// Back in Stock Notifications
Route::post('/products/{product}/notify-stock', [App\Http\Controllers\BackInStockController::class, 'store'])
    ->name('product.notify-stock')
    ->middleware('throttle:5,60');

// Collections (renamed from categories)
Route::prefix('collections')->name('categories.')->group(function () {
    Route::get('/', [App\Http\Controllers\CategoryController::class, 'index'])->name('index')->middleware('cache.response:5');
    Route::get('/{category:slug}', [App\Http\Controllers\CategoryController::class, 'show'])->name('show')->middleware('cache.response:5');
});

// 301 redirect /categories → /collections
Route::redirect('/categories', '/collections', 301);
Route::get('/categories/{slug}', fn (string $slug) => redirect("/collections/{$slug}", 301))->where('slug', '.*');

// Aliases: common short URLs → proper routes
Route::redirect('/orders', '/account/orders', 301);
Route::redirect('/order/{any}', '/account/orders', 301)->where('any', '.*');
// /track-order is a public guest order lookup — do NOT redirect it

// Alias: /category/{idOrSlug} → 301 redirect to /collections/{slug} OR 410 if inactive
Route::get('/category/{idOrSlug}', function (string $idOrSlug) {
    $category = is_numeric($idOrSlug)
        ? App\Models\Category::find($idOrSlug)
        : App\Models\Category::where('slug', $idOrSlug)->first();
    if (!$category) abort(404);
    if (!$category->is_active) abort(410);
    return redirect("/collections/{$category->slug}", 301);
})->name('category.show');

// Brands
Route::prefix('brands')->name('brands.')->group(function () {
    Route::get('/', [App\Http\Controllers\BrandController::class, 'index'])->name('index')->middleware('cache.response:5');
    Route::get('/{brand:slug}', [App\Http\Controllers\BrandController::class, 'show'])->name('show')->middleware('cache.response:5');
});

// Sellers
Route::get('/sellers/{seller:slug}', [App\Http\Controllers\SellerController::class, 'show'])->name('sellers.show');

// Search
Route::get('/search', [App\Http\Controllers\SearchController::class, 'index'])->name('search');
Route::get('/search/suggestions', [App\Http\Controllers\SearchController::class, 'suggestions'])->name('search.suggestions');

// Shopify-compatible shareable discount links (used by Shiprocket Checkout coupons)
Route::get('/discount/{code}', [App\Http\Controllers\DiscountLinkController::class, 'apply'])
    ->where('code', '[A-Za-z0-9_-]+')
    ->name('discount.apply');

// Special Pages
Route::redirect('/deals', '/pages/offers', 301);
Route::get('/new-arrivals', [App\Http\Controllers\ProductController::class, 'newArrivals'])->name('new-arrivals');
Route::get('/bestsellers', [App\Http\Controllers\ProductController::class, 'bestsellers'])->name('bestsellers');
Route::get('/wholesale', [App\Http\Controllers\WholesaleController::class, 'index'])->name('wholesale');

// Cart
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/data', [App\Http\Controllers\CartController::class, 'data'])->name('data');
    Route::get('/', [App\Http\Controllers\CartController::class, 'index'])->name('index');
    Route::post('/add', [App\Http\Controllers\CartController::class, 'add'])->name('add');
    Route::post('/apply-coupon', [App\Http\Controllers\CartController::class, 'applyCoupon'])->name('apply-coupon');
    Route::delete('/remove-coupon', [App\Http\Controllers\CartController::class, 'removeCoupon'])->name('remove-coupon');
    Route::delete('/', [App\Http\Controllers\CartController::class, 'clear'])->name('clear');
    Route::put('/{cartItem}', [App\Http\Controllers\CartController::class, 'update'])->name('update');
    Route::delete('/{cartItem}', [App\Http\Controllers\CartController::class, 'destroy'])->name('destroy');
});

// Wishlist page (handles auth check in controller)
Route::get('/wishlist', [App\Http\Controllers\WishlistController::class, 'index'])->name('wishlist');

// Wishlist actions (require auth)
Route::middleware('auth')->prefix('wishlist')->name('wishlist.')->group(function () {
    Route::post('/{product:id}', [App\Http\Controllers\WishlistController::class, 'store'])->name('store');
    Route::delete('/{product:id}', [App\Http\Controllers\WishlistController::class, 'destroy'])->name('destroy');
});

// Guest Authentication Routes
Route::middleware(['guest', 'throttle:10,1'])->group(function () {
    Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login'])->middleware('throttle.login');
    Route::get('/register', [App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);
    Route::get('/password/reset', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/password/reset/{token}', [App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});

// Pincode Serviceability Check (public AJAX)
Route::get('/api/check-pincode/{pincode}', [App\Http\Controllers\CheckoutController::class, 'checkPincode'])
    ->where('pincode', '[0-9]{6}')
    ->middleware('throttle:30,1')
    ->name('pincode.check');

// /api/shiprocket-checkout-token → moved to routes/shiprocket_checkout.php (ShiprocketCheckout\TokenController)

// Gift Card Balance Check
Route::get('/gift-cards/balance', [App\Http\Controllers\GiftCardController::class, 'balanceCheck'])->name('gift-cards.balance');
Route::post('/gift-cards/balance', [App\Http\Controllers\GiftCardController::class, 'checkBalance'])->middleware('throttle:5,1')->name('gift-cards.check-balance');

// Abandoned Checkout Capture (AJAX - captures email/phone before form submit)
Route::post('/api/abandoned-capture', [App\Http\Controllers\CheckoutController::class, 'captureAbandoned'])
    ->middleware('throttle:20,1')
    ->name('checkout.abandoned.capture');

// Checkout (guest + auth)
Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', [App\Http\Controllers\CheckoutController::class, 'index'])->name('index');
    Route::post('/process', [App\Http\Controllers\CheckoutController::class, 'process'])->middleware('throttle:10,1')->name('process');
    Route::post('/gift-card/apply', [App\Http\Controllers\CheckoutController::class, 'applyGiftCard'])->middleware('throttle:10,1')->name('gift-card.apply');
    Route::post('/gift-card/remove', [App\Http\Controllers\CheckoutController::class, 'removeGiftCard'])->name('gift-card.remove');
    Route::post('/razorpay/create-order', [App\Http\Controllers\CheckoutController::class, 'createRazorpayOrder'])->middleware('throttle:10,1')->name('razorpay.create');
    Route::post('/razorpay/verify', [App\Http\Controllers\CheckoutController::class, 'verifyRazorpayPayment'])->middleware('throttle:10,1')->name('razorpay.verify');
    Route::post('/cashfree/create-order', [App\Http\Controllers\CheckoutController::class, 'createCashfreeOrder'])->middleware('throttle:10,1')->name('cashfree.create');
    Route::get('/cashfree/pay', [App\Http\Controllers\CheckoutController::class, 'cashfreeRedirect'])->name('cashfree.redirect');
    Route::get('/cashfree/return', [App\Http\Controllers\CheckoutController::class, 'cashfreeReturn'])->name('cashfree.return');
    Route::post('/cashfree/webhook', [App\Http\Controllers\CheckoutController::class, 'cashfreeWebhook'])->name('cashfree.webhook');
    // /checkout/success/shiprocket and /checkout/shiprocket-details → moved to routes/shiprocket_checkout.php
    Route::get('/success/{token}', [App\Http\Controllers\CheckoutController::class, 'success'])->name('success')->where('token', '(?!shiprocket$)[^/]+');
    Route::get('/failed', [App\Http\Controllers\CheckoutController::class, 'failed'])->name('failed');
});

// Authenticated User Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

    // Email Verification
    Route::get('/email/verify', [App\Http\Controllers\Auth\VerificationController::class, 'show'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [App\Http\Controllers\Auth\VerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/email/resend', [App\Http\Controllers\Auth\VerificationController::class, 'resend'])->name('verification.resend');

    // Account Routes
    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/', [App\Http\Controllers\Account\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [App\Http\Controllers\Account\ProfileController::class, 'edit'])->name('profile');
        Route::put('/profile', [App\Http\Controllers\Account\ProfileController::class, 'update'])->name('profile.update');
        Route::put('/password', [App\Http\Controllers\Account\ProfileController::class, 'updatePassword'])->name('password.update');

        // Addresses
        Route::resource('addresses', App\Http\Controllers\Account\AddressController::class);

        // Orders
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [App\Http\Controllers\Account\OrderController::class, 'index'])->name('index');
            Route::get('/{order}', [App\Http\Controllers\Account\OrderController::class, 'show'])->name('show');
            Route::post('/{order}/cancel', [App\Http\Controllers\Account\OrderController::class, 'cancel'])->name('cancel');
            Route::get('/{order}/invoice', [App\Http\Controllers\Account\OrderController::class, 'invoice'])->name('invoice');
            Route::get('/{order}/track', [App\Http\Controllers\Account\OrderController::class, 'track'])->name('track');
        });

        // Returns
        Route::resource('returns', App\Http\Controllers\Account\ReturnController::class);

        // Reviews
        Route::get('/reviews', [App\Http\Controllers\Account\ReviewController::class, 'index'])->name('reviews');
        Route::get('/reviews/create/{product}', [App\Http\Controllers\Account\ReviewController::class, 'create'])->name('reviews.create');
        Route::post('/reviews/{product}', [App\Http\Controllers\Account\ReviewController::class, 'store'])->name('reviews.store');

        // Support Tickets
        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/', [App\Http\Controllers\Account\TicketController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Account\TicketController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Account\TicketController::class, 'store'])->name('store');
            Route::get('/{ticket}', [App\Http\Controllers\Account\TicketController::class, 'show'])->name('show');
            Route::post('/{ticket}/reply', [App\Http\Controllers\Account\TicketController::class, 'reply'])->name('reply');
        });

        // Notifications
        Route::get('/notifications', [App\Http\Controllers\Account\NotificationController::class, 'index'])->name('notifications');

        // Notification Preferences
        Route::get('/notification-preferences', [App\Http\Controllers\Account\NotificationPreferenceController::class, 'edit'])->name('notification-preferences');
        Route::put('/notification-preferences', [App\Http\Controllers\Account\NotificationPreferenceController::class, 'update'])->name('notification-preferences.update');

        // Become a Delivery Partner
        Route::get('/become-delivery-partner', [App\Http\Controllers\Account\DeliveryPartnerRegistrationController::class, 'create'])->name('become-delivery-partner');
        Route::post('/become-delivery-partner', [App\Http\Controllers\Account\DeliveryPartnerRegistrationController::class, 'store'])->name('become-delivery-partner.store');
        Route::post('/become-delivery-partner/documents', [App\Http\Controllers\Account\DeliveryPartnerRegistrationController::class, 'uploadDocuments'])->name('become-delivery-partner.documents');
    });
});

// Seller Registration (Guest)
Route::get('/sell', [App\Http\Controllers\Seller\RegistrationController::class, 'index'])->name('seller.register');
Route::post('/sell/register', [App\Http\Controllers\Seller\RegistrationController::class, 'store'])->name('seller.register.store');

// Newsletter
Route::post('/newsletter/subscribe', [App\Http\Controllers\NewsletterController::class, 'subscribe'])->middleware('throttle:5,1')->name('newsletter.subscribe');

// OTP Login & Password Reset
Route::post('/otp/send-login', [App\Http\Controllers\Auth\OtpController::class, 'sendLoginOtp'])->middleware('throttle:20,5')->name('otp.send-login');
Route::post('/otp/verify-login', [App\Http\Controllers\Auth\OtpController::class, 'verifyLoginOtp'])->middleware('throttle:30,5')->name('otp.verify-login');
Route::post('/otp/send-reset', [App\Http\Controllers\Auth\OtpController::class, 'sendResetOtp'])->middleware('throttle:20,5')->name('otp.send-reset');
Route::post('/otp/verify-reset', [App\Http\Controllers\Auth\OtpController::class, 'verifyResetOtp'])->middleware('throttle:30,5')->name('otp.verify-reset');
Route::post('/otp/reset-password', [App\Http\Controllers\Auth\OtpController::class, 'resetPassword'])->middleware('throttle:10,5')->name('otp.reset-password');

// Social Login (Google, Facebook)
Route::get('/auth/{provider}/redirect', [App\Http\Controllers\SocialLoginController::class, 'redirect'])->name('social.redirect');
Route::get('/auth/{provider}/callback', [App\Http\Controllers\SocialLoginController::class, 'callback'])->name('social.callback');

// Push Notifications
Route::post('/push/subscribe', [App\Http\Controllers\PushSubscriptionController::class, 'subscribe'])->middleware('throttle:10,1')->name('push.subscribe');
Route::post('/push/unsubscribe', [App\Http\Controllers\PushSubscriptionController::class, 'unsubscribe'])->middleware('throttle:10,1')->name('push.unsubscribe');

// Recommendations (AJAX)
Route::prefix('recommendations')->name('recommendations.')->group(function () {
    Route::get('/recently-viewed', [App\Http\Controllers\Web\RecommendationController::class, 'recentlyViewed'])->name('recently-viewed');
    Route::get('/similar/{productId}', [App\Http\Controllers\Web\RecommendationController::class, 'similar'])->name('similar');
    Route::get('/bought-together/{productId}', [App\Http\Controllers\Web\RecommendationController::class, 'frequentlyBoughtTogether'])->name('bought-together');
    Route::get('/personalized', [App\Http\Controllers\Web\RecommendationController::class, 'personalized'])->name('personalized');
});

// AI Chatbot
Route::post('/chatbot/message', [App\Http\Controllers\ChatbotController::class, 'message'])->middleware('throttle:20,1')->name('chatbot.message');

// Track Order (Public with order number)
Route::get('/track-order', [App\Http\Controllers\TrackOrderController::class, 'index'])->name('track-order');
Route::post('/track-order', [App\Http\Controllers\TrackOrderController::class, 'track'])->name('track-order.track');
Route::post('/track-order/{order}/cancel', [App\Http\Controllers\TrackOrderController::class, 'cancel'])->middleware('throttle:5,1')->name('track-order.cancel');
Route::get('/track-order/{order}/return', [App\Http\Controllers\TrackOrderController::class, 'returnForm'])->name('track-order.return');
Route::post('/track-order/{order}/return', [App\Http\Controllers\TrackOrderController::class, 'storeReturn'])->middleware('throttle:5,1')->name('track-order.return.store');
Route::get('/track-order/{order}/return/done', [App\Http\Controllers\TrackOrderController::class, 'returnConfirmation'])->name('track-order.return.confirmation');

// Free Consultation
Route::get('/consultation', [App\Http\Controllers\ConsultationController::class, 'index'])->name('consultation');
Route::post('/consultation', [App\Http\Controllers\ConsultationController::class, 'store'])->middleware('throttle:5,1')->name('consultation.store');

// Old WordPress/Shopify URL redirects (from nginx 404 logs + Ayurvexa migration)
// Products
Route::redirect('/liver-detox-supplement', '/products/ayurvexa-liver-support', 301);
Route::redirect('/coenzyme-q10-tablet', '/products/coenzyme-q10-tablets', 301);
Route::redirect('/ayurvexa-skin-sculpt-skin-radiance-supplement-for-naturally-glowing-skin', '/products/ayurvexa-skin-sculpt', 301);
Route::redirect('/products/coq10life-heart-health', '/products/coenzyme-q10-tablets', 301);
Route::redirect('/products/skin-sculpt', '/products/ayurvexa-skin-sculpt', 301);
Route::redirect('/products/liver-support', '/products/ayurvexa-liver-support', 301);
Route::redirect('/products/spirulina', '/products/ayurvexa-spirulina-bliss', 301);
// Old WordPress categories
Route::redirect('/product-category/ayurvexa', '/products', 301);
Route::redirect('/category/ayurvexa', '/products', 301);
Route::redirect('/collections/ayurvexa', '/products', 301);
Route::redirect('/categories/skin-radiance', '/category/skin-care', 301);
Route::redirect('/categories/high-on-energy', '/category/high-on-energy', 301);
Route::redirect('/categories/healthy-heart', '/category/healthy-heart', 301);
// Old WordPress pages
Route::redirect('/gdpr', '/privacy-policy', 301);
// Old WordPress blogs → product pages (blog doesn't exist for these topics)
Route::get('/2022/{any}', fn () => redirect('/products', 301))->where('any', '.*');
Route::redirect('/boosting-your-energy-naturally-sustainable-habits-ayurvexa-solutions', '/products/energize-q-stamina-booster', 301);
Route::redirect('/what-are-the-first-signs-of-liver-damage-causes-solutions-prevention-guide', '/products/ayurvexa-liver-support', 301);
// Guest review URL
Route::redirect('/review/{slug}', '/products/{slug}#customer-reviews', 301)->where('slug', '[a-z0-9-]+');

// Shopify-style /pages/{slug} URLs
Route::get('/pages/gallery', [App\Http\Controllers\PageController::class, 'gallery'])->name('gallery');
Route::get('/pages/about-us', [App\Http\Controllers\PageController::class, 'about'])->name('about');
Route::get('/pages/contact', [App\Http\Controllers\PageController::class, 'contact'])->name('contact');
Route::post('/pages/contact', [App\Http\Controllers\PageController::class, 'sendContact'])->middleware('throttle:5,1')->name('contact.send');
Route::get('/pages/offers', fn () => view('pages.offers'))->name('offers');
Route::get('/pages/{slug}', fn(string $slug) => redirect("/page/{$slug}", 301))->where('slug', '[a-z0-9-]+');

// Old URL redirects → new /pages/ URLs
Route::redirect('/about', '/pages/about-us', 301);
Route::redirect('/about-us', '/pages/about-us', 301);
Route::redirect('/contact', '/pages/contact', 301);
Route::redirect('/offers', '/pages/offers', 301);

// Static/CMS Pages
Route::get('/faq', [App\Http\Controllers\PageController::class, 'faq'])->name('faq');
Route::get('/blog', [App\Http\Controllers\PageController::class, 'blog'])->name('blog');
Route::get('/blog/{slug}', [App\Http\Controllers\PageController::class, 'blogShow'])->name('blog.show');
Route::get('/blogs/{any?}', fn () => redirect('/blog', 301))->where('any', '.*');
Route::get('/careers', [App\Http\Controllers\PageController::class, 'careers'])->name('careers');
Route::get('/help', [App\Http\Controllers\PageController::class, 'help'])->name('help');
Route::get('/returns-policy', [App\Http\Controllers\PageController::class, 'returns'])->name('returns');
Route::get('/shipping', [App\Http\Controllers\PageController::class, 'shipping'])->name('shipping');
Route::get('/size-guide', [App\Http\Controllers\PageController::class, 'sizeGuide'])->name('size-guide');
Route::get('/bmi-calculator', [App\Http\Controllers\PageController::class, 'bmi'])->name('bmi-calculator');
Route::get('/privacy-policy', [App\Http\Controllers\PageController::class, 'privacy'])->name('privacy');
Route::get('/terms-of-service', [App\Http\Controllers\PageController::class, 'terms'])->name('terms');
Route::get('/cookie-policy', [App\Http\Controllers\PageController::class, 'cookiePolicy'])->name('cookie-policy');


// Shipping Policy (legal page from DB)
Route::get('/shipping-policy', [App\Http\Controllers\PageController::class, 'shippingPolicy'])->name('shipping-policy');
// /collections is now a real page (was redirect to /products)
// Short URL redirects for common paths
Route::redirect('/terms', '/terms-of-service', 301);
Route::redirect('/privacy', '/privacy-policy', 301);
Route::redirect('/returns', '/returns-policy', 301);
Route::redirect('/refund-policy', '/returns-policy', 301);
Route::redirect('/account/dashboard', '/account', 301);
Route::get('/gdpr', [App\Http\Controllers\PageController::class, 'gdpr'])->name('gdpr');
Route::get('/sitemap', [App\Http\Controllers\PageController::class, 'sitemap'])->name('sitemap.html');
// Redirect /page/ slugs that have dedicated /pages/ routes
Route::redirect('/page/offers', '/pages/offers', 301);
Route::redirect('/page/about-us', '/pages/about-us', 301);
Route::redirect('/page/contact', '/pages/contact', 301);
Route::redirect('/page/gallery', '/pages/gallery', 301);
Route::get('/page/{page:slug}', [App\Http\Controllers\PageController::class, 'show'])->name('page.show');

// Instagram Callbacks (Facebook App requirement)
Route::match(['get', 'post'], '/auth/instagram/callback', [\App\Http\Controllers\Api\InstagramCallbackController::class, 'deauthorize'])->name('instagram.callback')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
Route::match(['get', 'post'], '/auth/instagram/deauthorize', [\App\Http\Controllers\Api\InstagramCallbackController::class, 'deauthorize'])->name('instagram.deauthorize.web')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
Route::match(['get', 'post'], '/auth/instagram/delete', [\App\Http\Controllers\Api\InstagramCallbackController::class, 'delete'])->name('instagram.delete.web')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

// Internal Tools (noindex, temporary)
Route::prefix('tools/jikra-img-mgr')->middleware(['auth:admin', 'admin'])->name('tools.')->group(function () {
    Route::get('/', [App\Http\Controllers\ProductImageManagerController::class, 'index'])->name('image-manager');
    Route::put('/{product}', [App\Http\Controllers\ProductImageManagerController::class, 'update'])->name('image-manager.update');
    Route::put('/{product}/toggle', [App\Http\Controllers\ProductImageManagerController::class, 'toggleStatus'])->name('image-manager.toggle');
    Route::delete('/image/{image}', [App\Http\Controllers\ProductImageManagerController::class, 'deleteImage'])->name('image-manager.delete-image');
    Route::delete('/product/{product}', [App\Http\Controllers\ProductImageManagerController::class, 'destroyProduct'])->name('image-manager.destroy');
});

// Load Admin Routes
require __DIR__.'/admin.php';

// Load Seller Routes
require __DIR__.'/seller.php';

// Load Delivery Partner Routes
require __DIR__.'/delivery.php';

// Load POS Routes
require __DIR__.'/pos.php';

// Load Affiliate Routes
require __DIR__.'/affiliate.php';

// ============================================================
// FALLBACK: Catch unknown URLs → redirect to matching product
// Handles: old WordPress URLs, Instagram links, Shopify URLs
// This MUST be the LAST route in the file.
// ============================================================
Route::fallback(function (\Illuminate\Http\Request $request) {
    $slug = trim($request->path(), '/');

    // Skip file paths and nested URLs
    if (!$slug || str_contains($slug, '.') || str_contains($slug, '/')) {
        abort(404);
    }

    // 1. Exact slug match (current or deleted product)
    $product = \App\Models\Product::withTrashed()->where('slug', $slug)->first();
    if ($product) {
        if ($product->is_active && !$product->trashed()) {
            return redirect()->route('products.show', $slug, 301);
        }
        abort(410); // Deleted → tell Google to stop crawling
    }

    // 2. Keyword match for old WordPress/Instagram URLs
    //    e.g. /liver-detox-supplement → matches product with "liver" + "detox" in name
    $keywords = array_filter(explode('-', $slug), fn($w) => strlen($w) > 3);
    if (count($keywords) >= 1) {
        $query = \App\Models\Product::where('is_active', true);
        foreach (array_slice($keywords, 0, 2) as $kw) {
            $query->where(function ($q) use ($kw) {
                $q->where('slug', 'ilike', "%{$kw}%")
                  ->orWhere('name', 'ilike', "%{$kw}%");
            });
        }
        $match = $query->first();
        if ($match) {
            return redirect()->route('products.show', $match->slug, 301);
        }
    }

    abort(404);
});
