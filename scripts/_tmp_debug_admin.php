<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check which DB natually tenant uses
$tenant = App\Models\Tenant::find('natually');
echo "Tenant DB: " . $tenant->tenancy_db_name . PHP_EOL;

$tenant->run(function() {
    // Raw DB check
    echo "Current DB: " . DB::connection()->getDatabaseName() . PHP_EOL;
    echo PHP_EOL;

    // Check if soft deletes are in play
    $columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'products' ORDER BY ordinal_position");
    $colNames = array_map(fn($c) => $c->column_name, $columns);
    echo "Product columns: " . implode(', ', $colNames) . PHP_EOL . PHP_EOL;

    $hasDeletedAt = in_array('deleted_at', $colNames);
    echo "Has deleted_at: " . ($hasDeletedAt ? 'YES' : 'NO') . PHP_EOL;

    if ($hasDeletedAt) {
        echo "Total (no soft delete filter): " . DB::table('products')->count() . PHP_EOL;
        echo "Not soft-deleted: " . DB::table('products')->whereNull('deleted_at')->count() . PHP_EOL;
        echo "Soft-deleted: " . DB::table('products')->whereNotNull('deleted_at')->count() . PHP_EOL;
        echo "Active + not deleted: " . DB::table('products')->where('is_active', true)->whereNull('deleted_at')->count() . PHP_EOL;
    } else {
        echo "Total: " . DB::table('products')->count() . PHP_EOL;
        echo "Active: " . DB::table('products')->where('is_active', true)->count() . PHP_EOL;
    }

    // Check if Product model uses SoftDeletes
    $model = new App\Models\Product();
    $traits = class_uses_recursive($model);
    echo PHP_EOL . "Product model uses SoftDeletes: " . (isset($traits['Illuminate\Database\Eloquent\SoftDeletes']) ? 'YES' : 'NO') . PHP_EOL;

    // Check what admin controller query looks like
    echo PHP_EOL . "Model query count: " . App\Models\Product::count() . PHP_EOL;
    echo "Model active count: " . App\Models\Product::where('is_active', true)->count() . PHP_EOL;
});
