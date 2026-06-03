<?php
/**
 * Replace hardcoded hex colors with semantic Tailwind classes.
 *
 * Mapping:
 *   #205258 (primary teal)    → primary-600
 *   #1b454a (primary dark)    → primary-700
 *   #F8931D (CTA orange)      → accent-500
 *   #E07E0A (CTA dark)        → accent-600
 *   #007185 (link blue)       → link (custom)
 *   #C7511F (link hover)      → link-hover (custom)
 *   #0F1111 (dark text)       → neutral-900 (keep as is — not brand-specific)
 *   #565959 (secondary text)  → neutral-600 (keep as is — not brand-specific)
 */

$viewsDir = $argv[1] ?? 'd:/Projects/jikra-theme-system/resources/views';
$dryRun = in_array('--dry-run', $argv);

// Only replace brand colors, NOT neutral greys/blacks
$replacements = [
    // bg-[#205258] → bg-primary-600
    'bg-[#205258]' => 'bg-primary-600',
    'bg-[#1b454a]' => 'bg-primary-700',
    'bg-[#F8931D]' => 'bg-accent-500',
    'bg-[#f8931d]' => 'bg-accent-500',
    'bg-[#E07E0A]' => 'bg-accent-600',
    'bg-[#e07e0a]' => 'bg-accent-600',

    // text-[#hex] → text-semantic
    'text-[#205258]' => 'text-primary-600',
    'text-[#1b454a]' => 'text-primary-700',
    'text-[#F8931D]' => 'text-accent-500',
    'text-[#f8931d]' => 'text-accent-500',
    'text-[#007185]' => 'text-link',
    'text-[#C7511F]' => 'text-link-hover',
    'text-[#c7511f]' => 'text-link-hover',

    // border-[#hex] → border-semantic
    'border-[#205258]' => 'border-primary-600',
    'border-[#1b454a]' => 'border-primary-700',
    'border-[#007185]' => 'border-link',
    'border-[#F8931D]' => 'border-accent-500',

    // ring-[#hex]
    'ring-[#205258]' => 'ring-primary-600',

    // hover states
    'hover:bg-[#205258]' => 'hover:bg-primary-600',
    'hover:bg-[#1b454a]' => 'hover:bg-primary-700',
    'hover:bg-[#E07E0A]' => 'hover:bg-accent-600',
    'hover:bg-[#e07e0a]' => 'hover:bg-accent-600',
    'hover:bg-[#F8931D]' => 'hover:bg-accent-500',
    'hover:bg-[#007185]' => 'hover:bg-link',
    'hover:text-[#205258]' => 'hover:text-primary-600',
    'hover:text-[#1b454a]' => 'hover:text-primary-700',
    'hover:text-[#C7511F]' => 'hover:text-link-hover',
    'hover:text-[#c7511f]' => 'hover:text-link-hover',
    'hover:text-[#007185]' => 'hover:text-link',
    'hover:border-[#205258]' => 'hover:border-primary-600',
    'hover:border-[#007185]' => 'hover:border-link',

    // focus states
    'focus:border-[#205258]' => 'focus:border-primary-600',
    'focus:border-[#007185]' => 'focus:border-link',
    'focus:ring-[#205258]' => 'focus:ring-primary-600',

    // opacity variants
    'bg-[#205258]/5' => 'bg-primary-600/5',
    'bg-[#205258]/10' => 'bg-primary-600/10',
    'bg-[#205258]/15' => 'bg-primary-600/15',
    'bg-[#205258]/20' => 'bg-primary-600/20',
    'ring-[#205258]/20' => 'ring-primary-600/20',
    'border-[#205258]/15' => 'border-primary-600/15',
    'border-[#205258]/20' => 'border-primary-600/20',
    'bg-[#F8931D]/15' => 'bg-accent-500/15',
    'bg-[#F8931D]/10' => 'bg-accent-500/10',
    'text-[#F8931D]/15' => 'text-accent-500/15',

    // peer-checked variants
    'peer-checked:border-[#205258]' => 'peer-checked:border-primary-600',
    'peer-checked:bg-[#205258]' => 'peer-checked:bg-primary-600',
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
    foreach ($replacements as $old => $new) {
        $count = substr_count($content, $old);
        if ($count > 0) {
            $content = str_replace($old, $new, $content);
            $fileReplacements += $count;
        }
    }

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

// Check what's left (hardcoded hex that wasn't mapped)
echo "\n=== Remaining hardcoded brand colors ===\n";
$remaining = 0;
$dir = new RecursiveDirectoryIterator($viewsDir);
$iter = new RecursiveIteratorIterator($dir);
foreach ($iter as $f) {
    if ($f->getExtension() !== 'php') continue;
    $content = file_get_contents($f->getPathname());
    // Check for remaining #205258 or #007185 etc that weren't in Tailwind class format
    if (preg_match_all('/#205258|#1b454a|#F8931D|#f8931d|#E07E0A|#007185|#C7511F|#c7511f/', $content, $matches)) {
        $remaining += count($matches[0]);
    }
}
echo "Remaining non-class hex references: $remaining (inline styles, meta tags, etc.)\n";
