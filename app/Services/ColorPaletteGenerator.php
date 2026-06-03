<?php

namespace App\Services;

/**
 * Generates a full 50-950 color palette from a single hex color.
 * Similar to Tailwind's default palette generation.
 */
class ColorPaletteGenerator
{
    /**
     * Generate full palette from a single primary color.
     * Input: #e91e8c → Output: ['50' => '#fff5f9', ..., '950' => '#500724']
     */
    public static function generate(string $hex): array
    {
        $hsl = self::hexToHsl($hex);

        return [
            '50'  => self::hslToHex($hsl[0], max(10, $hsl[1] - 30), min(97, $hsl[2] + 45)),
            '100' => self::hslToHex($hsl[0], max(15, $hsl[1] - 20), min(93, $hsl[2] + 35)),
            '200' => self::hslToHex($hsl[0], max(20, $hsl[1] - 10), min(86, $hsl[2] + 25)),
            '300' => self::hslToHex($hsl[0], max(25, $hsl[1] - 5), min(76, $hsl[2] + 15)),
            '400' => self::hslToHex($hsl[0], $hsl[1], min(65, $hsl[2] + 8)),
            '500' => self::hslToHex($hsl[0], $hsl[1], $hsl[2]),
            '600' => self::hslToHex($hsl[0], min(100, $hsl[1] + 5), max(20, $hsl[2] - 8)),
            '700' => self::hslToHex($hsl[0], min(100, $hsl[1] + 8), max(15, $hsl[2] - 15)),
            '800' => self::hslToHex($hsl[0], min(100, $hsl[1] + 10), max(12, $hsl[2] - 22)),
            '900' => self::hslToHex($hsl[0], min(100, $hsl[1] + 12), max(8, $hsl[2] - 30)),
            '950' => self::hslToHex($hsl[0], min(100, $hsl[1] + 15), max(5, $hsl[2] - 40)),
        ];
    }

    /**
     * Get predefined theme presets.
     */
    public static function presets(): array
    {
        return [
            'teal' => [
                'name' => 'Ocean Teal',
                'primary' => '#205258',
                'secondary' => '#F8931D',
                'link' => '#007185',
            ],
            'rose' => [
                'name' => 'Rose Gold',
                'primary' => '#e91e8c',
                'secondary' => '#d4145a',
                'link' => '#e91e8c',
            ],
            'purple' => [
                'name' => 'Royal Purple',
                'primary' => '#7c3aed',
                'secondary' => '#f59e0b',
                'link' => '#6366f1',
            ],
            'blue' => [
                'name' => 'Ocean Blue',
                'primary' => '#0ea5e9',
                'secondary' => '#f97316',
                'link' => '#0284c7',
            ],
            'green' => [
                'name' => 'Forest Green',
                'primary' => '#059669',
                'secondary' => '#f59e0b',
                'link' => '#047857',
            ],
            'red' => [
                'name' => 'Ruby Red',
                'primary' => '#dc2626',
                'secondary' => '#f59e0b',
                'link' => '#b91c1c',
            ],
            'orange' => [
                'name' => 'Sunset Orange',
                'primary' => '#ea580c',
                'secondary' => '#0ea5e9',
                'link' => '#c2410c',
            ],
            'black' => [
                'name' => 'Midnight Black',
                'primary' => '#18181b',
                'secondary' => '#f59e0b',
                'link' => '#3f3f46',
            ],
        ];
    }

    /**
     * Generate complete theme settings from a preset or custom color.
     */
    public static function generateThemeSettings(string $primary, string $secondary = '#F8931D', string $link = null): array
    {
        $palette = self::generate($primary);
        $secPalette = self::generate($secondary);
        $link = $link ?? $primary;
        $linkPalette = self::generate($link);

        return [
            'primary_color' => $palette['600'],
            'primary_color_dark' => $palette['700'],
            'secondary_color' => $secPalette['500'],
            'secondary_color_dark' => $secPalette['600'],
            'link_color' => $linkPalette['500'],
            'link_hover_color' => $linkPalette['700'],
            'color_50' => $palette['50'],
            'color_100' => $palette['100'],
            'color_200' => $palette['200'],
            'color_300' => $palette['300'],
            'color_400' => $palette['400'],
            'color_500' => $palette['500'],
            'color_800' => $palette['800'],
            'color_900' => $palette['900'],
            'color_950' => $palette['950'],
        ];
    }

    private static function hexToHsl(string $hex): array
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

            switch ($max) {
                case $r: $h = (($g - $b) / $d + ($g < $b ? 6 : 0)) / 6; break;
                case $g: $h = (($b - $r) / $d + 2) / 6; break;
                case $b: $h = (($r - $g) / $d + 4) / 6; break;
            }
        }

        return [round($h * 360), round($s * 100), round($l * 100)];
    }

    private static function hslToHex(float $h, float $s, float $l): string
    {
        $h = $h / 360;
        $s = max(0, min(100, $s)) / 100;
        $l = max(0, min(100, $l)) / 100;

        if ($s === 0.0) {
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = self::hueToRgb($p, $q, $h + 1/3);
            $g = self::hueToRgb($p, $q, $h);
            $b = self::hueToRgb($p, $q, $h - 1/3);
        }

        return '#' . str_pad(dechex((int) round($r * 255)), 2, '0', STR_PAD_LEFT)
                    . str_pad(dechex((int) round($g * 255)), 2, '0', STR_PAD_LEFT)
                    . str_pad(dechex((int) round($b * 255)), 2, '0', STR_PAD_LEFT);
    }

    private static function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1/2) return $q;
        if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
        return $p;
    }
}
