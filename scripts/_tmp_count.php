<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

App\Models\Tenant::find('natually')->run(function() {
    echo 'Total products: ' . DB::table('products')->count() . PHP_EOL;
    echo 'Active: ' . DB::table('products')->where('is_active', true)->count() . PHP_EOL;
    echo 'Inactive: ' . DB::table('products')->where('is_active', false)->count() . PHP_EOL;
});
