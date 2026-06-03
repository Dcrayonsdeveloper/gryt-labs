<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('urbanindia'));

// Fix ugly "aeurs" in slugs (from curly apostrophes)
$blogs = App\Models\BlogPost::where('slug', 'LIKE', '%aeurs%')->get();
echo "Blogs with 'aeurs' in slug: {$blogs->count()}\n";

foreach ($blogs as $b) {
    $newSlug = str_replace('aeurs', '', $b->slug);
    $newSlug = preg_replace('/-{2,}/', '-', $newSlug); // collapse double dashes
    $newSlug = trim($newSlug, '-');

    // Ensure unique
    if (App\Models\BlogPost::where('slug', $newSlug)->where('id', '!=', $b->id)->exists()) {
        $newSlug .= '-' . $b->id;
    }

    echo "  {$b->slug} → {$newSlug}\n";
    $b->slug = $newSlug;
    $b->save();
}

// Also fix titles with encoding issues
$blogs2 = App\Models\BlogPost::where('title', 'LIKE', '%â€%')->get();
foreach ($blogs2 as $b) {
    $clean = $b->title;
    $clean = str_replace(['â€™', 'â€œ', 'â€', 'â€˜'], ["'", '"', '"', "'"], $clean);
    if ($clean !== $b->title) {
        echo "  Title fix: {$b->slug}\n";
        $b->title = $clean;
        $b->save();
    }
}

// Check for duplicate blogs (same title, different slug)
echo "\nChecking for duplicates...\n";
$titles = App\Models\BlogPost::pluck('title', 'id')->toArray();
$seen = [];
$dupes = [];
foreach ($titles as $id => $title) {
    $key = strtolower(trim(preg_replace('/[^a-z0-9]/', '', strtolower($title))));
    if (isset($seen[$key])) {
        $dupes[] = ['keep' => $seen[$key], 'delete' => $id, 'title' => $title];
    } else {
        $seen[$key] = $id;
    }
}

if ($dupes) {
    echo "  Found " . count($dupes) . " duplicates:\n";
    foreach ($dupes as $d) {
        echo "    Duplicate ID {$d['delete']}: {$d['title']} (keeping ID {$d['keep']})\n";
        App\Models\BlogPost::find($d['delete'])->delete();
    }
}

echo "\nFinal blog count: " . App\Models\BlogPost::count() . "\n";
echo "All slugs:\n";
App\Models\BlogPost::orderBy('id')->get()->each(fn($b) => print("  {$b->id}: {$b->slug}\n"));
