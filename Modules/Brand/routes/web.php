<?php

use Illuminate\Support\Facades\Route;
use Modules\Brand\Http\Controllers\BrandController;

Route::middleware('auth')->prefix('brands')->name('brands.')->group(function () {
    Route::get('/', [BrandController::class, 'index'])->name('index');
    Route::get('/create', [BrandController::class, 'create'])->name('create');
    Route::post('/', [BrandController::class, 'store'])->name('store');
    Route::post('/quick-store', [BrandController::class, 'quickStore'])->name('quick-store');
    Route::get('/{brand}/edit', [BrandController::class, 'edit'])->name('edit');
    Route::put('/{brand}', [BrandController::class, 'update'])->name('update');
    Route::patch('/{brand}/toggle-status', [BrandController::class, 'toggleStatus'])->name('toggle-status');
    Route::delete('/{brand}', [BrandController::class, 'destroy'])->name('destroy');
});
