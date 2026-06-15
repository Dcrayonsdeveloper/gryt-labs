<?php
// Generate a square, crisp favicon from the wide GRYT logo.
// Crops the green leaf mark (the recognizable square element) and centers it
// on a white square canvas. Output: public/images/favicon.png (256x256).

$src = imagecreatefrompng(__DIR__ . '/../public/images/logo.png');
$sw = imagesx($src); // 1005
$sh = imagesy($src); // 280

// Auto-detect the green leaf mark by scanning for lime-green pixels
// (high R, high G, low B) and computing their bounding box. This avoids
// hardcoded crop guesses that catch neighbouring letters.
$minX = $sw; $minY = $sh; $maxX = 0; $maxY = 0; $found = false;
for ($y = 0; $y < $sh; $y++) {
    for ($x = 0; $x < $sw; $x++) {
        $rgb = imagecolorat($src, $x, $y);
        $r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $b = $rgb & 0xFF;
        if ($g > 150 && $r > 110 && $r < 230 && $b < 110 && $g > $b + 60) {
            $found = true;
            if ($x < $minX) $minX = $x; if ($x > $maxX) $maxX = $x;
            if ($y < $minY) $minY = $y; if ($y > $maxY) $maxY = $y;
        }
    }
}
if (!$found) { fwrite(STDERR, "no green pixels found\n"); exit(1); }
$cropX = max(0, $minX - 4);
$cropY = max(0, $minY - 4);
$cropW = min($sw - $cropX, ($maxX - $minX) + 8);
$cropH = min($sh - $cropY, ($maxY - $minY) + 8);
fwrite(STDERR, "leaf bbox: x=$cropX y=$cropY w=$cropW h=$cropH\n");

$leaf = imagecreatetruecolor($cropW, $cropH);
imagealphablending($leaf, false);
imagesavealpha($leaf, true);
$transparent = imagecolorallocatealpha($leaf, 0, 0, 0, 127);
imagefill($leaf, 0, 0, $transparent);
imagecopy($leaf, $src, 0, 0, $cropX, $cropY, $cropW, $cropH);

// Strip any dark, non-green fragments (e.g. the nearby "T" stem) -> transparent
for ($y = 0; $y < $cropH; $y++) {
    for ($x = 0; $x < $cropW; $x++) {
        $rgb = imagecolorat($leaf, $x, $y);
        $r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $b = $rgb & 0xFF;
        $isGreen = ($g > 130 && $g > $b + 50 && $r < 230);
        if (!$isGreen && $r < 110 && $g < 110 && $b < 110) {
            imagesetpixel($leaf, $x, $y, $transparent);
        }
    }
}

// Square canvas (white background, like the logo's background)
$size = 256;
$pad  = 24; // breathing room
$canvas = imagecreatetruecolor($size, $size);
$white = imagecolorallocate($canvas, 255, 255, 255);
imagefill($canvas, 0, 0, $white);

// Fit the leaf inside the padded square, preserving aspect ratio
$maxBox = $size - 2 * $pad;
$scale = min($maxBox / $cropW, $maxBox / $cropH);
$dw = (int) round($cropW * $scale);
$dh = (int) round($cropH * $scale);
$dx = (int) round(($size - $dw) / 2);
$dy = (int) round(($size - $dh) / 2);
imagecopyresampled($canvas, $leaf, $dx, $dy, 0, 0, $dw, $dh, $cropW, $cropH);

$out = __DIR__ . '/../public/images/favicon.png';
imagepng($canvas, $out);
echo "wrote $out (" . $size . "x" . $size . ")\n";
