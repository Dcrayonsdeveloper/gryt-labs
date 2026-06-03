<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves tenant-specific frontend views.
 *
 * View resolution order:
 * 1. frontends/{tenant}/views/  (tenant-specific overrides)
 * 2. frontends/default/views/   (shared storefront defaults)
 * 3. resources/views/           (ultimate fallback — originals)
 *
 * Components, admin, email, and account views remain shared
 * in resources/views/ and are always available via fallback.
 */
class ResolveTenantFrontend
{
    public function handle(Request $request, Closure $next): Response
    {
        $this->registerFrontendPaths();

        return $next($request);
    }

    /**
     * Determine which frontend key to use for this tenant.
     * Used by the layout to load the correct CSS/JS bundle.
     */
    protected function resolveFrontendKey(): string
    {
        try {
            $tenant = tenant();
            if ($tenant) {
                $key = $tenant->getTenantKey();
                if (is_dir(base_path("frontends/{$key}/views"))) {
                    return $key;
                }
            }
        } catch (\Throwable $e) {}

        return 'default';
    }

    protected function registerFrontendPaths(): void
    {
        $frontendKey = $this->resolveFrontendKey();

        // Share the frontend key so the layout knows which CSS/JS to load
        config(['app.frontend' => $frontendKey]);

        $finder = View::getFinder();
        $currentPaths = $finder->getPaths();

        // Build new path order: tenant frontend → default frontend → existing paths
        $newPaths = [];

        // Tenant-specific frontend (highest priority)
        $tenantPath = base_path("frontends/{$frontendKey}/views");
        if (is_dir($tenantPath)) {
            $newPaths[] = $tenantPath;
        }

        // Default frontend (fallback for missing views)
        if ($frontendKey !== 'default') {
            $defaultPath = base_path('frontends/default/views');
            if (is_dir($defaultPath)) {
                $newPaths[] = $defaultPath;
            }
        }

        // Add existing paths (resources/views) after frontends
        foreach ($currentPaths as $path) {
            if (!in_array($path, $newPaths)) {
                $newPaths[] = $path;
            }
        }

        $finder->setPaths($newPaths);
        $finder->flush();
    }
}
