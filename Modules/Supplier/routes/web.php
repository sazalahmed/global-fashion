<?php

use Illuminate\Support\Facades\Route;
use Modules\Supplier\Http\Controllers\SupplierController;
use Modules\Supplier\Http\Controllers\SupplierGroupController;
use Modules\Supplier\Http\Controllers\SupplierPaymentController;

Route::middleware('auth')->prefix('supplier-groups')->name('supplier-groups.')->group(function () {
    Route::get('/', [SupplierGroupController::class, 'index'])->name('index');
    Route::post('/', [SupplierGroupController::class, 'store'])->name('store');
    Route::put('/{group}', [SupplierGroupController::class, 'update'])->name('update');
    Route::patch('/{group}/toggle-status', [SupplierGroupController::class, 'toggleStatus'])->name('toggle-status');
    Route::delete('/{group}', [SupplierGroupController::class, 'destroy'])->name('destroy');
});

Route::middleware('auth')->prefix('suppliers')->name('supplier.')->group(function () {
    Route::get('/', [SupplierController::class, 'index'])->name('index');
    Route::get('/create', [SupplierController::class, 'create'])->name('create');
    Route::get('/payable', [SupplierController::class, 'payable'])->name('payable');
    Route::post('/', [SupplierController::class, 'store'])->name('store');
    Route::get('/{supplier}', [SupplierController::class, 'show'])->name('show');
    Route::get('/{supplier}/edit', [SupplierController::class, 'edit'])->name('edit');
    Route::put('/{supplier}', [SupplierController::class, 'update'])->name('update');
    Route::patch('/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('toggle-status');
    Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('destroy');
    Route::get('/{supplier}/ledger', [SupplierController::class, 'ledger'])->name('ledger');
    Route::get('/{supplier}/print', [SupplierController::class, 'print'])->name('print');
    Route::get('/{supplier}/export', [SupplierController::class, 'export'])->name('export');
    Route::post('/{supplier}/payments', [SupplierPaymentController::class, 'store'])->name('payment.store');
});
