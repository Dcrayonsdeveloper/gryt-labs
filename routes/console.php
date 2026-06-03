<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Generate reviews from delivered orders daily at 2am
Schedule::command('reviews:generate')->dailyAt('02:00');

// Drip 1-3 reviews every hour at random intervals (skips ~40% of hours for natural pattern)
Schedule::command('reviews:drip-daily --min=1 --max=3')->hourly()->when(function () {
    // Skip random hours — only run ~60% of the time for unpredictable pattern
    return mt_rand(1, 100) <= 60;
});

// Publish scheduled social media posts every minute
Schedule::command('social:publish-scheduled')->everyMinute();

// Send abandoned cart reminders hourly (email + WhatsApp multi-touch)
Schedule::command('cart:remind-abandoned')->hourly();

// Daily abandoned cart summary to admin at 8:30am
Schedule::command('cart:abandoned-summary')->dailyAt('08:30');

// Sync all carrier tracking statuses every 30 minutes (Delhivery, BlueDart, etc.)
Schedule::command('shipping:sync-tracking')->everyThirtyMinutes();

// Detect Shiprocket Checkout orders that never synced into the local DB
// (callback missed AND webhook missed) and WhatsApp the admin so they can be
// recovered with `php artisan shiprocket:reconcile-orders --tenant=<id> --create`.
Schedule::command('shiprocket:reconcile-orders --alert')->hourly();

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
