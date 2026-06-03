<?php

use App\Http\Controllers\Affiliate\Auth\LoginController;
use App\Http\Controllers\Affiliate\Auth\RegisterController;
use App\Http\Controllers\Affiliate\CommissionController;
use App\Http\Controllers\Affiliate\DashboardController;
use App\Http\Controllers\Affiliate\RedemptionController;
use App\Http\Controllers\Affiliate\SettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('affiliate')->name('affiliate.')->group(function () {
    // Guest routes
    Route::middleware(['guest', 'throttle:10,1'])->group(function () {
        Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [LoginController::class, 'login']);
        Route::get('/register', [RegisterController::class, 'index'])->name('register');
        Route::post('/register', [RegisterController::class, 'store']);
    });

    // Authenticated affiliate routes
    Route::middleware(['auth', 'affiliate'])->group(function () {
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::prefix('commissions')->name('commissions.')->group(function () {
            Route::get('/', [CommissionController::class, 'index'])->name('index');
        });

        Route::prefix('redemptions')->name('redemptions.')->group(function () {
            Route::get('/', [RedemptionController::class, 'index'])->name('index');
            Route::get('/create', [RedemptionController::class, 'create'])->name('create');
            Route::post('/', [RedemptionController::class, 'store'])->name('store');
        });

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::put('/profile', [SettingsController::class, 'updateProfile'])->name('profile');
            Route::put('/payout', [SettingsController::class, 'updatePayout'])->name('payout');
        });
    });
});
