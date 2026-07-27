<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ResolveTenantFrontend;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| All storefront routes run inside tenant context.
| The middleware resolves the tenant by domain, switches the DB connection,
| and scopes cache/storage/queue to that tenant.
|
| Every existing route file (web, admin, seller, delivery, pos, affiliate, api)
| is included here — they all operate within the tenant's database.
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    ResolveTenantFrontend::class,
])->group(function () {
    // Load all existing route files inside tenant context
    require base_path('routes/web.php');
    require base_path('routes/shiprocket_checkout.php');
    require base_path('routes/admin.php');
    require base_path('routes/seller.php');
    require base_path('routes/delivery.php');
    require base_path('routes/pos.php');
    require base_path('routes/affiliate.php');
    require base_path('routes/influencer.php');
});

// API routes in tenant context
Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    ResolveTenantFrontend::class,
])->prefix('api')->group(function () {
    require base_path('routes/api.php');
});
