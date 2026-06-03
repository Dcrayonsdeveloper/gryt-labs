<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('urbanindia'));
$sql = file_get_contents(__DIR__ . '/_tmp_urbanindia.sql');

$targets = [
    21 => 'menstrual-hygiene-kit-essential',
    16 => 'tampons-vs-pads-difference',
];

foreach ($targets as $id => $destSlug) {
    $marker = '(' . $id . ', ';
    $pos = strpos($sql, $marker);
    if ($pos === false) { echo "$destSlug: NOT FOUND\n"; continue; }

    $p = $pos + strlen($marker);

    // Skip title (quoted string)
    if ($sql[$p] === "'") {
        $p++;
        while ($p < strlen($sql)) {
            if ($sql[$p] === '\\') { $p += 2; continue; }
            if ($sql[$p] === "'") { $p++; break; }
            $p++;
        }
    }

    // Skip ", '"
    while ($p < strlen($sql) && ($sql[$p] === ',' || $sql[$p] === ' ')) $p++;
    if ($sql[$p] === "'") $p++; // skip opening quote

    // Read content until unescaped quote
    $content = '';
    while ($p < strlen($sql)) {
        if ($sql[$p] === '\\' && $p + 1 < strlen($sql)) {
            $next = $sql[$p + 1];
            if ($next === "'") { $content .= "'"; $p += 2; continue; }
            if ($next === "\\") { $content .= "\\"; $p += 2; continue; }
            if ($next === "n") { $content .= "\n"; $p += 2; continue; }
            if ($next === "r") { $content .= "\r"; $p += 2; continue; }
            if ($next === '"') { $content .= '"'; $p += 2; continue; }
            $content .= $sql[$p]; $p++; continue;
        }
        if ($sql[$p] === "'") break;
        $content .= $sql[$p];
        $p++;
    }

    $content = @iconv('UTF-8', 'UTF-8//IGNORE', $content);

    $blog = App\Models\BlogPost::where('slug', $destSlug)->first();
    if ($blog && strlen($content) > 200) {
        $blog->content = $content;
        $blog->save();
        echo "$destSlug: " . strlen($content) . " chars OK\n";
    } else {
        echo "$destSlug: content=" . strlen($content) . " blog=" . ($blog ? 'found' : 'NOT FOUND') . "\n";
    }
}

$full = App\Models\BlogPost::whereRaw("LENGTH(content) > 200")->count();
$total = App\Models\BlogPost::count();
echo "\nFinal: $full/$total blogs with full content\n";
