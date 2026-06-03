<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
tenancy()->initialize(App\Models\Tenant::find('urbanindia'));

foreach (['products', 'blog_posts', 'coupons'] as $table) {
    echo "=== $table ===\n";
    $cols = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name='$table' ORDER BY ordinal_position");
    echo implode(', ', array_map(fn($c) => $c->column_name, $cols)) . "\n\n";
}

echo "=== Sample product ===\n";
$p = DB::table('products')->first();
echo json_encode($p, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
