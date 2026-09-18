<?php

use Illuminate\Support\Facades\Route;
use Modules\PurchaseReturn\Http\Controllers\PurchaseReturnController;
use Modules\PurchaseReturn\Http\Controllers\PurchaseReturnTypeController;

Route::middleware('auth')->prefix('purchase-return-types')->name('purchase-return-types.')->group(function () {
    Route::get('/', [PurchaseReturnTypeController::class, 'index'])->name('index');
    Route::post('/', [PurchaseReturnTypeController::class, 'store'])->name('store');
    Route::put('/{type}', [PurchaseReturnTypeController::class, 'update'])->name('update');
    Route::patch('/{type}/toggle-status', [PurchaseReturnTypeController::class, 'toggleStatus'])->name('toggle-status');
    Route::delete('/{type}', [PurchaseReturnTypeController::class, 'destroy'])->name('destroy');
});

Route::middleware('auth')->prefix('purchase-returns')->name('purchase-returns.')->group(function () {
    Route::get('/', [PurchaseReturnController::class, 'index'])->name('index');
    Route::get('/create', [PurchaseReturnController::class, 'create'])->name('create');
    Route::get('/purchase-items/{purchase}', [PurchaseReturnController::class, 'purchaseItems'])->name('purchase-items');
    Route::post('/', [PurchaseReturnController::class, 'store'])->name('store');
    Route::get('/{purchaseReturn}', [PurchaseReturnController::class, 'show'])->name('show');
    Route::get('/{purchaseReturn}/edit', [PurchaseReturnController::class, 'edit'])->name('edit');
    Route::put('/{purchaseReturn}', [PurchaseReturnController::class, 'update'])->name('update');
    Route::delete('/{purchaseReturn}', [PurchaseReturnController::class, 'destroy'])->name('destroy');
    Route::post('/{purchaseReturn}/complete', [PurchaseReturnController::class, 'complete'])->name('complete');
    Route::post('/{purchaseReturn}/cancel', [PurchaseReturnController::class, 'cancel'])->name('cancel');
    Route::get('/{purchaseReturn}/print', [PurchaseReturnController::class, 'print'])->name('print');
    Route::get('/{purchaseReturn}/pdf', [PurchaseReturnController::class, 'pdf'])->name('pdf');
});
