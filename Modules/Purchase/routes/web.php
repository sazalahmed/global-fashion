<?php

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Http\Controllers\PurchaseController;
use Modules\Purchase\Http\Controllers\GrnController;
use Modules\Purchase\Http\Controllers\RequisitionController;

Route::middleware('auth')->prefix('purchases')->name('purchases.')->group(function () {
    Route::get('/', [PurchaseController::class, 'index'])->name('index');
    Route::get('/create', [PurchaseController::class, 'create'])->name('create');
    Route::post('/', [PurchaseController::class, 'store'])->name('store');
    Route::get('/{purchase}', [PurchaseController::class, 'show'])->name('show');
    Route::get('/{purchase}/edit', [PurchaseController::class, 'edit'])->name('edit');
    Route::put('/{purchase}', [PurchaseController::class, 'update'])->name('update');
    Route::delete('/{purchase}', [PurchaseController::class, 'destroy'])->name('destroy');
    Route::post('/{purchase}/approve', [PurchaseController::class, 'approve'])->name('approve');
    Route::post('/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('cancel');
    Route::get('/{purchase}/receive', [GrnController::class, 'create'])->name('receive.create');
    Route::post('/{purchase}/receive', [GrnController::class, 'store'])->name('receive.store');
    Route::post('/quick-add-supplier', [PurchaseController::class, 'quickAddSupplier'])->name('quick-add-supplier');
    Route::get('/{purchase}/print', [PurchaseController::class, 'print'])->name('print');
});

Route::middleware('auth')->prefix('requisitions')->name('requisitions.')->group(function () {
    Route::get('/', [RequisitionController::class, 'index'])->name('index');
    Route::get('/create', [RequisitionController::class, 'create'])->name('create');
    Route::post('/', [RequisitionController::class, 'store'])->name('store');
    Route::get('/{requisition}', [RequisitionController::class, 'show'])->name('show');
    Route::get('/{requisition}/edit', [RequisitionController::class, 'edit'])->name('edit');
    Route::put('/{requisition}', [RequisitionController::class, 'update'])->name('update');
    Route::delete('/{requisition}', [RequisitionController::class, 'destroy'])->name('destroy');
    Route::post('/{requisition}/approve', [RequisitionController::class, 'approve'])->name('approve');
    Route::post('/{requisition}/reject', [RequisitionController::class, 'reject'])->name('reject');
    Route::post('/{requisition}/cancel', [RequisitionController::class, 'cancel'])->name('cancel');
    Route::post('/{requisition}/mark-fulfilled', [RequisitionController::class, 'markFulfilled'])->name('mark-fulfilled');
    Route::get('/{requisition}/convert', [RequisitionController::class, 'convert'])->name('convert');
});
