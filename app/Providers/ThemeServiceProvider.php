<?php

namespace App\Providers;

use App\Services\ThemeManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThemeManager::class);
    }

    public function boot(): void
    {
        // Register theme view paths AFTER tenancy is initialized
        // This ensures the correct tenant's theme is used
        $this->app->booted(function () {
            try {
                $themeManager = app(ThemeManager::class);
                $viewFinder = View::getFinder();

                // Prepend theme paths so they take priority over default views
                foreach (array_reverse($themeManager->getViewPaths()) as $path) {
                    if ($path !== resource_path('views')) {
                        $viewFinder->prependLocation($path);
                    }
                }
            } catch (\Exception $e) {
                // Theme loading fails gracefully — standard views used
            }
        });

        // Share theme manager with all views
        View::composer('*', function ($view) {
            if (!$view->offsetExists('themeManager')) {
                try {
                    $view->with('themeManager', app(ThemeManager::class));
                } catch (\Exception $e) {
                    // Ignore
                }
            }
        });
    }
}
