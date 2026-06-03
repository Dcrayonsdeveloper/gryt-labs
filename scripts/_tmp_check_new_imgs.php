<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
tenancy()->initialize(App\Models\Tenant::find('urbanindia'));

echo "=== New blog images check ===\n";
$newBlogs = App\Models\BlogPost::whereIn('id', [36,37,38,39,40,41])->get();
foreach ($newBlogs as $b) {
    $path = storage_path('app/public/' . $b->featured_image);
    $exists = file_exists($path) ? 'OK' : 'MISSING';
    echo "  {$b->id}: {$b->featured_image} => {$exists}\n";
}

echo "\n=== All blogs missing images on disk ===\n";
$all = App\Models\BlogPost::whereNotNull('featured_image')->where('featured_image', '!=', '')->get();
$missing = 0;
foreach ($all as $b) {
    $path = storage_path('app/public/' . $b->featured_image);
    if (!file_exists($path)) {
        echo "  MISSING: {$b->slug} => {$b->featured_image}\n";
        $missing++;
    }
}
echo "Missing images: {$missing}/" . $all->count() . "\n";
