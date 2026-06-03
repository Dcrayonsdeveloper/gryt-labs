<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

App\Models\Tenant::find('natually')->run(function() {
    $fixes = [
        15 => '/pages/gallery',
        14 => '/pages/about-us',
        16 => '/pages/contact',
        18 => '/pages/offers',
        31 => '/pages/contact',
        30 => '/pages/about-us',
        29 => '/pages/contact',
        25 => '/pages/about-us',
    ];
    foreach($fixes as $id => $url) {
        DB::table('navigation_menus')->where('id', $id)->update(['url' => $url]);
        echo "Updated ID $id => $url" . PHP_EOL;
    }
    Illuminate\Support\Facades\Cache::flush();
    echo "Cache flushed." . PHP_EOL;
});
