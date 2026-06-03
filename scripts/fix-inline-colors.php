<?php
/**
 * Replace hardcoded hex colors in inline style="" attributes with CSS variables.
 * This handles cases the Tailwind class replacer couldn't catch.
 *
 * Usage: php scripts/fix-inline-colors.php [--dry-run]
 */

$viewsDir = __DIR__ . '/../resources/views';
$dryRun = in_array('--dry-run', $argv);

// Map: hardcoded hex → CSS variable
$replacements = [
    '#205258' => 'var(--brand-600)',
    '#1b454a' => 'var(--brand-700)',
    '#15383c' => 'var(--brand-800)',
    '#0f2a2e' => 'var(--brand-900)',
    '#091c1f' => 'var(--brand-950)',
    '#307079' => 'var(--brand-500)',
    '#4d8f97' => 'var(--brand-400)',
    '#6ba5ac' => 'var(--brand-300)',
    '#96c0c5' => 'var(--brand-200)',
    '#c0dade' => 'var(--brand-100)',
    '#e6f0f1' => 'var(--brand-50)',
    '#F8931D' => 'var(--brand-accent)',
    '#f8931d' => 'var(--brand-accent)',
    '#E07E0A' => 'var(--brand-accent-dark)',
    '#e07e0a' => 'var(--brand-accent-dark)',
    '#007185' => 'var(--brand-link)',
    '#C7511F' => 'var(--brand-link-hover)',
    '#c7511f' => 'var(--brand-link-hover)',
];

$totalReplacements = 0;
$filesFixed = 0;

$dir = new RecursiveDirectoryIterator($viewsDir);
$iter = new RecursiveIteratorIterator($dir);

foreach ($iter as $f) {
    if ($f->getExtension() !== 'php') continue;
    $path = $f->getPathname();
    $content = file_get_contents($path);
    $original = $content;

    $fileReplacements = 0;

    // Only replace within style="..." attributes
    $content = preg_replace_callback('/style="([^"]*)"/', function ($match) use ($replacements, &$fileReplacements) {
        $style = $match[1];
        $newStyle = $style;
        foreach ($replacements as $hex => $var) {
            $count = substr_count(strtolower($newStyle), strtolower($hex));
            if ($count > 0) {
                $newStyle = str_ireplace($hex, $var, $newStyle);
                $fileReplacements += $count;
            }
        }
        return 'style="' . $newStyle . '"';
    }, $content);

    if ($fileReplacements > 0) {
        $rel = str_replace(str_replace('/', DIRECTORY_SEPARATOR, $viewsDir) . DIRECTORY_SEPARATOR, '', $path);
        $rel = str_replace(DIRECTORY_SEPARATOR, '/', $rel);
        echo sprintf("%-60s %d replacements\n", $rel, $fileReplacements);
        $totalReplacements += $fileReplacements;
        $filesFixed++;

        if (!$dryRun) {
            file_put_contents($path, $content);
        }
    }
}

echo "\n" . ($dryRun ? '[DRY RUN] ' : '') . "Total: $totalReplacements replacements across $filesFixed files\n";
