<?php

use Illuminate\Support\Facades\Route;
use Modules\Location\Http\Controllers\LocationController;

Route::middleware('auth')->prefix('locations')->name('locations.')->group(function () {
    Route::get('/', [LocationController::class, 'index'])->name('index');

    // Districts
    Route::post('/districts', [LocationController::class, 'storeDistrict'])->name('districts.store');
    Route::put('/districts/{district}', [LocationController::class, 'updateDistrict'])->name('districts.update');
    Route::delete('/districts/{district}', [LocationController::class, 'destroyDistrict'])->name('districts.destroy');
    Route::get('/districts/{district}/thanas', [LocationController::class, 'thanasByDistrict'])->name('districts.thanas');

    // Thanas
    Route::post('/thanas', [LocationController::class, 'storeThana'])->name('thanas.store');
    Route::put('/thanas/{thana}', [LocationController::class, 'updateThana'])->name('thanas.update');
    Route::delete('/thanas/{thana}', [LocationController::class, 'destroyThana'])->name('thanas.destroy');
});
