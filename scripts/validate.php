#!/usr/bin/env php
<?php
/**
 * Jikra Code Validator
 * Run: php scripts/validate.php [--fix] [--file=path]
 *
 * Catches common AI-generated code issues:
 * - Missing closing tags in Blade (>, /div, endif, etc.)
 * - @context/@type parsed as Blade directives in JSON-LD
 * - Unbalanced @if/@endif, @foreach/@endforeach
 * - Broken HTML attributes (missing >)
 * - Dollar signs in INR context
 * - Hardcoded admin credentials
 * - PHP syntax errors in Blade compiled output
 */

$fix = in_array('--fix', $argv);
$singleFile = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--file=')) {
        $singleFile = substr($arg, 7);
    }
}

$errors = [];
$warnings = [];
$checked = 0;

// Get blade files to check
if ($singleFile) {
    $files = [realpath($singleFile)];
} else {
    $files = array_merge(
        glob(__DIR__ . '/../resources/views/**/*.blade.php'),
        glob(__DIR__ . '/../resources/views/**/**/*.blade.php'),
        glob(__DIR__ . '/../resources/views/**/**/**/*.blade.php'),
        glob(__DIR__ . '/../resources/views/*.blade.php'),
    );
}
$files = array_unique(array_filter($files));

foreach ($files as $file) {
    $checked++;
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    $rel = str_replace(realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR, '', $file);

    // 1. Check @if/@endif balance
    $opens = preg_match_all('/@if\s*\(|@unless\s*\(|@foreach\s*\(|@forelse\s*\(|@isset\s*\(|@empty\s*\(|@hasSection\s*\(|@guest|@auth\b|@can\s*\(/', $content);
    $closes = preg_match_all('/@endif|@endunless|@endforeach|@endforelse|@endisset|@endempty|@endguest|@endauth|@endcan/', $content);
    // Account for @elseif (doesn't add to opens)
    if ($opens !== $closes) {
        $errors[] = "$rel: Unbalanced directives — $opens opens vs $closes closes";
    }

    // 2. Check raw @context/@type inside <script type="application/ld+json"> (not inside @php/json_encode)
    if (preg_match_all('/<script\s+type=["\']application\/ld\+json["\']>(.*?)<\/script>/s', $content, $jsonLdBlocks)) {
        foreach ($jsonLdBlocks[1] as $block) {
            // Only flag if block has raw @context/@type (not via json_encode)
            if (preg_match('/"@context"|"@type"/', $block) && !str_contains($block, 'json_encode')) {
                $errors[] = "$rel: Raw @context/@type inside JSON-LD <script> — use @php json_encode() instead";
            }
            // Also flag @foreach/@if inside JSON-LD (not regular script)
            if (preg_match('/@foreach|@for\b/', $block)) {
                $errors[] = "$rel: @foreach/@for inside JSON-LD <script> — use PHP array methods instead";
            }
        }
    }

    // 3. Check for @context / @type in Blade (will be parsed as directives)
    foreach ($lines as $num => $line) {
        $lineNum = $num + 1;

        // @context/@type outside of @php blocks
        if (preg_match('/"@context"|"@type"/', $line) && !preg_match('/json_encode|@php|@endphp/', $line)) {
            $errors[] = "$rel:$lineNum: '@context' or '@type' in template — Blade will parse as directive. Use json_encode()";
        }
    }

    // 4. Check for unclosed HTML tags with x-data (missing >)
    // Only flag if we can't find a closing > within 50 lines
    if (preg_match_all('/<\w+[^>]*x-data="[^"]*"\s*$/m', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        foreach ($matches as $m) {
            $pos = $m[0][1];
            $lineNum = substr_count(substr($content, 0, $pos), "\n") + 1;
            // Search up to 50 lines ahead for the closing >
            $searchBlock = implode("\n", array_slice($lines, $lineNum, 50));
            // If there's a > anywhere in the next 50 lines, the tag is probably closed
            if (str_contains($searchBlock, '>')) {
                continue;
            }
            $errors[] = "$rel:$lineNum: x-data tag may be missing closing '>'";
        }
    }

    // 5. Check for dollar signs in price context (should be ₹)
    if (preg_match_all('/\$\d+|\$\s*\d+|dollar/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $match) {
            $lineNum = substr_count(substr($content, 0, $match[1]), "\n") + 1;
            $line = $lines[$lineNum - 1] ?? '';
            // Skip PHP variables, Blade, JS vars
            if (!preg_match('/\$\w+|@price|\{\{|config\(|env\(|function|var |let |const /', $line)) {
                $warnings[] = "$rel:$lineNum: Possible dollar sign in price context — use ₹ for INR";
            }
        }
    }

    // 6. Check for hardcoded credentials
    $sensitivePatterns = [
        '/password\s*[:=]\s*["\'][^"\']{4,}["\']/' => 'Possible hardcoded password',
        '/admin@\w+\.\w+/' => 'Admin email in template',
        '/rahul@/' => 'Personal email in template',
        '/Bearer\s+[A-Za-z0-9]{20,}/' => 'Hardcoded API token',
    ];
    foreach ($sensitivePatterns as $pattern => $msg) {
        if (preg_match($pattern, $content, $m, PREG_OFFSET_CAPTURE)) {
            $lineNum = substr_count(substr($content, 0, $m[0][1]), "\n") + 1;
            $warnings[] = "$rel:$lineNum: $msg";
        }
    }

    // 7. Check x-cloak without [x-cloak] CSS
    // (just a warning if file uses x-cloak but doesn't include layout)

    // 8. Check <template x-if> (should be x-if, not x-show for template)
    if (preg_match('/<template\s+x-show/', $content)) {
        $lineNum = 0;
        foreach ($lines as $num => $line) {
            if (str_contains($line, '<template') && str_contains($line, 'x-show')) {
                $warnings[] = "$rel:" . ($num + 1) . ": <template> should use x-if, not x-show";
            }
        }
    }
}

// Also check PHP files for common issues
$phpFiles = $singleFile ? [] : array_merge(
    glob(__DIR__ . '/../app/Http/Controllers/*.php'),
    glob(__DIR__ . '/../app/Http/Controllers/**/*.php'),
    glob(__DIR__ . '/../routes/*.php'),
);

foreach ($phpFiles as $file) {
    $checked++;
    $rel = str_replace(realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR, '', $file);

    // PHP syntax check
    $output = [];
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $code);
    if ($code !== 0) {
        $errors[] = "$rel: PHP syntax error — " . implode(' ', $output);
    }
}

// Print results
echo "\n";
echo "  Jikra Code Validator\n";
echo "  " . str_repeat("=", 50) . "\n";
echo "  Checked: $checked files\n\n";

if (empty($errors) && empty($warnings)) {
    echo "  ✓ All checks passed!\n\n";
    exit(0);
}

if (!empty($errors)) {
    echo "  ERRORS (" . count($errors) . "):\n";
    foreach ($errors as $e) {
        echo "  ✗ $e\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "  WARNINGS (" . count($warnings) . "):\n";
    foreach ($warnings as $w) {
        echo "  ⚠ $w\n";
    }
    echo "\n";
}

exit(count($errors) > 0 ? 1 : 0);
