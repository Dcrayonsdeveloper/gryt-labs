<?php

use App\Http\Controllers\Influencer\Auth\LoginController;
use App\Http\Controllers\Influencer\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Influencer Portal
|--------------------------------------------------------------------------
| Username/password login on the dedicated `influencer` guard. Each
| influencer only ever sees data for their own coupon. Loaded per-tenant
| from routes/tenant.php (and routes/web.php), like the affiliate portal.
*/

Route::prefix('influencer')->name('influencer.')->group(function () {

    // ─── Guest ────────────────────────────────────────────────────────────
    Route::middleware('throttle:10,1')->group(function () {
        Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [LoginController::class, 'login']);
    });

    // ─── Authenticated influencer ─────────────────────────────────────────
    Route::middleware('auth:influencer')->group(function () {
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
        Route::get('/', fn () => redirect()->route('influencer.dashboard'));
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/export', [DashboardController::class, 'export'])->name('export');
    });
});
