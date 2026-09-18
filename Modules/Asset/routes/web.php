<?php

use Illuminate\Support\Facades\Route;
use Modules\Asset\Http\Controllers\AssetCategoryController;
use Modules\Asset\Http\Controllers\AssetController;

Route::middleware('auth')->prefix('asset-categories')->name('asset-categories.')->group(function () {
    Route::get('/', [AssetCategoryController::class, 'index'])->name('index');
    Route::get('/create', [AssetCategoryController::class, 'create'])->name('create');
    Route::post('/', [AssetCategoryController::class, 'store'])->name('store');
    Route::get('/{category}/edit', [AssetCategoryController::class, 'edit'])->name('edit');
    Route::put('/{category}', [AssetCategoryController::class, 'update'])->name('update');
    Route::delete('/{category}', [AssetCategoryController::class, 'destroy'])->name('destroy');
});

Route::middleware('auth')->prefix('assets')->name('assets.')->group(function () {
    Route::get('/', [AssetController::class, 'index'])->name('index');
    Route::get('/create', [AssetController::class, 'create'])->name('create');
    Route::post('/', [AssetController::class, 'store'])->name('store');
    Route::post('/depreciation', [AssetController::class, 'depreciation'])->name('depreciation');
    Route::get('/{asset}', [AssetController::class, 'show'])->name('show');
    Route::get('/{asset}/edit', [AssetController::class, 'edit'])->name('edit');
    Route::put('/{asset}', [AssetController::class, 'update'])->name('update');
    Route::delete('/{asset}', [AssetController::class, 'destroy'])->name('destroy');
    Route::post('/{asset}/maintenance', [AssetController::class, 'maintenance'])->name('maintenance');
    Route::post('/{asset}/payment', [AssetController::class, 'recordPayment'])->name('payment');
    Route::get('/{asset}/invoice', [AssetController::class, 'invoice'])->name('invoice');
    Route::get('/{asset}/invoice/pdf', [AssetController::class, 'invoicePdf'])->name('invoice.pdf');
    Route::get('/{asset}/ledger', [AssetController::class, 'ledger'])->name('ledger');
});

Route::middleware('auth')->prefix('asset-payments')->name('assets.payment.')->group(function () {
    Route::get('/{payment}/receipt', [AssetController::class, 'paymentReceipt'])->name('receipt');
});
