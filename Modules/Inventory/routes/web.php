<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\AdjustmentReasonController;
use Modules\Inventory\Http\Controllers\InventoryController;

Route::middleware('auth')->prefix('inventory')->name('inventory.')->group(function () {
    // Stock Overview
    Route::get('/', [InventoryController::class, 'index'])->name('index');
    Route::get('/alerts', [InventoryController::class, 'alerts'])->name('alerts');
    Route::get('/ledger', [InventoryController::class, 'ledger'])->name('ledger');
    Route::get('/stock-ledger', [InventoryController::class, 'stockLedger'])->name('stock-ledger');

    // Stock Adjustments
    Route::get('/adjustments', [InventoryController::class, 'adjustments'])->name('adjustments');
    Route::get('/adjustments/create', [InventoryController::class, 'createAdjustment'])->name('adjustments.create');
    Route::post('/adjustments', [InventoryController::class, 'storeAdjustment'])->name('adjustments.store');
    Route::get('/adjustments/{adjustment}', [InventoryController::class, 'showAdjustment'])->name('adjustments.show');
    Route::get('/adjustments/{adjustment}/edit', [InventoryController::class, 'editAdjustment'])->name('adjustments.edit');
    Route::put('/adjustments/{adjustment}', [InventoryController::class, 'updateAdjustment'])->name('adjustments.update');
    Route::post('/adjustments/{adjustment}/approve', [InventoryController::class, 'approveAdjustment'])->name('adjustments.approve');
    Route::post('/adjustments/{adjustment}/cancel', [InventoryController::class, 'cancelAdjustment'])->name('adjustments.cancel');
    Route::delete('/adjustments/{adjustment}', [InventoryController::class, 'destroyAdjustment'])->name('adjustments.destroy');
    Route::get('/adjustments/{adjustment}/print', [InventoryController::class, 'printAdjustment'])->name('adjustments.print');

    // Adjustment Reasons
    Route::post('/adjustment-reasons/{adjustment_reason}/toggle-status', [AdjustmentReasonController::class, 'toggleStatus'])->name('adjustment-reasons.toggle-status');
    Route::resource('adjustment-reasons', AdjustmentReasonController::class)->except(['show']);

    // Stock Reconciliation
    Route::get('/reconciliation', [InventoryController::class, 'reconciliation'])->name('reconciliation');
});
