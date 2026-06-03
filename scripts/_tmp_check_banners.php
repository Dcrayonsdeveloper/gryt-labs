<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('urbanindia'));

echo "=== BANNERS ===\n";
$banners = App\Models\Banner::all();
foreach ($banners as $b) {
    echo "ID:{$b->id} | image:{$b->image} | title:{$b->title} | subtitle:{$b->subtitle} | link:{$b->link}\n";
}
echo "Total: " . $banners->count() . "\n";

echo "\n=== BLOG IMAGES (sample) ===\n";
$blogs = App\Models\BlogPost::whereNotNull('featured_image')->where('featured_image', '!=', '')->limit(5)->get();
foreach ($blogs as $b) {
    echo "{$b->slug} => {$b->featured_image}\n";
}
echo "Blogs with images: " . App\Models\BlogPost::whereNotNull('featured_image')->where('featured_image', '!=', '')->count() . "\n";
echo "Total blogs: " . App\Models\BlogPost::count() . "\n";

echo "\n=== BANNER FILES ON DISK ===\n";
$bannerDir = storage_path('app/public/products/urbanindia/banners');
if (is_dir($bannerDir)) {
    foreach (scandir($bannerDir) as $f) {
        if ($f === '.' || $f === '..') continue;
        echo "  $f (" . round(filesize("$bannerDir/$f") / 1024) . " KB)\n";
    }
} else {
    echo "  Directory not found: $bannerDir\n";
}
