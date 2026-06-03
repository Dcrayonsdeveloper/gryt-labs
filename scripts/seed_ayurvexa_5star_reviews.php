<?php

/**
 * Seed 30 five-star reviews on each active product for the Ayurvexa tenant.
 * Dates are randomly spread within the last 11 months (not before 1 year).
 *
 * Usage: php artisan tenants:run "tinker --execute=require base_path('scripts/seed_ayurvexa_5star_reviews.php')" --tenants=ayurvexa
 * Or directly: php scripts/seed_ayurvexa_5star_reviews.php (from project root, after bootstrap)
 */

use App\Models\Product;
use App\Models\Setting;
use App\Services\ReviewGeneratorService;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel if running standalone
if (!defined('LARAVEL_START')) {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    // Initialize tenant
    $tenant = \App\Models\Tenant::find('ayurvexa');
    if (!$tenant) {
        echo "ERROR: Ayurvexa tenant not found.\n";
        exit(1);
    }
    tenancy()->initialize($tenant);
    echo "Initialized tenant: ayurvexa\n";
}

$generator = app(ReviewGeneratorService::class);
$products = Product::where('is_active', true)->get();

if ($products->isEmpty()) {
    echo "No active products found.\n";
    exit(1);
}

echo "Found {$products->count()} active products. Seeding 30 five-star reviews each...\n";

$totalCreated = 0;
$now = now();

// Disable model events to avoid slow updateRating() on every insert
\App\Models\Review::withoutEvents(function () use ($products, $generator, $now, &$totalCreated) {
    foreach ($products as $product) {
        $reviews = [];

        for ($i = 0; $i < 30; $i++) {
            // Random date within last 11 months (not before 1 year)
            $daysAgo = mt_rand(1, 330);
            $createdAt = $now->copy()
                ->subDays($daysAgo)
                ->setTime(mt_rand(6, 23), mt_rand(0, 59), mt_rand(0, 59));

            $reviews[] = [
                'product_id' => $product->id,
                'user_id' => null,
                'guest_name' => $generator->randomIndianName(),
                'guest_email' => 'review' . mt_rand(10000, 99999) . '@customer.' . parse_url(config('app.url', 'localhost'), PHP_URL_HOST),
                'rating' => 5,
                'title' => $generator->generateTitle($product, 5),
                'content' => $generator->generateContent($product, 5),
                'pros' => json_encode($generator->generatePros($product, 5)),
                'cons' => json_encode([]),
                'is_verified_purchase' => (bool) mt_rand(0, 1),
                'is_approved' => true,
                'is_featured' => $i === 0,
                'helpful_count' => mt_rand(0, 25),
                'unhelpful_count' => 0,
                'status' => 'approved',
                'is_generated' => true,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }

        DB::table('reviews')->insert($reviews);
        $totalCreated += count($reviews);

        // Update product rating & count
        $stats = DB::table('reviews')
            ->where('product_id', $product->id)
            ->where('is_approved', true)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as review_count')
            ->first();

        $product->update([
            'rating' => round($stats->avg_rating, 2),
            'review_count' => $stats->review_count,
        ]);

        echo "  [OK] {$product->name} — {$stats->review_count} total reviews (avg: {$stats->avg_rating})\n";
    }
});

echo "\nDone! Created {$totalCreated} five-star reviews across {$products->count()} products.\n";
