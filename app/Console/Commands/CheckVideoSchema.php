<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ReviewSchemaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CheckVideoSchema extends Command
{
    protected $signature = 'reviews:check-video-schema';
    protected $description = 'Check if video JSON-LD structured data is working';

    public function handle(): int
    {
        // Check column exists
        $cols = Schema::getColumnListing('products');
        $hasCol = in_array('video_url', $cols);
        $this->info("video_url column exists: " . ($hasCol ? 'YES' : 'NO'));

        if (!$hasCol) {
            $this->error('video_url column missing from products table!');
            return 1;
        }

        // Check products with video
        $withVideo = Product::whereNotNull('video_url')->where('video_url', '!=', '')->count();
        $this->info("Products with video_url: {$withVideo}");

        $product = Product::whereNotNull('video_url')
            ->where('video_url', '!=', '')
            ->with(['images', 'brand', 'approvedReviews.user', 'questions.answers'])
            ->first();

        if (!$product) {
            $this->warn('No products have a video_url set.');

            // Show sample products
            $this->info("\nSample products (first 5):");
            Product::take(5)->get()->each(function ($p) {
                $this->line("  ID:{$p->id} - {$p->name} | video_url: " . ($p->video_url ?: 'NULL'));
            });
            return 0;
        }

        $this->info("\nProduct: {$product->name}");
        $this->info("Video URL: {$product->video_url}");

        $schema = app(ReviewSchemaService::class)->getProductSchema($product);

        $this->newLine();
        $this->line(json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        // Highlight video section
        $this->newLine();
        if (isset($schema['video'])) {
            $this->info('VIDEO in JSON-LD: YES');
            $this->line(json_encode($schema['video'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->error('VIDEO in JSON-LD: NO — missing from schema!');
        }

        return 0;
    }
}
