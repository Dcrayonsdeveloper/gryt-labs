<?php

use App\Http\Controllers\ShiprocketCheckout\CallbackController;
use App\Http\Controllers\ShiprocketCheckout\CatalogController;
use App\Http\Controllers\ShiprocketCheckout\CustomerDetailsController;
use App\Http\Controllers\ShiprocketCheckout\TokenController;
use App\Http\Controllers\ShiprocketCheckout\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Shiprocket Checkout — All routes in one place
|--------------------------------------------------------------------------
|
| Webhook stages (configured in Shiprocket → Settings → Webhooks):
|   Cart Initiated, Phone Received, Payment Initiated,
|   Order Placed, Payment Complete, Abandoned Cart
|
| Custom Endpoints (configured in Shiprocket → Settings → Custom Endpoints):
|   PRODUCT FETCH           → GET /api/v1/products
|   COLLECTION FETCH        → GET /api/v1/categories
|   COLLECTION PRODUCT FETCH→ GET /api/v1/categories/{slug}/products
|
| Success callback URL (configured in token redirect_url):
|   GET /checkout/success/shiprocket?oid=<hex>&ost=<status>
|
| To swap from the old controllers to these new ones, update routes/web.php
| to include this file instead of defining inline routes.
|
*/

// ─── Webhook (Shiprocket → us) ────────────────────────────────────────────────
// Real-time + Abandoned Cart events sent by Shiprocket Checkout at each stage.
// Auth: custom header verified by ShiprocketCheckoutService::verifyWebhookAuth().
Route::post('/webhook/shipping-updates', [WebhookController::class, 'handle'])
    ->middleware('throttle:120,1')
    ->name('shiprocket.checkout.webhook');

// ─── SDK Token (AJAX — browser → us → Shiprocket) ────────────────────────────
// Called when customer clicks Buy Now or Checkout.
// Returns a token the frontend passes to shiprocketCheckoutCustomHandler().
Route::post('/api/shiprocket-checkout-token', TokenController::class)
    ->middleware('throttle:30,1')
    ->name('shiprocket.checkout.token');

// ─── Success Callback (Shiprocket → browser → us) ────────────────────────────
// Shiprocket redirects here after checkout completes.
// Creates order, dispatches OrderPlaced, renders confirmation page.
Route::get('/checkout/success/shiprocket', CallbackController::class)
    ->name('shiprocket.checkout.callback');

// ─── Customer Details (browser → us, silent POST from success page) ───────────
// The success page reads sessionStorage['sr_checkout_customer'] and POSTs here
// to fill in guest_name / guest_phone / shipping_address on the order.
Route::post('/checkout/shiprocket-details', CustomerDetailsController::class)
    ->middleware('throttle:10,1')
    ->name('shiprocket.checkout.details');

// ─── Catalog Custom Endpoints (Shiprocket → us) ──────────────────────────────
// Shiprocket pulls these periodically to keep its product catalog in sync.
// Configure in Shiprocket → Settings → Custom Endpoints with these URLs:
//   PRODUCT FETCH           → https://{domain}/sr/catalog/products
//   COLLECTION FETCH        → https://{domain}/sr/catalog/categories
//   COLLECTION PRODUCT FETCH→ https://{domain}/sr/catalog/categories/{slug}/products
//
// NOTE: /api/v1/* is used by our internal API with VerifyApiKey middleware.
// Using /sr/catalog/* avoids the auth conflict — update Shiprocket dashboard to match.
// Throttle is high (600/min) because Shiprocket's crawler fetches every
// collection in a rapid burst. At the old 60/min cap, requests past the 60th
// were 429'd, so Shiprocket could never refresh its catalog and kept charging
// stale prices. The endpoints expose only public, read-only catalog data.
Route::prefix('sr/catalog')->name('shiprocket.catalog.')->group(function () {
    Route::get('/products', [CatalogController::class, 'products'])
        ->middleware('throttle:600,1')
        ->name('products');

    Route::get('/categories', [CatalogController::class, 'categories'])
        ->middleware('throttle:600,1')
        ->name('categories');

    // collection_id query-param variant — must be before {slug} to avoid "products" matching as slug
    Route::get('/categories/products', [CatalogController::class, 'collectionProducts'])
        ->middleware('throttle:600,1')
        ->name('collection.products');

    Route::get('/categories/{slug}/products', [CatalogController::class, 'categoryProducts'])
        ->middleware('throttle:600,1')
        ->name('category.products');
});
