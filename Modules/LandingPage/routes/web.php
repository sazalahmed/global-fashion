<?php

use Illuminate\Support\Facades\Route;
use Modules\LandingPage\Http\Controllers\LandingPageController;

Route::middleware('auth')->prefix('landing-pages')->name('landing-pages.')->group(function () {
    Route::get('/', [LandingPageController::class, 'index'])->name('index');
    Route::get('/create', [LandingPageController::class, 'create'])->name('create');
    Route::post('/', [LandingPageController::class, 'store'])->name('store');
    Route::get('/{landingPage}/edit', [LandingPageController::class, 'edit'])->name('edit');
    Route::put('/{landingPage}', [LandingPageController::class, 'update'])->name('update');
    Route::delete('/{landingPage}', [LandingPageController::class, 'destroy'])->name('destroy');
    Route::post('/{landingPage}/activate', [LandingPageController::class, 'activate'])->name('activate');
    Route::post('/deactivate', [LandingPageController::class, 'deactivate'])->name('deactivate');
    Route::get('/{landingPage}/preview', [LandingPageController::class, 'preview'])->name('preview');
    Route::post('/{landingPage}/preview-data', [LandingPageController::class, 'previewWithData'])->name('preview-data');
    Route::post('/preview-blank', [LandingPageController::class, 'previewBlank'])->name('preview-blank');
    Route::get('/search-products', [LandingPageController::class, 'searchProducts'])->name('search-products');
});
