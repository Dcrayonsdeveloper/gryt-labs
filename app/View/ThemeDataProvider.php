<?php

namespace App\View;

use App\Models\Setting;
use App\Models\NavigationMenu;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Pre-fetches ALL tenant settings + navigation into a single $theme object.
 *
 * Like Shopify's `shop` / `theme` Liquid objects — views never query the DB directly.
 * All data flows: Controller/Provider → View. Views are pure templates.
 *
 * Usage in Blade: {{ $theme->store_name }}, {{ $theme->contact_phone }}, etc.
 */
class ThemeDataProvider
{
    private static ?self $instance = null;
    private array $settings = [];
    private ?object $navigation = null;

    /**
     * Get the singleton instance (one load per request).
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->load();
        }

        return self::$instance;
    }

    /**
     * Reset for a new tenant context (used in testing or tenant switching).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Load all settings from the tenant database.
     */
    private function load(): void
    {
        // Setting::get() already caches all settings for 5 minutes.
        // We load them all at once instead of N individual calls.
        $cacheKey = 'settings.all.' . DB::connection()->getDatabaseName();
        $this->settings = Cache::store(config('cache.default'))
            ->remember($cacheKey, 300, function () {
                return Setting::all(['key', 'value', 'type'])
                    ->pluck('value', 'key')
                    ->toArray();
            });
    }

    /**
     * Get a setting value with optional default.
     */
    public function get(string $key, mixed $default = ''): mixed
    {
        $value = $this->settings[$key] ?? null;

        // Treat empty strings as "not set" (fail-closed — no Jikra defaults leak)
        return ($value !== null && $value !== '') ? $value : $default;
    }

    /**
     * Magic property access: $theme->store_name
     */
    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    /**
     * Check if a setting exists and is non-empty.
     */
    public function has(string $key): bool
    {
        $value = $this->settings[$key] ?? null;

        return $value !== null && $value !== '';
    }

    /**
     * Get all settings as array.
     */
    public function all(): array
    {
        return $this->settings;
    }

    /**
     * Pre-loaded navigation menus (header + footer).
     */
    public function navigation(string $location = 'header'): \Illuminate\Support\Collection
    {
        if ($this->navigation === null) {
            $this->navigation = (object) [];

            $cacheKey = 'theme_nav.' . DB::connection()->getDatabaseName();
            $menus = Cache::store(config('cache.default'))
                ->remember($cacheKey, 300, function () {
                    return NavigationMenu::where('is_active', true)
                        ->whereNull('parent_id')
                        ->with(['children' => fn($q) => $q->where('is_active', true)->orderBy('position')])
                        ->orderBy('position')
                        ->get()
                        ->groupBy('location');
                });

            $this->navigation->header = $menus->get('header', collect());
            $this->navigation->footer = $menus->get('footer', collect());
            $this->navigation->mobile = $menus->get('mobile', collect());
        }

        return $this->navigation->{$location} ?? collect();
    }
}
