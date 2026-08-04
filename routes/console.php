<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// NOTE: `reviews:generate` and `reviews:drip-daily` are intentionally NOT scheduled.
// They synthesise review text and attribute it to real customers who placed orders.
// Publishing fabricated reviews as genuine customer reviews is prohibited by the
// CCPA / BIS IS 19000:2022 (India) and the FTC's 2024 fake-review rule (US).
// Real reviews are collected instead: OrderDelivered -> SendReviewInvitationAfterDelivery
// emails the customer an invitation, and SendCouponAfterReview rewards them.
// The commands remain available for seeding local/demo data only.

// Publish scheduled social media posts every minute
Schedule::command('social:publish-scheduled')->everyMinute();

// Send abandoned cart reminders hourly (email + WhatsApp multi-touch)
Schedule::command('cart:remind-abandoned')->hourly();

// Daily abandoned cart summary to admin at 8:30am
Schedule::command('cart:abandoned-summary')->dailyAt('08:30');

// Sync all carrier tracking statuses every 30 minutes (Delhivery, BlueDart, etc.)
Schedule::command('shipping:sync-tracking')->everyThirtyMinutes();

// Background verification loop (webhooks stay primary; this is the safety net).
// Every 5 min: recover Shiprocket Checkout orders missing locally (missed
// callback + missed webhook — the norm on this account, where Shiprocket has
// never delivered a webhook), then verify & repair orders needing attention:
// pricing/coupons, payment + transactions ledger, customer, address, items,
// fulfillment (AWB/courier/shipment record), status + lifecycle timestamps and
// the AWB tracking timeline. Idempotent; --limit=10 bounds API usage per run.
Schedule::command('shiprocket:verify-orders --days=2 --limit=10')
    ->everyFiveMinutes()
    ->withoutOverlapping(10);

// Recover Shiprocket Checkout orders that never synced into the local DB
// (customer paid but the success redirect was missed AND the webhook was missed —
// the norm on this account, where webhooks are not delivered). --create rebuilds
// the missing Order + dispatches OrderPlaced so it shows in admin and the customer
// gets their confirmation; --alert still WhatsApps the admin. Idempotent (advisory
// lock + existence check), so re-running never duplicates. Every 15 min keeps the
// gap between payment and the order appearing small. Overlaps with verify-orders
// are safe (same engine, same idempotency keys); this run adds the webhook-event +
// Shipping-API report sources and the WhatsApp alert.
Schedule::command('shiprocket:reconcile-orders --create --alert')->everyFifteenMinutes();

// Backfill real Shiprocket pricing (coupon/discount/COD) onto recent orders so the
// stored total and "Discount Codes Applied" match what the customer actually paid.
// Shiprocket does not push pricing on this account, so orders arrive at the full
// pre-discount total until this pulls the authoritative numbers from the order API.
// --limit=25 bounds it to the most recent orders (new ones are always recent).
Schedule::command('shiprocket:sync-pricing --limit=25')->everyFifteenMinutes();

// Refresh Instagram reels cache every 2 hours
Schedule::command('instagram:refresh-reels')->everyTwoHours();

// Send wishlist reminder emails weekly (Wednesdays at 10am)
Schedule::command('wishlist:send-reminders')->weeklyOn(3, '10:00');

// Send low stock inventory alert daily at 8am
Schedule::command('inventory:low-stock-alert')->dailyAt('08:00');

// Post-purchase email sequence (day 3 check-in, day 14 reorder) — daily at 9am
Schedule::command('orders:post-purchase-sequence')->dailyAt('09:00');

// Welcome email sequence for new users (day 3, day 7) — daily at 10:30am
Schedule::command('users:welcome-sequence')->dailyAt('10:30');

// Refresh customer segment counts daily at 4am
Schedule::command('customers:refresh-segments')->dailyAt('04:00');

// WhatsApp abandoned cart recovery — DISABLED (cart:remind-abandoned already sends WhatsApp)
// Schedule::command('abandoned:whatsapp-recover')->everyTwoHours();

// Automated database backup daily at 2:30am (keeps 7 days)
Schedule::command('backup:database')->dailyAt('02:30');

// Auto-update social proof text daily at 5am based on sales data
Schedule::command('products:update-social-proof')->dailyAt('05:00');

// Refresh Instagram access token every 50 days (token lasts 60 days)
Schedule::command('instagram:refresh-token')->dailyAt('03:00')->when(function () {
    $lastRefresh = \App\Models\Setting::get('instagram_token_last_refresh');
    if (!$lastRefresh) return true;
    return now()->diffInDays(\Carbon\Carbon::parse($lastRefresh)) >= 50;
});

// Re-dispatch SyncShiprocketCustomerDetails for orders aged 8h-7d with empty customer details.
// In-job retries cover the first 8h; this catches Shiprocket-API-late cases up to 7 days.
Schedule::command('orders:reconcile-missing-customer-details')->dailyAt('06:00');
