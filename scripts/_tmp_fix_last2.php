<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('urbanindia'));

$sql = file_get_contents(__DIR__ . '/_tmp_urbanindia.sql');

$mapping = [
    'why-menstrual-hygiene-kit-is-essential' => 'menstrual-hygiene-kit-essential',
    'difference-between-tampons-vs-pads' => 'tampons-vs-pads-difference',
];

// Reuse the parser from v2
function extractContent($sql, $srcSlug) {
    // Find the slug in the SQL
    $marker = "'" . $srcSlug . "'";
    $slugPos = strpos($sql, $marker);
    if ($slugPos === false) return null;

    // Find the start of this row
    $rowStart = strrpos(substr($sql, 0, $slugPos), '(');
    if ($rowStart === false) return null;

    $pos = $rowStart + 1;
    $len = strlen($sql);

    // Parse fields: id, title, content
    // Skip ID (number)
    while ($pos < $len && $sql[$pos] !== ',') $pos++;
    $pos++; // skip comma

    // Skip whitespace
    while ($pos < $len && ctype_space($sql[$pos])) $pos++;

    // Skip title field (quoted string)
    if ($sql[$pos] === "'") {
        $pos++; // skip opening quote
        while ($pos < $len) {
            if ($sql[$pos] === '\\') { $pos += 2; continue; }
            if ($sql[$pos] === "'") { $pos++; break; }
            $pos++;
        }
    }

    // Skip ", '"
    while ($pos < $len && ($sql[$pos] === ',' || ctype_space($sql[$pos]))) $pos++;

    // Now we're at the content field
    if ($sql[$pos] !== "'") return null;
    $pos++; // skip opening quote

    $content = '';
    while ($pos < $len) {
        if ($sql[$pos] === '\\' && $pos + 1 < $len) {
            $next = $sql[$pos + 1];
            if ($next === "'") { $content .= "'"; $pos += 2; continue; }
            if ($next === "\\") { $content .= "\\"; $pos += 2; continue; }
            if ($next === "n") { $content .= "\n"; $pos += 2; continue; }
            if ($next === "r") { $content .= "\r"; $pos += 2; continue; }
            $content .= $sql[$pos];
            $pos++;
            continue;
        }
        if ($sql[$pos] === "'") break;
        $content .= $sql[$pos];
        $pos++;
    }

    return $content;
}

foreach ($mapping as $srcSlug => $destSlug) {
    $content = extractContent($sql, $srcSlug);
    if (!$content || strlen($content) < 100) {
        echo "$destSlug: content not found or too short\n";
        continue;
    }

    // Clean encoding
    $content = @iconv('UTF-8', 'UTF-8//IGNORE', $content);

    $blog = App\Models\BlogPost::where('slug', $destSlug)->first();
    if ($blog) {
        $blog->content = $content;
        $blog->save();
        echo "$destSlug: " . strlen($content) . " chars\n";
    } else {
        echo "$destSlug: blog not found\n";
    }
}

echo "\nBlogs with full content: " . App\Models\BlogPost::whereRaw("LENGTH(content) > 200")->count() . "/" . App\Models\BlogPost::count() . "\n";
