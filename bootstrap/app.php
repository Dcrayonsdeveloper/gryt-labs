<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/central.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
        // Note: Tenant routes (web.php, admin.php, etc.) are loaded via
        // TenancyServiceProvider → routes/tenant.php with domain middleware.
        // Central routes (super admin) are loaded here for admin.jikra.in.
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\TrackAffiliateReferral::class);
        $middleware->append(\App\Http\Middleware\TrackVisitor::class);
        $middleware->append(\App\Http\Middleware\CaptureFbTestParam::class);

        // Allow sr_test_mode cookie to be set via JS without encryption
        $middleware->encryptCookies(except: ['sr_test_mode']);

        $middleware->validateCsrfTokens(except: [
            'webhook/*',
            'api/abandoned-capture',
            // Shiprocket server-to-server HMAC-signed token endpoint; CSRF bypass safe
            // because the signature is verified in ShiprocketService::getCheckoutToken()
            // using the tenant's shiprocket_checkout_api_secret.
            'api/shiprocket-checkout-token',
            'checkout/cashfree/webhook',
        ]);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('delivery/*') || $request->is('delivery')) {
                return route('delivery.login');
            }
            if ($request->is('affiliate/*') || $request->is('affiliate')) {
                return route('affiliate.login');
            }
            if ($request->is('influencer/*') || $request->is('influencer')) {
                return route('influencer.login');
            }
            if ($request->is('admin/*') || $request->is('admin')) {
                return route('admin.login');
            }
            if ($request->is('pos/*') || $request->is('pos')) {
                return route('pos.login');
            }
            return route('login');
        });

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'admin.section' => \App\Http\Middleware\CheckAdminSection::class,
            'delivery' => \App\Http\Middleware\EnsureUserIsDeliveryPartner::class,
            'affiliate' => \App\Http\Middleware\EnsureUserIsAffiliate::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'pos.auth' => \App\Http\Middleware\PosAuthenticate::class,
            'pos.shift' => \App\Http\Middleware\PosShiftRequired::class,
            'cache.response' => \App\Http\Middleware\CacheResponse::class,
            'admin.audit' => \App\Http\Middleware\AdminAuditLog::class,
            'throttle.login' => \App\Http\Middleware\ThrottleLogins::class,
            '2fa' => \App\Http\Middleware\Require2FA::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Suppress tenant-not-found errors entirely (bot traffic on wildcard subdomains)
        $exceptions->dontReport([
            \Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException::class,
        ]);

        // Handle 419 (CSRF token expired) like Shopify — silent reload, not ugly error page
        $exceptions->renderable(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            // AJAX requests → return fresh token so client can retry
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Session expired.',
                    'token' => csrf_token(),
                ], 419);
            }

            // HTML form submissions → auto-reload page (Shopify-style)
            // This reloads the form with a fresh CSRF token, preserving the URL
            $url = $request->fullUrl();
            return response(
                '<html><head><meta http-equiv="refresh" content="0;url=' . e($url) . '"></head>' .
                '<body><p style="font-family:system-ui;text-align:center;margin-top:40vh;color:#666;">' .
                'Session refreshed. Redirecting...</p></body></html>',
                419
            );
        });

        $exceptions->reportable(function (\Throwable $e): void {
            // Skip 404s and validation errors from DB logging (too noisy)
            if ($e instanceof \Illuminate\Http\Exceptions\HttpResponseException) return;
            if ($e instanceof \Illuminate\Validation\ValidationException) return;
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) return;
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) return;
            if ($e instanceof \Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException) return;

            try {
                \App\Services\ErrorLogService::log(
                    message: $e->getMessage(),
                    severity: \App\Services\ErrorLogService::severity($e),
                    category: \App\Services\ErrorLogService::categorize($e),
                    file: $e->getFile(),
                    line: $e->getLine(),
                    trace: mb_substr($e->getTraceAsString(), 0, 5000),
                    url: request()->fullUrl(),
                    userId: auth()->id(),
                );
            } catch (\Throwable $logError) {
                // Never let error logging break the app
            }
        });
    })->create();
