<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

App\Models\Tenant::find('natually')->run(function() {
    // Clear deleted_at on ALL 189 remaining products
    $updated = DB::table('products')->whereNotNull('deleted_at')->update(['deleted_at' => null]);
    echo "Restored {$updated} soft-deleted products" . PHP_EOL;

    echo PHP_EOL . "Total: " . DB::table('products')->count() . PHP_EOL;
    echo "Not deleted: " . DB::table('products')->whereNull('deleted_at')->count() . PHP_EOL;
    echo "Active: " . DB::table('products')->where('is_active', true)->whereNull('deleted_at')->count() . PHP_EOL;
    echo "Inactive: " . DB::table('products')->where('is_active', false)->whereNull('deleted_at')->count() . PHP_EOL;
    echo PHP_EOL . "Model count: " . App\Models\Product::count() . PHP_EOL;
    echo "Model active: " . App\Models\Product::where('is_active', true)->count() . PHP_EOL;
});
