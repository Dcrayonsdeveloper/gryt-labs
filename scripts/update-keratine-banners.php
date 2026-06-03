<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('keratine'));
echo "Initialized keratine tenant\n";

DB::table('banners')->truncate();

$banners = [
    [
        'name' => 'Keratine Hair Care Combo',
        'title' => 'Discover Our Keratine Hair Care Combo',
        'subtitle' => 'Your All-in-One Hair Solution',
        'button_text' => 'Shop Now',
        'position' => 'hero',
        'image_url' => 'banners/keratine_desktop_1.jpg',
        'mobile_image_url' => 'banners/keratine_desktop_1.jpg',
        'link' => '/products',
        'overlay_style' => 'none',
        'priority' => 1,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Keratine Argan Oil Hair Spa',
        'title' => 'Say Goodbye to Damaged & Lifeless Hair',
        'subtitle' => 'Argan Oil Hair Spa - Deep Repair & Nourishment',
        'button_text' => 'Shop Now',
        'position' => 'hero',
        'image_url' => 'banners/keratine_desktop_2.jpg',
        'mobile_image_url' => 'banners/keratine_desktop_2.jpg',
        'link' => '/products',
        'overlay_style' => 'none',
        'priority' => 2,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Keratine Professional - New Products',
        'title' => 'New Products from Keratine Professional',
        'subtitle' => 'Best Offers This Month',
        'button_text' => 'Shop Now',
        'position' => 'hero',
        'image_url' => 'banners/keratine_desktop_3.jpg',
        'mobile_image_url' => 'banners/keratine_desktop_3.jpg',
        'link' => '/products',
        'overlay_style' => 'none',
        'priority' => 3,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ],
];

foreach ($banners as $b) {
    DB::table('banners')->insert($b);
}

echo "Updated " . DB::table('banners')->count() . " banners with desktop images\n";
echo "Done!\n";
