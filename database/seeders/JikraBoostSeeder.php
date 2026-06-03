<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class JikraBoostSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Create/assign "Jikra" brand to all products ──
        $brand = Brand::firstOrCreate(
            ['slug' => 'jikra'],
            [
                'name' => 'Jikra',
                'description' => 'Jikra - Quality products for everyday life.',
                'is_active' => true,
                'is_featured' => true,
                'position' => 0,
            ]
        );

        $updated = Product::where('is_active', true)
            ->whereNull('brand_id')
            ->update(['brand_id' => $brand->id]);

        $this->command->info("Brand 'Jikra' (ID: {$brand->id}) assigned to {$updated} products.");

        // ── 2. Seed 10+ reviews per product (rating 3.8–4.6) ──
        $names = [
            'Priya S.', 'Rahul M.', 'Anita K.', 'Vikram P.', 'Sneha D.',
            'Amit R.', 'Pooja G.', 'Karan T.', 'Neha V.', 'Suresh B.',
            'Divya L.', 'Arjun N.', 'Meera J.', 'Ravi C.', 'Swati H.',
            'Deepak W.', 'Kavita F.', 'Manoj E.', 'Rina Q.', 'Sanjay U.',
        ];

        $reviewTitles = [
            5 => ['Absolutely love it!', 'Best purchase ever!', 'Exceeded expectations!', 'Must buy!', 'Perfect quality!'],
            4 => ['Great product', 'Very satisfied', 'Good value for money', 'Happy with purchase', 'Recommended!'],
            3 => ['Decent product', 'Okay for the price', 'Average quality', 'Does the job'],
        ];

        $reviewBodies = [
            5 => [
                'Amazing quality! Delivered on time and exactly as described. Very happy with this purchase.',
                'This is exactly what I was looking for. The quality is premium and worth every rupee.',
                'Superb product! My family loves it. Will definitely buy more from Jikra.',
                'Outstanding quality and fast delivery. Highly recommend to everyone!',
                'Best product in this price range. Jikra never disappoints!',
            ],
            4 => [
                'Good quality product. Packaging was great and delivery was fast.',
                'Nice product overall. Minor improvements could be made but I am satisfied.',
                'Value for money. The product works well and looks good too.',
                'Happy with my purchase. Would recommend to friends and family.',
                'Solid product. Slightly better than what I expected at this price.',
            ],
            3 => [
                'Product is okay. It works as expected but nothing extraordinary.',
                'Decent quality for the price. Could be better in some aspects.',
                'Average product. Does what it is supposed to do.',
            ],
        ];

        $products = Product::where('is_active', true)->get();
        $totalReviews = 0;

        foreach ($products as $product) {
            // Skip if product already has 10+ reviews
            if ($product->reviews()->count() >= 10) {
                continue;
            }

            // Target avg rating between 3.8 and 4.6
            $targetAvg = round(mt_rand(38, 46) / 10, 1);

            // Generate 10-15 reviews
            $numReviews = mt_rand(10, 15);
            $ratings = $this->generateRatingsForAverage($targetAvg, $numReviews);

            foreach ($ratings as $i => $rating) {
                $ratingBucket = $rating >= 5 ? 5 : ($rating >= 4 ? 4 : 3);
                $name = $names[array_rand($names)];
                $titles = $reviewTitles[$ratingBucket];
                $bodies = $reviewBodies[$ratingBucket];

                $daysAgo = mt_rand(3, 90);

                Review::create([
                    'product_id' => $product->id,
                    'guest_name' => $name,
                    'guest_email' => strtolower(str_replace([' ', '.'], '', $name)) . mt_rand(10, 99) . '@gmail.com',
                    'rating' => $rating,
                    'title' => $titles[array_rand($titles)],
                    'content' => $bodies[array_rand($bodies)],
                    'is_approved' => true,
                    'is_verified_purchase' => (bool) mt_rand(0, 1),
                    'is_generated' => true,
                    'status' => 'approved',
                    'helpful_count' => mt_rand(0, 12),
                    'unhelpful_count' => mt_rand(0, 2),
                    'created_at' => Carbon::now()->subDays($daysAgo),
                    'updated_at' => Carbon::now()->subDays($daysAgo),
                ]);

                $totalReviews++;
            }

            // Update product cached rating
            $avgRating = $product->reviews()->where('is_approved', true)->avg('rating');
            $reviewCount = $product->reviews()->where('is_approved', true)->count();
            $product->update([
                'rating' => round($avgRating, 1),
                'review_count' => $reviewCount,
            ]);
        }

        $this->command->info("Created {$totalReviews} reviews across {$products->count()} products.");

        // ── 3. Create coupons/deals ──
        $coupons = [
            [
                'code' => 'WELCOME20',
                'name' => 'First Order - 20% Off',
                'description' => 'Get 20% off on your first order!',
                'type' => 'percentage',
                'value' => 20,
                'max_discount' => 200,
                'min_order_amount' => 499,
                'usage_limit' => null,
                'usage_per_user' => 1,
                'is_active' => true,
                'auto_apply' => false,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(6),
            ],
            [
                'code' => 'JIKRA10',
                'name' => 'Flat 10% Off',
                'description' => 'Flat 10% off on orders above ₹999',
                'type' => 'percentage',
                'value' => 10,
                'max_discount' => 500,
                'min_order_amount' => 999,
                'usage_limit' => null,
                'usage_per_user' => 3,
                'is_active' => true,
                'auto_apply' => false,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(3),
            ],
            [
                'code' => 'SAVE100',
                'name' => 'Flat ₹100 Off',
                'description' => 'Flat ₹100 off on orders above ₹599',
                'type' => 'fixed',
                'value' => 100,
                'max_discount' => 100,
                'min_order_amount' => 599,
                'usage_limit' => null,
                'usage_per_user' => 5,
                'is_active' => true,
                'auto_apply' => false,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(3),
            ],
            [
                'code' => 'MEGA15',
                'name' => '15% Off on ₹1499+',
                'description' => 'Get 15% off on orders above ₹1499',
                'type' => 'percentage',
                'value' => 15,
                'max_discount' => 750,
                'min_order_amount' => 1499,
                'usage_limit' => null,
                'usage_per_user' => 2,
                'is_active' => true,
                'auto_apply' => false,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(3),
            ],
        ];

        $created = 0;
        foreach ($coupons as $data) {
            if (!Coupon::where('code', $data['code'])->exists()) {
                Coupon::create($data);
                $created++;
            }
        }

        $this->command->info("Created {$created} new coupons. Total coupons: " . Coupon::count());
    }

    private function generateRatingsForAverage(float $targetAvg, int $count): array
    {
        $ratings = [];
        $sum = 0;

        for ($i = 0; $i < $count - 1; $i++) {
            // Weighted towards 4 and 5
            $rand = mt_rand(1, 100);
            if ($rand <= 40) {
                $rating = 5;
            } elseif ($rand <= 75) {
                $rating = 4;
            } elseif ($rand <= 90) {
                $rating = 3;
            } else {
                $rating = mt_rand(2, 3);
            }
            $ratings[] = $rating;
            $sum += $rating;
        }

        // Adjust last rating to hit target average
        $lastRating = round($targetAvg * $count - $sum);
        $lastRating = max(1, min(5, $lastRating));
        $ratings[] = (int) $lastRating;

        shuffle($ratings);
        return $ratings;
    }
}
