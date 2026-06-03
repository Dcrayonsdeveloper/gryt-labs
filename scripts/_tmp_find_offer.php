<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

App\Models\Tenant::find('natually')->run(function() {
    // Check pages table for offer pages
    $pages = DB::table('pages')->where('slug', 'like', '%offer%')->get();
    echo "=== OFFER PAGES ===" . PHP_EOL;
    foreach ($pages as $p) {
        echo "id={$p->id} slug={$p->slug} title={$p->title}" . PHP_EOL;
        echo "content (first 500): " . substr($p->content ?? '', 0, 500) . PHP_EOL . PHP_EOL;
    }

    // Check settings for offer-related keys
    $settings = DB::table('settings')->where('key', 'like', '%offer%')->get();
    echo "=== OFFER SETTINGS ===" . PHP_EOL;
    foreach ($settings as $s) {
        echo "{$s->key} = " . substr($s->value ?? '', 0, 300) . PHP_EOL;
    }

    // Check banners/sliders
    $banners = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema='public' AND table_name LIKE '%banner%' OR table_name LIKE '%slider%'");
    echo PHP_EOL . "=== BANNER/SLIDER TABLES ===" . PHP_EOL;
    foreach ($banners as $b) echo $b->table_name . PHP_EOL;
});
