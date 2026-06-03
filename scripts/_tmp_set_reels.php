<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Urban India reels (scraped from source site)
$urbanReels = 'DTaD5HhEfxj,DU-tSZcD-JZ,DUX6UF1kXio,DUiWl4UEdr6,DUspUW8kpjG,DVLaAOeCCfy,DVQsRjjjlnY';

tenancy()->initialize(App\Models\Tenant::find('urbanindia'));
App\Models\Setting::set('instagram_collab_reels', $urbanReels);
// Clear cached reels so new ones show up
Illuminate\Support\Facades\Cache::forget('instagram_reels_urbanindia');
echo "Urban India: set " . count(explode(',', $urbanReels)) . " collab reels\n";
echo "  instagram_collab_reels = " . App\Models\Setting::get('instagram_collab_reels') . "\n";

// Saachi Miraai - check their Instagram for reels
tenancy()->end();
tenancy()->initialize(App\Models\Tenant::find('saachimiraai'));
$sachiReels = App\Models\Setting::get('instagram_collab_reels');
if (empty($sachiReels)) {
    echo "\nSaachi Miraai: no reels to set (source site has none)\n";
} else {
    echo "\nSaachi Miraai: already has reels: $sachiReels\n";
}

echo "\nDone.\n";
