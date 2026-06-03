<?php
/**
 * Replace all hardcoded "Jikra" text and logo references with dynamic Setting::get() calls
 */

$viewsDir = 'd:/Projects/jikra-theme-system/resources/views';
$fixed = 0;

$dir = new RecursiveDirectoryIterator($viewsDir);
$iter = new RecursiveIteratorIterator($dir);

foreach ($iter as $f) {
    if ($f->getExtension() !== 'php') continue;
    $path = $f->getPathname();
    $content = file_get_contents($path);
    $original = $content;

    // Replace hardcoded logo paths
    $content = str_replace(
        ["asset('images/jikra-logo.png')", 'asset("images/jikra-logo.png")'],
        ["asset(\\App\\Models\\Setting::get('store_logo', 'images/jikra-logo.png'))", "asset(\\App\\Models\\Setting::get('store_logo', 'images/jikra-logo.png'))"],
        $content
    );

    // Replace url() logo in emails
    $content = str_replace(
        ["url('/images/jikra-logo.png')", 'url("/images/jikra-logo.png")'],
        ["url('/' . \\App\\Models\\Setting::get('store_logo', 'images/jikra-logo.png'))", "url('/' . \\App\\Models\\Setting::get('store_logo', 'images/jikra-logo.png'))"],
        $content
    );

    // Replace src="/images/jikra-logo.png" (direct HTML)
    $content = str_replace(
        'src="/images/jikra-logo.png"',
        'src="{{ asset(\\App\\Models\\Setting::get(\'store_logo\', \'images/jikra-logo.png\')) }}"',
        $content
    );

    // Replace alt="Jikra"
    $content = str_replace(
        'alt="Jikra"',
        'alt="{{ \\App\\Models\\Setting::get(\'store_name\', config(\'app.name\')) }}"',
        $content
    );

    // Replace meta content="Jikra"
    $content = str_replace(
        'content="Jikra"',
        'content="{{ \\App\\Models\\Setting::get(\'store_name\', config(\'app.name\')) }}"',
        $content
    );

    // Replace 'Jikra' in fallback strings like ?? 'Jikra'
    $content = str_replace(
        "?? 'Jikra'",
        "?? \\App\\Models\\Setting::get('store_name', config('app.name'))",
        $content
    );

    // Replace hardcoded business info in content pages
    $content = str_replace(
        'Enormous Technology',
        "{{ \\App\\Models\\Setting::get('legal_name', 'Enormous Technology') }}",
        $content
    );

    if ($content !== $original) {
        file_put_contents($path, $content);
        $rel = str_replace(str_replace('/', DIRECTORY_SEPARATOR, $viewsDir) . DIRECTORY_SEPARATOR, '', $path);
        echo "Fixed: " . str_replace(DIRECTORY_SEPARATOR, '/', $rel) . "\n";
        $fixed++;
    }
}

echo "\nTotal files fixed: $fixed\n";

// Final check
echo "\n=== Remaining hardcoded references ===\n";
$dir = new RecursiveDirectoryIterator($viewsDir);
$iter = new RecursiveIteratorIterator($dir);
$count = 0;
foreach ($iter as $f) {
    if ($f->getExtension() !== 'php') continue;
    $c = file_get_contents($f->getPathname());
    if (preg_match('/jikra-logo\.png|"Jikra"|>Jikra<|\'Jikra\'/', $c) && strpos($c, 'Setting::get') === false) {
        $rel = str_replace(str_replace('/', DIRECTORY_SEPARATOR, $viewsDir) . DIRECTORY_SEPARATOR, '', $f->getPathname());
        echo "  " . str_replace(DIRECTORY_SEPARATOR, '/', $rel) . "\n";
        $count++;
    }
}
echo "Files with remaining references: $count\n";
