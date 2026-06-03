<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\File;

/**
 * Manages theme resolution — like Shopify's theme system.
 *
 * Themes live in /themes/{name}/views/
 * View resolution: active theme → default theme → standard views
 */
class ThemeManager
{
    protected ?string $activeTheme = null;

    /**
     * Get the active theme for the current tenant.
     */
    public function active(): string
    {
        if ($this->activeTheme === null) {
            $this->activeTheme = Setting::get('active_theme', '') ?: 'default';
        }
        return $this->activeTheme;
    }

    /**
     * Set the active theme.
     */
    public function setActive(string $theme): void
    {
        $this->activeTheme = $theme;
        Setting::set('active_theme', $theme);
    }

    /**
     * Get all available themes.
     */
    public function available(): array
    {
        $themesPath = base_path('themes');
        if (!File::isDirectory($themesPath)) {
            return ['default' => $this->getThemeMeta('default')];
        }

        $themes = [];
        foreach (File::directories($themesPath) as $dir) {
            $name = basename($dir);
            $themes[$name] = $this->getThemeMeta($name);
        }

        return $themes;
    }

    /**
     * Get theme metadata from theme.json.
     */
    public function getThemeMeta(string $theme): array
    {
        $path = base_path("themes/{$theme}/theme.json");
        if (File::exists($path)) {
            return json_decode(File::get($path), true) ?? [];
        }

        return [
            'name' => ucfirst($theme),
            'version' => '1.0.0',
            'author' => config('app.name', 'Store'),
            'description' => $theme === 'default' ? 'Default theme' : '',
        ];
    }

    /**
     * Get the settings schema for a theme.
     */
    public function getSettingsSchema(string $theme = null): array
    {
        $theme = $theme ?? $this->active();
        $meta = $this->getThemeMeta($theme);
        return $meta['settings_schema'] ?? [];
    }

    /**
     * Get view paths in priority order for the active theme.
     * 1. Active theme views
     * 2. Default theme views (fallback)
     * 3. Standard resources/views (ultimate fallback)
     */
    public function getViewPaths(): array
    {
        $theme = $this->active();
        $paths = [];

        // Active theme views (highest priority)
        $themePath = base_path("themes/{$theme}/views");
        if ($theme !== 'default' && File::isDirectory($themePath)) {
            $paths[] = $themePath;
        }

        // Default theme views
        $defaultPath = base_path('themes/default/views');
        if (File::isDirectory($defaultPath)) {
            $paths[] = $defaultPath;
        }

        // Standard views (always available as ultimate fallback)
        $paths[] = resource_path('views');

        return $paths;
    }

    /**
     * Get theme-specific CSS file path (if exists).
     */
    public function getThemeCss(): ?string
    {
        $theme = $this->active();
        $cssPath = "themes/{$theme}/css/theme.css";

        if (File::exists(base_path($cssPath))) {
            return $cssPath;
        }

        return null;
    }

    /**
     * Get theme screenshot URL.
     */
    public function getScreenshot(string $theme): ?string
    {
        $path = "themes/{$theme}/screenshot.png";
        if (File::exists(base_path($path))) {
            return asset($path);
        }
        return null;
    }
}
