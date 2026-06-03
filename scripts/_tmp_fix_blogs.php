<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('urbanindia'));

// Map slug -> image filename
$imageMap = [
    'stay-fresh-and-hygienic-with-period-box' => 'period-box.jpeg',
    'how-to-prepare-for-periods-with-first-period-kit' => 'firstperiod.jpeg',
    'complete-period-kit-essentials' => 'Period Kit.jpeg',
    'period-panties-for-women-changing-menstrual-care' => 'Period Panties for Women.jpeg',
    'herbal-tea-for-pms-symptoms' => 'Herbal Tea For Period.jpeg',
    'what-is-a-period-hamper' => 'period hamper.jpeg',
    'period-care-kit-for-emergencies' => 'period care kit.jpeg',
    'tampons-vs-pads-difference' => 'Tampons vs Pads.jpeg',
    'choose-right-period-kit-lifestyle' => 'period kit for women.jpeg',
    'uti-delay-menstruation' => 'UTI can delay your period.jpeg',
    'period-wellness-kit-vs-painkillers' => 'Period Wellness Kit.jpeg',
    'menstrual-hygiene-kit-essential' => 'Hygiene Kit.jpeg',
    'period-hamper-for-her' => 'Period Hamper for Her.jpeg',
    'menstrual-care-box-stress-free-periods' => 'menstrual care box.jpeg',
    'first-period-kit-anxiety-help' => 'First Period Kit.jpeg',
    'what-is-sanitary-kit-guide' => 'Sanitary Kit.png',
    'what-is-happy-period-box' => 'Happy period box.jpeg',
    'what-to-put-in-period-box' => 'period box.jpeg',
    'period-pamper-kit-for-cramps' => 'period pamper kit.jpeg',
    'period-comfort-kit-relieve-cramps' => 'period comfort kit.jpeg',
    '5-things-to-keep-in-bag-during-period' => 'period essentials kit.jpg',
    'first-period-kit-without-stress' => 'First Period Kit.png',
    'first-period-kit-supports-everyday-health' => 'menstrual kit for periods.jpeg',
    'menstrual-kit-sense-of-ease' => 'menstrual kit.jpeg',
    'modern-hygiene-kit-every-lifestyle' => 'hygine kit.jpeg',
    'period-wellness-kit-improve-monthly-routine' => 'wellness kit.jpeg',
    'period-starter-kit-essentials' => 'period starter kit.jpeg',
    'menstrual-care-box-breakdown' => 'menstrual care box.jpeg',
    'understanding-period-hamper-resource' => 'period hamper for girls.jpeg',
];

$fixed = 0;
foreach ($imageMap as $slug => $img) {
    $blog = App\Models\BlogPost::where('slug', $slug)->first();
    if (!$blog) { echo "  NOT FOUND: $slug\n"; continue; }

    $blog->featured_image = 'blogs/urbanindia/' . $img;
    if (!$blog->published_at) {
        $blog->published_at = $blog->created_at;
    }
    $blog->save();
    $fixed++;
}

// Also set published_at on any remaining blogs
$remaining = App\Models\BlogPost::whereNull('published_at')->get();
foreach ($remaining as $b) {
    $b->published_at = $b->created_at;
    $b->save();
}

echo "Fixed $fixed blog images + published_at\n";
echo "Total blogs: " . App\Models\BlogPost::count() . "\n";
echo "Published blogs: " . App\Models\BlogPost::whereNotNull('published_at')->count() . "\n";
echo "Blogs with images: " . App\Models\BlogPost::whereNotNull('featured_image')->where('featured_image', '!=', '')->count() . "\n";
