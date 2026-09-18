<?php

use Illuminate\Support\Facades\Route;
use Modules\AdSpend\Http\Controllers\AdSpendController;
use Modules\AdSpend\Http\Controllers\AdPlatformController;
use Modules\AdSpend\Http\Controllers\MetaAdsController;

Route::middleware('auth')->group(function () {

    // Ad Spend Campaigns
    Route::prefix('ad-spend')->name('adspend.')->group(function () {
        Route::get('/', [AdSpendController::class, 'index'])->name('index');
        Route::get('/create', [AdSpendController::class, 'create'])->name('create');
        Route::post('/', [AdSpendController::class, 'store'])->name('store');
        Route::get('/analytics', [AdSpendController::class, 'analytics'])->name('analytics');
        Route::get('/platforms', [AdPlatformController::class, 'index'])->name('platforms');
        Route::post('/platforms', [AdPlatformController::class, 'store'])->name('platforms.store');
        Route::put('/platforms/{platform}', [AdPlatformController::class, 'update'])->name('platforms.update');
        Route::patch('/platforms/{platform}/toggle-status', [AdPlatformController::class, 'toggleStatus'])->name('platforms.toggle-status');
        Route::delete('/platforms/{platform}', [AdPlatformController::class, 'destroy'])->name('platforms.destroy');

        // Meta Ads integration
        Route::get('/meta', [MetaAdsController::class, 'settings'])->name('meta.settings');
        Route::post('/meta', [MetaAdsController::class, 'saveSettings'])->name('meta.save');
        Route::post('/meta/test', [MetaAdsController::class, 'testConnection'])->name('meta.test');
        Route::post('/meta/sync', [MetaAdsController::class, 'sync'])->name('meta.sync');
        Route::get('/{campaign}', [AdSpendController::class, 'show'])->name('show');
        Route::get('/{campaign}/edit', [AdSpendController::class, 'edit'])->name('edit');
        Route::put('/{campaign}', [AdSpendController::class, 'update'])->name('update');
        Route::delete('/{campaign}', [AdSpendController::class, 'destroy'])->name('destroy');
        Route::post('/{campaign}/payment', [AdSpendController::class, 'payment'])->name('payment');
    });

});
