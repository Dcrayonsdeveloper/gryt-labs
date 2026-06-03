<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\TenantController;
use App\Http\Controllers\SuperAdmin\AuthController;

/*
|--------------------------------------------------------------------------
| Central Routes (Super Admin Panel)
|--------------------------------------------------------------------------
|
| These routes run on the central domain (admin.jikra.in).
| They are NOT tenant-scoped — they access the central database
| for managing tenants, global analytics, and platform settings.
|
*/

// Super Admin Auth — restricted to central domains only (admin.jikra.in, localhost)
Route::middleware(\App\Http\Middleware\EnsureCentralDomain::class)
    ->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::middleware('guest:super_admin')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle.login')->name('login.submit');
    });

    Route::middleware('auth:super_admin')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Tenant Management
        Route::resource('tenants', TenantController::class);
        Route::post('/tenants/{tenant}/toggle', [TenantController::class, 'toggle'])->name('tenants.toggle');
        Route::post('/tenants/{tenant}/impersonate', [TenantController::class, 'impersonate'])->name('tenants.impersonate');
        Route::get('/tenants/{tenant}/stats', [TenantController::class, 'stats'])->name('tenants.stats');
        Route::post('/tenants/{tenant}/add-domain', [TenantController::class, 'addDomain'])->name('tenants.add-domain');
        Route::delete('/tenants/{tenant}/remove-domain', [TenantController::class, 'removeDomain'])->name('tenants.remove-domain');
        Route::get('/tenants/check-availability', [TenantController::class, 'checkAvailability'])->name('tenants.check-availability');
    });
});

// Redirect root of central domain to super admin login
Route::middleware(\App\Http\Middleware\EnsureCentralDomain::class)->get('/', function () {
    return redirect()->route('super-admin.login');
});
