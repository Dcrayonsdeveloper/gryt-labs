<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;

$tenant = Tenant::find('keratine');
if (!$tenant) { echo "Tenant not found!\n"; exit(1); }

tenancy()->initialize($tenant);
echo "Initialized keratine tenant\n";

// Clear existing banners
DB::table('banners')->truncate();
echo "Cleared existing banners\n";

$banners = [
    [
        'name' => 'Keratine Professional - Nano Plastia Range',
        'title' => 'Keratine Professional',
        'subtitle' => 'Transform Your Hair with Nano Plastia Technology',
        'button_text' => 'Shop Now',
        'position' => 'hero',
        'image_url' => 'banners/keratine_banner_1.jpg',
        'mobile_image_url' => 'banners/keratine_banner_1.jpg',
        'link' => '/products',
        'overlay_style' => 'none',
        'priority' => 1,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Keratine Professional - Hair Care Collection',
        'title' => 'Professional Hair Care',
        'subtitle' => 'Salon-Quality Results at Home',
        'button_text' => 'Explore',
        'position' => 'hero',
        'image_url' => 'banners/keratine_banner_2.jpg',
        'mobile_image_url' => 'banners/keratine_banner_2.jpg',
        'link' => '/products',
        'overlay_style' => 'none',
        'priority' => 2,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Keratine Professional - Premium Products',
        'title' => 'Premium Hair Solutions',
        'subtitle' => 'Trusted by Professionals Worldwide',
        'button_text' => 'Shop Now',
        'position' => 'hero',
        'image_url' => 'banners/keratine_banner_3.jpg',
        'mobile_image_url' => 'banners/keratine_banner_3.jpg',
        'link' => '/products',
        'overlay_style' => 'none',
        'priority' => 3,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ],
];

foreach ($banners as $banner) {
    DB::table('banners')->insert($banner);
}

echo "Created " . DB::table('banners')->count() . " banners\n";
echo "Done!\n";
