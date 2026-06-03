<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

App\Models\Tenant::find('natually')->run(function() {
    $link = '/products/anti-ageing-facial-kit';

    $content = '<section class="py-8">
  <h1 class="text-3xl font-bold mb-6 text-center">Special Offers</h1>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-6xl mx-auto">
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
    DB::table('pages')->where('slug', 'latest-offers')->update(['content' => $content]);
    DB::table('pages')->where('slug', 'festive-offer')->update(['content' => $content]);
    Illuminate\Support\Facades\Cache::flush();
    echo "Updated — 4 images in one row." . PHP_EOL;
});
