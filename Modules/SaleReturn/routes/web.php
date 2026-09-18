<?php

use Illuminate\Support\Facades\Route;
use Modules\SaleReturn\Http\Controllers\SaleReturnController;

Route::middleware('auth')->prefix('sale-returns')->name('sale-returns.')->group(function () {
    Route::get('/', [SaleReturnController::class, 'index'])->name('index');
    Route::get('/create', [SaleReturnController::class, 'create'])->name('create');
    Route::post('/', [SaleReturnController::class, 'store'])->name('store');
    Route::get('/sale-items/{sale}', [SaleReturnController::class, 'saleItems'])->name('sale-items');
    Route::get('/{saleReturn}', [SaleReturnController::class, 'show'])->name('show');
    Route::get('/{saleReturn}/edit', [SaleReturnController::class, 'edit'])->name('edit');
    Route::put('/{saleReturn}', [SaleReturnController::class, 'update'])->name('update');
    Route::delete('/{saleReturn}', [SaleReturnController::class, 'destroy'])->name('destroy');
    Route::post('/{saleReturn}/approve', [SaleReturnController::class, 'approve'])->name('approve');
    Route::post('/{saleReturn}/complete', [SaleReturnController::class, 'complete'])->name('complete');
    Route::post('/{saleReturn}/cancel', [SaleReturnController::class, 'cancel'])->name('cancel');
    Route::get('/{saleReturn}/print', [SaleReturnController::class, 'print'])->name('print');
    Route::get('/{saleReturn}/pdf', [SaleReturnController::class, 'pdf'])->name('pdf');
});
