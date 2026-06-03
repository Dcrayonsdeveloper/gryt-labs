<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Review;
use App\Services\ReviewGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DripDailyReviews extends Command
{
    protected $signature = 'reviews:drip-daily
                            {--min=10 : Minimum reviews to drip today}
                            {--max=20 : Maximum reviews to drip today}
                            {--min-rating=3.9 : Minimum target average rating}
                            {--max-rating=4.6 : Maximum target average rating}
                            {--dry-run : Show what would be generated without creating}';

    protected $description = 'Drip new reviews across random products (spread throughout the day at random times)';

    public function handle(ReviewGeneratorService $generator): int
    {
        $min = (int) $this->option('min');
        $max = (int) $this->option('max');
        $minRating = (float) $this->option('min-rating');
        $maxRating = (float) $this->option('max-rating');

        $todayCount = mt_rand($min, $max);

        // Get active products
        $products = Product::where('is_active', true)
            ->withCount(['approvedReviews'])
            ->get();

        if ($products->isEmpty()) {
            $this->error('No active products found.');
            return 1;
        }

        // Weighted random — favor products with fewer reviews
        $maxReviews = $products->max('approved_reviews_count') ?: 1;
        $weighted = $products->flatMap(function ($product) use ($maxReviews) {
            $weight = max(1, (int) ceil(($maxReviews - $product->approved_reviews_count + 1) / max(1, $maxReviews) * 5));
            return array_fill(0, $weight, $product);
        });

        $this->info("Dripping {$todayCount} reviews across random products...");

        if ($this->option('dry-run')) {
            $picks = collect();
            for ($i = 0; $i < $todayCount; $i++) {
                $picks->push($weighted->random());
            }
            $grouped = $picks->countBy(fn ($p) => $p->name);
            foreach ($grouped as $name => $count) {
                $this->line("  Would add {$count} review(s) to \"{$name}\"");
            }
            return 0;
        }

        $generated = 0;
        $productUpdates = [];

        // Generate random timestamps spread across the entire day
        // Each review gets a unique random time between 6am-11pm
        $timestamps = $this->generateRandomTimestamps($todayCount);

        Review::withoutEvents(function () use ($weighted, $todayCount, $generator, $minRating, $maxRating, $timestamps, &$generated, &$productUpdates) {
            for ($i = 0; $i < $todayCount; $i++) {
                $product = $weighted->random();

                // Each product gets a slightly different target avg for variety
                $targetAvg = round($minRating + (mt_rand() / mt_getrandmax()) * ($maxRating - $minRating), 2);
                $rating = $generator->pickRatingForTarget($targetAvg);

                $createdAt = $timestamps[$i];

                $data = [
                    'product_id' => $product->id,
                    'user_id' => null,
                    'guest_name' => $generator->randomIndianName(),
                    'guest_email' => 'review' . mt_rand(10000, 99999) . '@customer.' . parse_url(config('app.url', 'localhost'), PHP_URL_HOST),
                    'rating' => $rating,
                    'title' => $generator->generateTitle($product, $rating),
                    'content' => $generator->generateContent($product, $rating),
                    'pros' => json_encode($generator->generatePros($product, $rating)),
                    'cons' => json_encode($generator->generateCons($product, $rating)),
                    'is_verified_purchase' => $this->randomVerifiedPurchase(),
                    'is_approved' => true,
                    'is_featured' => false,
                    'helpful_count' => mt_rand(0, 15),
                    'unhelpful_count' => mt_rand(0, 2),
                    'status' => 'approved',
                    'is_generated' => true,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];

                DB::table('reviews')->insert($data);
                $generated++;
                $productUpdates[$product->id] = true;

                $this->line("  ★{$rating} for \"{$product->name}\" by {$data['guest_name']} at {$createdAt->format('H:i')}");
            }
        });

        // Update ratings for affected products
        foreach (array_keys($productUpdates) as $productId) {
            $stats = DB::table('reviews')
                ->where('product_id', $productId)
                ->where('is_approved', true)
                ->where('created_at', '<=', now())
                ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as review_count')
                ->first();

            Product::where('id', $productId)->update([
                'rating' => round($stats->avg_rating, 2),
                'review_count' => $stats->review_count,
            ]);
        }

        $this->newLine();
        $this->info("Done! Dripped {$generated} reviews across " . count($productUpdates) . " products.");

        return 0;
    }

    /**
     * Generate random timestamps spread naturally across the day.
     * Mimics real human review patterns — more reviews during daytime/evening.
     */
    private function generateRandomTimestamps(int $count): array
    {
        $timestamps = [];

        // Hour weights — busier during daytime and evening (realistic human pattern)
        $hourWeights = [
            6 => 1, 7 => 2, 8 => 3, 9 => 5, 10 => 6, 11 => 7,
            12 => 5, 13 => 6, 14 => 7, 15 => 6, 16 => 5, 17 => 6,
            18 => 8, 19 => 9, 20 => 10, 21 => 8, 22 => 5, 23 => 2,
        ];

        $totalWeight = array_sum($hourWeights);

        for ($i = 0; $i < $count; $i++) {
            // Weighted random hour
            $rand = mt_rand(1, $totalWeight);
            $cumulative = 0;
            $hour = 12;
            foreach ($hourWeights as $h => $w) {
                $cumulative += $w;
                if ($rand <= $cumulative) {
                    $hour = $h;
                    break;
                }
            }

            $timestamps[] = now()
                ->setTime($hour, mt_rand(0, 59), mt_rand(0, 59))
                ->addSeconds(mt_rand(-120, 120)); // ±2 min jitter
        }

        // Sort chronologically
        usort($timestamps, fn ($a, $b) => $a->timestamp <=> $b->timestamp);

        return $timestamps;
    }

    /**
     * Weighted verified purchase — 70% verified, 30% not (more realistic).
     */
    private function randomVerifiedPurchase(): bool
    {
        return mt_rand(1, 100) <= 70;
    }
}
