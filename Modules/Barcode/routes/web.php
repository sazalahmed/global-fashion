<?php

use Illuminate\Support\Facades\Route;
use Modules\Barcode\Http\Controllers\BarcodeController;

Route::middleware('auth')->prefix('barcode')->name('barcode.')->group(function () {
    Route::get('/', [BarcodeController::class, 'index'])->name('index');
    Route::get('/search', [BarcodeController::class, 'search'])->name('search');
    Route::post('/generate', [BarcodeController::class, 'generate'])->name('generate');
    Route::post('/print', [BarcodeController::class, 'print'])->name('print');
});
