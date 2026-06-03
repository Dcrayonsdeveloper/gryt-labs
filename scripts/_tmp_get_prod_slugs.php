<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('urbanindia'));

echo "=== BLOG SLUGS ===\n";
App\Models\BlogPost::pluck('slug')->each(fn($s) => print($s . PHP_EOL));
echo "Total: " . App\Models\BlogPost::count() . "\n";

echo "\n=== PRODUCTS ===\n";
App\Models\Product::all()->each(function($p) {
    $imgCount = $p->images()->count();
    $firstImg = $p->images()->first();
    echo "ID:{$p->id} | {$p->slug} | stock:{$p->stock} | main_image:{$p->main_image} | gallery_images:{$imgCount} | first_gallery:" . ($firstImg ? $firstImg->image_path : 'NONE') . "\n";
});

echo "\n=== REVIEWS ===\n";
App\Models\Review::all()->each(function($r) {
    echo "ID:{$r->id} | product_id:{$r->product_id} | rating:{$r->rating} | guest_name:{$r->guest_name} | content:" . substr($r->content, 0, 50) . "\n";
});

echo "\n=== TESTIMONIALS ===\n";
if (class_exists('App\Models\Testimonial')) {
    App\Models\Testimonial::all()->each(function($t) {
        echo "ID:{$t->id} | name:{$t->customer_name} | content:" . substr($t->content ?? $t->text ?? '', 0, 50) . "\n";
    });
} else {
    // Check homepage_sections for testimonials
    $ts = App\Models\HomepageSection::where('key', 'testimonials')->first();
    if ($ts) {
        echo "Testimonials in homepage_sections: " . substr(json_encode($ts->data ?? $ts->content ?? ''), 0, 200) . "\n";
    }
    // Check settings
    $test = App\Models\Setting::where('key', 'LIKE', '%testimonial%')->get();
    $test->each(fn($s) => print("Setting: {$s->key} = " . substr($s->value, 0, 100) . PHP_EOL));
}

echo "\n=== PRODUCT IMAGES TABLE ===\n";
$imgTable = DB::table('product_images')->get();
foreach ($imgTable as $img) {
    echo "product_id:{$img->product_id} | path:{$img->image_path} | position:" . ($img->position ?? 'N/A') . "\n";
}
