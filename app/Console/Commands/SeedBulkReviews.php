<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Review;
use App\Services\ReviewGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedBulkReviews extends Command
{
    protected $signature = 'reviews:seed-bulk
                            {--count=200 : Number of reviews per product}
                            {--product= : Specific product ID (omit for all products)}
                            {--min-rating=3.9 : Minimum target average rating}
                            {--max-rating=4.6 : Maximum target average rating}
                            {--days=180 : Spread reviews over this many past days}
                            {--forward-days=0 : Also spread reviews into future days (drip feed)}
                            {--dry-run : Show what would be generated without creating}';

    protected $description = 'Seed bulk reviews for products with backdated timestamps and targeted ratings (3.9-4.6 avg)';

    public function handle(ReviewGeneratorService $generator): int
    {
        $count = (int) $this->option('count');
        $minRating = (float) $this->option('min-rating');
        $maxRating = (float) $this->option('max-rating');
        $spreadDays = (int) $this->option('days');
        $forwardDays = (int) $this->option('forward-days');
        $productId = $this->option('product');

        $products = $productId
            ? Product::where('id', $productId)->get()
            : Product::where('is_active', true)->get();

        if ($products->isEmpty()) {
            $this->error('No products found.');
            return 1;
        }

        $this->info("Seeding {$count} reviews each for {$products->count()} products (target avg: {$minRating}-{$maxRating})");

        if ($this->option('dry-run')) {
            foreach ($products as $product) {
                $target = round($minRating + (mt_rand() / mt_getrandmax()) * ($maxRating - $minRating), 2);
                $this->line("  Would seed {$count} reviews for \"{$product->name}\" (target avg: {$target})");
            }
            return 0;
        }

        $bar = $this->output->createProgressBar($products->count());
        $totalCreated = 0;

        // Disable model events to avoid slow updateRating() on every insert
        Review::withoutEvents(function () use ($products, $generator, $count, $minRating, $maxRating, $spreadDays, $forwardDays, $bar, &$totalCreated) {
            foreach ($products as $product) {
                // Random target avg for this product within the range
                $targetAvg = round($minRating + (mt_rand() / mt_getrandmax()) * ($maxRating - $minRating), 2);

                $reviews = [];
                $now = now();

                for ($i = 0; $i < $count; $i++) {
                    $rating = $generator->pickRatingForTarget($targetAvg);

                    // Spread across past days and optionally future days
                    if ($forwardDays > 0 && mt_rand(1, 100) <= 40) {
                        // 40% of reviews go into future dates (drip feed)
                        $createdAt = $now->copy()
                            ->addDays(mt_rand(1, $forwardDays))
                            ->setTime(mt_rand(6, 23), mt_rand(0, 59), mt_rand(0, 59));
                    } else {
                        $createdAt = $now->copy()
                            ->subDays(mt_rand(1, $spreadDays))
                            ->setTime(mt_rand(6, 23), mt_rand(0, 59), mt_rand(0, 59));
                    }

                    $reviews[] = [
                        'product_id' => $product->id,
                        'user_id' => null,
                        'guest_name' => $generator->randomIndianName(),
                        'guest_email' => 'review' . mt_rand(10000, 99999) . '@customer.' . parse_url(config('app.url', 'localhost'), PHP_URL_HOST),
                        'rating' => $rating,
                        'title' => $generator->generateTitle($product, $rating),
                        'content' => $generator->generateContent($product, $rating),
                        'pros' => json_encode($generator->generatePros($product, $rating)),
                        'cons' => json_encode($generator->generateCons($product, $rating)),
                        'is_verified_purchase' => (bool) mt_rand(0, 1),
                        'is_approved' => true,
                        'is_featured' => $i === 0,
                        'helpful_count' => mt_rand(0, 30),
                        'unhelpful_count' => mt_rand(0, 3),
                        'status' => 'approved',
                        'is_generated' => true,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ];

                    // Insert in batches of 50 to avoid memory issues
                    if (count($reviews) >= 50) {
                        DB::table('reviews')->insert($reviews);
                        $totalCreated += count($reviews);
                        $reviews = [];
                    }
                }

                // Insert remaining
                if (!empty($reviews)) {
                    DB::table('reviews')->insert($reviews);
                    $totalCreated += count($reviews);
                }

                // Update product rating from all approved reviews
                $stats = DB::table('reviews')
                    ->where('product_id', $product->id)
                    ->where('is_approved', true)
                    ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as review_count')
                    ->first();

                $product->update([
                    'rating' => round($stats->avg_rating, 2),
                    'review_count' => $stats->review_count,
                ]);

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Done! Created {$totalCreated} reviews across {$products->count()} products.");

        // Show summary
        $this->newLine();
        $this->table(
            ['Product', 'Reviews', 'Avg Rating'],
            $products->map(fn ($p) => [
                substr($p->name, 0, 50),
                $p->fresh()->review_count,
                $p->fresh()->rating,
            ])->take(20)->toArray()
        );

        if ($products->count() > 20) {
            $this->line("  ... and " . ($products->count() - 20) . " more products");
        }

        return 0;
    }
}
