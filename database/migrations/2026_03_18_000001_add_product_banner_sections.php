<?php

use App\Models\HomepageSection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $maxPosition = HomepageSection::max('position') ?? 0;

        // Copy banner images from public/images to storage if they exist
        $imageMap = [
            'banner-lighter.jpg' => 'sections/banner-lighter.jpg',
            'banner-coffee-mug.jpg' => 'sections/banner-coffee-mug.jpg',
        ];

        foreach ($imageMap as $source => $dest) {
            $sourcePath = public_path("images/{$source}");
            if (File::exists($sourcePath) && !Storage::disk('public')->exists($dest)) {
                Storage::disk('public')->makeDirectory('sections');
                Storage::disk('public')->put($dest, File::get($sourcePath));
            }
        }

        HomepageSection::firstOrCreate(
            ['key' => 'product_banner_1'],
            [
                'title' => 'Jikra Electric Lighter',
                'subtitle' => null,
                'type' => 'product_banner',
                'image_url' => 'sections/banner-lighter.jpg',
                'button_text' => 'Shop Now',
                'button_link' => '/product/electric-lighter',
                'position' => $maxPosition + 1,
                'is_active' => true,
            ]
        );

        HomepageSection::firstOrCreate(
            ['key' => 'product_banner_2'],
            [
                'title' => 'Jikra Coffee Mug',
                'subtitle' => null,
                'type' => 'product_banner',
                'image_url' => 'sections/banner-coffee-mug.jpg',
                'button_text' => 'Shop Now',
                'button_link' => '/product/temprature-coffee-mug',
                'position' => $maxPosition + 2,
                'is_active' => true,
            ]
        );
    }

    public function down(): void
    {
        HomepageSection::whereIn('key', ['product_banner_1', 'product_banner_2'])->delete();
    }
};
