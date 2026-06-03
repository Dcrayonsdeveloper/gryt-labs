<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use App\Services\ReviewGeneratorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReviewSeeder extends Seeder
{
    private array $indianNames = [
        'Priya Sharma', 'Rahul Verma', 'Sneha Patel', 'Amit Kumar', 'Ananya Reddy',
        'Vikram Singh', 'Meera Iyer', 'Deepak Joshi', 'Kavita Nair', 'Arjun Mehta',
        'Pooja Gupta', 'Suresh Rao', 'Neha Mishra', 'Rajesh Tiwari', 'Divya Kapoor',
        'Manish Agarwal', 'Ritu Saxena', 'Sanjay Malhotra', 'Swati Bhatt', 'Arun Pillai',
        'Geeta Deshmukh', 'Kiran Jain', 'Nitin Bansal', 'Pallavi Kulkarni', 'Rohit Pandey',
        'Sunita Chaudhary', 'Vijay Thakur', 'Aarti Srivastava', 'Gaurav Dubey', 'Lakshmi Menon',
        'Harish Yadav', 'Anjali Bose', 'Prakash Naik', 'Rekha Chauhan', 'Manoj Sethi',
        'Shilpa Rathore', 'Tarun Khanna', 'Uma Devi', 'Naveen Gowda', 'Bhavna Shah',
        'Dinesh Choudhury', 'Falguni Parikh', 'Girish Shetty', 'Heena Ansari', 'Isha Tandon',
        'Jagdish Patil', 'Komal Bajaj', 'Lalit Mittal', 'Madhuri Hegde', 'Nilesh Kale',
    ];

    public function run(): void
    {
        $service = app(ReviewGeneratorService::class);
        $products = Product::all();

        if ($products->isEmpty()) {
            $this->command->warn('No products found. Seed products first.');
            return;
        }

        $this->command->info("Generating reviews for {$products->count()} products...");

        // Disable model events to avoid slow updateRating() on every insert
        Review::withoutEvents(function () use ($products, $service) {
            $bar = $this->command->getOutput()->createProgressBar($products->count());

            foreach ($products as $product) {
                $reviewCount = rand(8, 20);
                $reviews = [];
                $now = now();

                for ($i = 0; $i < $reviewCount; $i++) {
                    $rating = $service->pickRating();
                    $createdAt = $now->copy()->subDays(rand(1, 120))->subHours(rand(0, 23))->subMinutes(rand(0, 59));

                    $reviews[] = [
                        'product_id' => $product->id,
                        'user_id' => null,
                        'guest_name' => $this->indianNames[array_rand($this->indianNames)],
                        'guest_email' => 'review' . rand(1000, 9999) . '@example.com',
                        'rating' => $rating,
                        'title' => $service->generateTitle($product, $rating),
                        'content' => $service->generateContent($product, $rating),
                        'pros' => json_encode($service->generatePros($product, $rating)),
                        'cons' => json_encode($service->generateCons($product, $rating)),
                        'is_verified_purchase' => (bool) rand(0, 1),
                        'is_approved' => true,
                        'is_featured' => $i === 0,
                        'helpful_count' => rand(0, 25),
                        'unhelpful_count' => rand(0, 3),
                        'status' => 'approved',
                        'is_generated' => true,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ];
                }

                DB::table('reviews')->insert($reviews);

                // Update product rating from inserted reviews
                $avgRating = DB::table('reviews')
                    ->where('product_id', $product->id)
                    ->where('is_approved', true)
                    ->avg('rating');

                $count = DB::table('reviews')
                    ->where('product_id', $product->id)
                    ->where('is_approved', true)
                    ->count();

                $product->update([
                    'rating' => round($avgRating, 2),
                    'review_count' => $count,
                ]);

                $bar->advance();
            }

            $bar->finish();
        });

        $totalReviews = Review::count();
        $this->command->newLine();
        $this->command->info("Done! Created {$totalReviews} reviews across {$products->count()} products.");
    }
}
