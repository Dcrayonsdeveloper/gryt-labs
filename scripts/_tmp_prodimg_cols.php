<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('urbanindia'));

$cols = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name='product_images' ORDER BY ordinal_position");
echo "Columns: ";
foreach ($cols as $c) echo $c->column_name . ', ';
echo "\n\n";

$rows = DB::table('product_images')->limit(5)->get();
foreach ($rows as $r) {
    echo json_encode($r) . "\n";
}
echo "Total: " . DB::table('product_images')->count() . "\n";

// Also check testimonials columns
$tcols = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name='testimonials' ORDER BY ordinal_position");
echo "\nTestimonial columns: ";
foreach ($tcols as $c) echo $c->column_name . ', ';
echo "\n";
$t = DB::table('testimonials')->first();
if ($t) echo json_encode($t) . "\n";
