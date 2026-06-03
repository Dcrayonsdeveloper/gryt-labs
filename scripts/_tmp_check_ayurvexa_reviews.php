<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

tenancy()->initialize(\App\Models\Tenant::find('ayurvexa'));

$products = \App\Models\Product::where('is_active', true)->get();
foreach ($products as $p) {
    $reviews = \Illuminate\Support\Facades\DB::table('reviews')
        ->where('product_id', $p->id)
        ->where('is_approved', true)
        ->selectRaw('COUNT(*) as cnt, AVG(rating) as avg_r, MIN(rating) as min_r, MAX(rating) as max_r')
        ->first();
    echo "ID={$p->id} | {$p->name} | reviews={$reviews->cnt} avg={$reviews->avg_r} min={$reviews->min_r} max={$reviews->max_r}\n";
}

// Also check if there are reviews with product_ids that don't exist
$orphans = \Illuminate\Support\Facades\DB::table('reviews')
    ->leftJoin('products', 'reviews.product_id', '=', 'products.id')
    ->whereNull('products.id')
    ->count();
echo "\nOrphan reviews (product_id not in products table): {$orphans}\n";

// Check total
$total = \Illuminate\Support\Facades\DB::table('reviews')->count();
echo "Total reviews in DB: {$total}\n";

// Show sample: first 3 reviews from each product
echo "\n--- Sample reviews per product ---\n";
foreach ($products as $p) {
    $samples = \Illuminate\Support\Facades\DB::table('reviews')
        ->where('product_id', $p->id)
        ->orderByDesc('created_at')
        ->limit(3)
        ->get(['id', 'product_id', 'guest_name', 'rating', 'title', 'created_at']);
    echo "\nProduct {$p->id}: {$p->name}\n";
    foreach ($samples as $r) {
        echo "  review #{$r->id} | pid={$r->product_id} | {$r->guest_name} | {$r->rating}* | {$r->title} | {$r->created_at}\n";
    }
}
