<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ColorPaletteGenerator;
use App\View\ThemeDataProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ThemeController extends Controller
{
    /**
     * Theme Customizer page.
     */
    public function index()
    {
        $settings = $this->getThemeSettings();
        $presets = ColorPaletteGenerator::presets();

        return view('admin.theme.index', compact('settings', 'presets'));
    }

    /**
     * Visual Theme Editor — Shopify "Customize" full-screen editor.
     */
    public function editor()
    {
        $settings = $this->getThemeSettings();
        $presets = ColorPaletteGenerator::presets();
        $sections = \App\Models\PageSection::where('page', 'home')->orderBy('position')->get();
        $availableTypes = \App\Models\PageSection::availableTypes();

        return view('admin.theme.editor', compact('settings', 'presets', 'sections', 'availableTypes'));
    }

    /**
     * Save theme settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'store_name' => 'nullable|string|max:255',
            'store_tagline' => 'nullable|string|max:500',
            'store_logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'store_favicon' => 'nullable|file|mimes:png,ico,svg|max:512',
            'primary_color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
            'secondary_color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
            'link_color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
            'header_announcement' => 'nullable|string|max:500',
            'header_announcement_active' => 'nullable|boolean',
            'font_family' => 'nullable|string|max:100',
            'heading_font' => 'nullable|string|max:100',
            'button_radius' => 'nullable|in:rounded,pill,square',
            'header_style' => 'nullable|in:centered,left,mega',
            'footer_text' => 'nullable|string|max:1000',
            'custom_css' => 'nullable|string|max:10000',
        ]);

        // Handle logo upload — tenant-scoped path, delete old file first
        if ($request->hasFile('store_logo')) {
            $this->deleteOldAsset('store_logo');
            $tenantDir = 'branding/' . $this->getTenantPrefix();
            $path = $request->file('store_logo')->store($tenantDir, 'public');
            Setting::set('store_logo', 'storage/' . $path);
        }

        // Handle favicon upload — tenant-scoped path, delete old file first
        if ($request->hasFile('store_favicon')) {
            $this->deleteOldAsset('store_favicon');
            $tenantDir = 'branding/' . $this->getTenantPrefix();
            $path = $request->file('store_favicon')->store($tenantDir, 'public');
            Setting::set('store_favicon', 'storage/' . $path);
        }

        // Save text settings
        $textSettings = [
            'store_name', 'store_tagline', 'header_announcement',
            'font_family', 'heading_font', 'button_radius', 'header_style', 'footer_text',
        ];
        foreach ($textSettings as $key) {
            if (array_key_exists($key, $validated)) {
                Setting::set($key, $validated[$key] ?? '');
            }
        }

        // Sanitize and save custom CSS (strip tags, @import, url(), expressions)
        if (array_key_exists('custom_css', $validated)) {
            Setting::set('custom_css', $this->sanitizeCss($validated['custom_css'] ?? ''));
        }

        Setting::set('header_announcement_active', $request->boolean('header_announcement_active') ? '1' : '0');

        // Generate and save color palette if primary color changed
        if (!empty($validated['primary_color'])) {
            $colors = ColorPaletteGenerator::generateThemeSettings(
                $validated['primary_color'],
                $validated['secondary_color'] ?? (Setting::get('secondary_color', '') ?: '#f59e0b'),
                $validated['link_color'] ?? null,
            );

            foreach ($colors as $key => $value) {
                Setting::set($key, $value);
            }
        }

        // Reset the ThemeDataProvider singleton so cached settings refresh immediately
        ThemeDataProvider::reset();

        return back()->with('success', 'Theme updated successfully.');
    }

    /**
     * Apply a preset theme.
     */
    public function applyPreset(Request $request)
    {
        $request->validate(['preset' => 'required|string|alpha_dash']);

        $presets = ColorPaletteGenerator::presets();
        $presetKey = $request->input('preset');

        if (!isset($presets[$presetKey])) {
            return back()->with('error', 'Invalid preset.');
        }

        $preset = $presets[$presetKey];
        $colors = ColorPaletteGenerator::generateThemeSettings(
            $preset['primary'],
            $preset['secondary'],
            $preset['link'],
        );

        foreach ($colors as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('success', "Applied '{$preset['name']}' theme preset.");
    }

    /**
     * Preview colors via AJAX (returns CSS variables).
     */
    public function preview(Request $request)
    {
        $request->validate([
            'primary_color' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'secondary_color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
            'link_color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        $primary = $request->input('primary_color', '#334155');
        $secondary = $request->input('secondary_color', '#f59e0b');
        $link = $request->input('link_color');

        $colors = ColorPaletteGenerator::generateThemeSettings($primary, $secondary, $link);
        $palette = ColorPaletteGenerator::generate($primary);

        return response()->json([
            'settings' => $colors,
            'palette' => $palette,
            'css' => $this->generateCssVars($colors),
        ]);
    }

    /**
     * Export theme settings as JSON.
     */
    public function export()
    {
        $settings = $this->getThemeSettings();

        // Remove image paths from export (not portable)
        unset($settings['store_logo'], $settings['store_favicon']);

        $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return response($json)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="theme-settings.json"');
    }

    /**
     * Import theme settings from JSON.
     */
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:json|max:100']);

        $json = json_decode(file_get_contents($request->file('file')->path()), true);
        if (!$json || !is_array($json)) {
            return back()->with('error', 'Invalid JSON file.');
        }

        // Strict whitelist of allowed import keys
        $allowed = [
            'primary_color', 'primary_color_dark', 'secondary_color', 'secondary_color_dark',
            'link_color', 'link_hover_color',
            'color_50', 'color_100', 'color_200', 'color_300', 'color_400', 'color_500',
            'color_800', 'color_900', 'color_950',
            'store_name', 'store_tagline', 'font_family', 'heading_font',
            'button_radius', 'header_style', 'footer_text',
            'header_announcement', 'header_announcement_active',
        ];

        $imported = 0;
        foreach ($json as $key => $value) {
            if (in_array($key, $allowed, true) && is_string($value) && mb_strlen($value) <= 1000) {
                Setting::set($key, $value);
                $imported++;
            }
        }

        if ($imported === 0) {
            return back()->with('error', 'No valid settings found in file.');
        }

        return back()->with('success', "Imported {$imported} theme settings.");
    }

    /**
     * Reset theme to defaults.
     */
    public function reset()
    {
        $defaults = ColorPaletteGenerator::generateThemeSettings('#334155', '#f59e0b', '#2563eb');
        foreach ($defaults as $key => $value) {
            Setting::set($key, $value);
        }

        // Reset non-color settings
        Setting::set('font_family', 'Inter');
        Setting::set('heading_font', 'Inter');
        Setting::set('button_radius', 'rounded');
        Setting::set('header_style', 'centered');
        Setting::set('custom_css', '');

        return back()->with('success', 'Theme reset to defaults.');
    }

    /**
     * Delete old branding asset from storage before re-upload.
     */
    private function deleteOldAsset(string $settingKey): void
    {
        $current = Setting::get($settingKey);
        if ($current && str_starts_with($current, 'storage/branding/')) {
            $storagePath = str_replace('storage/', '', $current);
            Storage::disk('public')->delete($storagePath);
        }
    }

    /**
     * Sanitize custom CSS to prevent XSS.
     * Strips: <script>, </style>, @import, javascript:, expression(), url(data:)
     */
    private function sanitizeCss(string $css): string
    {
        // Remove any HTML tags
        $css = strip_tags($css);

        // Remove dangerous CSS patterns
        $css = preg_replace('/@import\b/i', '/* blocked-import */', $css);
        $css = preg_replace('/expression\s*\(/i', '/* blocked-expression */(', $css);
        $css = preg_replace('/javascript\s*:/i', '/* blocked */', $css);
        $css = preg_replace('/url\s*\(\s*["\']?\s*data\s*:/i', 'url(/* blocked-data */', $css);
        $css = preg_replace('/-moz-binding/i', '/* blocked */', $css);
        $css = preg_replace('/behavior\s*:/i', '/* blocked */', $css);

        return $css;
    }

    private function getThemeSettings(): array
    {
        return [
            'store_name' => Setting::get('store_name', config('app.name')),
            'store_tagline' => Setting::get('store_tagline', ''),
            'store_logo' => Setting::get('store_logo', ''),
            'store_favicon' => Setting::get('store_favicon', ''),
            'primary_color' => Setting::get('primary_color', '') ?: '#334155',
            'primary_color_dark' => Setting::get('primary_color_dark', '') ?: '#1e293b',
            'secondary_color' => Setting::get('secondary_color', '') ?: '#f59e0b',
            'secondary_color_dark' => Setting::get('secondary_color_dark', '') ?: '#d97706',
            'link_color' => Setting::get('link_color', '') ?: '#2563eb',
            'link_hover_color' => Setting::get('link_hover_color', '') ?: '#1d4ed8',
            'color_50' => Setting::get('color_50', '') ?: '#f8fafc',
            'color_100' => Setting::get('color_100', '') ?: '#f1f5f9',
            'color_200' => Setting::get('color_200', '') ?: '#e2e8f0',
            'color_300' => Setting::get('color_300', '') ?: '#cbd5e1',
            'color_400' => Setting::get('color_400', '') ?: '#94a3b8',
            'color_500' => Setting::get('color_500', '') ?: '#64748b',
            'color_800' => Setting::get('color_800', '') ?: '#1e293b',
            'color_900' => Setting::get('color_900', '') ?: '#0f172a',
            'color_950' => Setting::get('color_950', '') ?: '#020617',
            'header_announcement' => Setting::get('header_announcement', ''),
            'header_announcement_active' => Setting::get('header_announcement_active', '') ?: '1',
            'font_family' => Setting::get('font_family', '') ?: 'Poppins',
            'heading_font' => Setting::get('heading_font', '') ?: 'Fredoka',
            'button_radius' => Setting::get('button_radius', '') ?: 'rounded',
            'header_style' => Setting::get('header_style', '') ?: 'centered',
            'footer_text' => Setting::get('footer_text', ''),
            'custom_css' => Setting::get('custom_css', ''),
        ];
    }

    /**
     * Get tenant-scoped directory prefix to isolate uploads between tenants.
     */
    private function getTenantPrefix(): string
    {
        if (function_exists('tenant') && tenant()) {
            return tenant()->id;
        }
        return 'default';
    }

    private function generateCssVars(array $settings): string
    {
        $vars = [];
        $map = [
            'color_50' => '--brand-50', 'color_100' => '--brand-100', 'color_200' => '--brand-200',
            'color_300' => '--brand-300', 'color_400' => '--brand-400', 'color_500' => '--brand-500',
            'primary_color' => '--brand-600', 'primary_color_dark' => '--brand-700',
            'color_800' => '--brand-800', 'color_900' => '--brand-900', 'color_950' => '--brand-950',
            'secondary_color' => '--brand-accent', 'secondary_color_dark' => '--brand-accent-dark',
            'link_color' => '--brand-link', 'link_hover_color' => '--brand-link-hover',
        ];

        foreach ($map as $key => $var) {
            if (isset($settings[$key])) {
                $vars[] = "$var: {$settings[$key]};";
            }
        }

        return ':root { ' . implode(' ', $vars) . ' }';
    }
}
