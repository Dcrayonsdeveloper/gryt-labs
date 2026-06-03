<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

tenancy()->initialize(\App\Models\Tenant::find('ayurvexa'));

$products = \App\Models\Product::where('is_active', true)->get();
foreach ($products as $p) {
    echo "===== PRODUCT ID={$p->id} =====\n";
    echo "Name: {$p->name}\n";
    echo "Slug: {$p->slug}\n";
    echo "Short Desc: {$p->short_description}\n";
    echo "Description:\n" . strip_tags($p->description) . "\n";
    echo "\n";
}
