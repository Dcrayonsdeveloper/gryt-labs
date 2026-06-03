<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('urbanindia'));

$banners = [
    [
        'id' => 5,
        'title' => "Your Period Doesn't Need a Pause Button. It Needs a Ritual.",
        'subtitle' => 'The first expert-backed wellness box designed for all 30 days of your cycle.',
        'button_text' => 'Shop Now',
        'overlay_style' => 'left-dark',
    ],
    [
        'id' => 6,
        'title' => 'Happy Period Box',
        'subtitle' => 'Everything you need for comfortable, stress-free periods. Curated with love.',
        'button_text' => 'Explore',
        'overlay_style' => 'left-dark',
    ],
    [
        'id' => 7,
        'title' => 'Complete Period Care Range',
        'subtitle' => 'Natural, safe, and eco-friendly products for every woman.',
        'button_text' => 'View Products',
        'overlay_style' => 'left-dark',
    ],
];

foreach ($banners as $data) {
    $banner = App\Models\Banner::find($data['id']);
    if (!$banner) { echo "Banner {$data['id']} not found\n"; continue; }
    $banner->title = $data['title'];
    $banner->subtitle = $data['subtitle'];
    $banner->button_text = $data['button_text'];
    $banner->overlay_style = $data['overlay_style'];
    $banner->save();
    echo "Updated banner {$data['id']}: {$data['title']}\n";
}

echo "\nAll banners:\n";
App\Models\Banner::all()->each(function($b) {
    echo "  ID:{$b->id} title='{$b->title}' subtitle='{$b->subtitle}' btn='{$b->button_text}' overlay={$b->overlay_style}\n";
});
