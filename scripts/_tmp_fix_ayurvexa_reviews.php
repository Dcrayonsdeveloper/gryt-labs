<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

tenancy()->initialize(\App\Models\Tenant::find('ayurvexa'));

use App\Models\Product;
use App\Models\Review;
use App\Services\ReviewGeneratorService;
use Illuminate\Support\Facades\DB;

$generator = app(ReviewGeneratorService::class);

// Step 1: Delete ALL generated reviews (is_generated = true)
$deleted = DB::table('reviews')->where('is_generated', true)->delete();
echo "Deleted {$deleted} generated reviews.\n";

// Step 2: Re-seed 30 five-star reviews per active product
$products = Product::where('is_active', true)->get();
echo "Re-seeding 30 reviews for {$products->count()} products...\n";

$totalCreated = 0;
$now = now();

Review::withoutEvents(function () use ($products, $generator, $now, &$totalCreated) {
    foreach ($products as $product) {
        $reviews = [];

        for ($i = 0; $i < 30; $i++) {
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

        echo "  [OK] {$product->name} — {$stats->review_count} reviews (avg: {$stats->avg_rating})\n";
    }
});

echo "\nDone! Created {$totalCreated} reviews across {$products->count()} products.\n";

// Verify: show sample
echo "\n--- Verification samples ---\n";
foreach ($products as $p) {
    $sample = DB::table('reviews')
        ->where('product_id', $p->id)
        ->orderByDesc('created_at')
        ->limit(2)
        ->get(['guest_name', 'rating', 'title']);
    echo "\n{$p->name}:\n";
    foreach ($sample as $r) {
        echo "  {$r->guest_name} | {$r->rating}* | {$r->title}\n";
    }
}
