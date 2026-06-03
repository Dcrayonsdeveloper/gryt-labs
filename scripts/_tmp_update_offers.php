<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

App\Models\Tenant::find('natually')->run(function() {
    $link = '/products/anti-ageing-facial-kit';

    // Update offers_collage_json — only 4 images, all link to anti-ageing facial kit
    $collage = [
        ['image' => '/tenants/natually/images/offers/1000437185.jpg', 'alt' => 'Natually Facial Kit Bonanza - Buy 2 Get 1 Free', 'url' => $link],
        ['image' => '/tenants/natually/images/offers/1000437187.jpg', 'alt' => 'Bridal Facial Kit Special Offer - Buy 2 Get 1 Free', 'url' => $link],
        ['image' => '/tenants/natually/images/offers/1000437189.jpg', 'alt' => 'Vitamin C Facial Kit Special Offer - Buy 2 Get 1 Free', 'url' => $link],
        ['image' => '/tenants/natually/images/offers/1000437193.jpg', 'alt' => 'Anti-Ageing Facial Kit Special Offer', 'url' => $link],
    ];

    DB::table('settings')->where('key', 'offers_collage_json')->update([
        'value' => json_encode($collage),
    ]);
    echo "Updated offers_collage_json with 4 images" . PHP_EOL;

    // Update the offers page content to show only these 4 images
    $content = '<section class="py-8">
  <h1 class="text-3xl font-bold mb-6 text-center">Special Offers</h1>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-5xl mx-auto">
    <a href="' . $link . '" class="block rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-shadow">
      <img src="/tenants/natually/images/offers/1000437185.jpg" alt="Natually Facial Kit Bonanza" class="w-full h-auto">
    </a>
    <a href="' . $link . '" class="block rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-shadow">
      <img src="/tenants/natually/images/offers/1000437187.jpg" alt="Bridal Facial Kit Offer" class="w-full h-auto">
    </a>
    <a href="' . $link . '" class="block rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-shadow">
      <img src="/tenants/natually/images/offers/1000437189.jpg" alt="Vitamin C Facial Kit Offer" class="w-full h-auto">
    </a>
    <a href="' . $link . '" class="block rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-shadow">
      <img src="/tenants/natually/images/offers/1000437193.jpg" alt="Anti-Ageing Facial Kit Offer" class="w-full h-auto">
    </a>
  </div>
</section>';

    DB::table('pages')->where('slug', 'offers')->update(['content' => $content]);
    echo "Updated offers page content" . PHP_EOL;

    // Also update latest-offers page
    DB::table('pages')->where('slug', 'latest-offers')->update(['content' => $content]);
    echo "Updated latest-offers page content" . PHP_EOL;

    // Also update festive-offer page
    DB::table('pages')->where('slug', 'festive-offer')->update(['content' => $content]);
    echo "Updated festive-offer page content" . PHP_EOL;

    // Clear cache
    Illuminate\Support\Facades\Cache::flush();
    echo PHP_EOL . "Cache flushed." . PHP_EOL;

    // Verify
    $val = DB::table('settings')->where('key', 'offers_collage_json')->value('value');
    echo PHP_EOL . "Verified collage JSON: " . $val . PHP_EOL;
});
