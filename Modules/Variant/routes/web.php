<?php

use Illuminate\Support\Facades\Route;
use Modules\Variant\Http\Controllers\VariantChartController;
use Modules\Variant\Http\Controllers\VariantController;

Route::middleware('auth')->prefix('variants')->name('variants.')->group(function () {
    Route::get('/', [VariantController::class, 'index'])->name('index');
    Route::get('/create', [VariantController::class, 'create'])->name('create');
    Route::post('/', [VariantController::class, 'store'])->name('store');
    Route::patch('/values/{value}/active', [VariantController::class, 'setValueActive'])->name('values.active');
    Route::get('/{variant}', [VariantController::class, 'show'])->name('show');
    Route::get('/{variant}/edit', [VariantController::class, 'edit'])->name('edit');
    Route::put('/{variant}', [VariantController::class, 'update'])->name('update');
    Route::patch('/{variant}/toggle-status', [VariantController::class, 'toggleStatus'])->name('toggle-status');
    Route::delete('/{variant}', [VariantController::class, 'destroy'])->name('destroy');

    // Measurement chart (e.g. size chart) bound to the attribute.
    Route::prefix('{variant}/chart')->name('chart.')->group(function () {
        Route::post('rows',              [VariantChartController::class, 'storeRow'])->name('rows.store');
        Route::put('rows/{row}',         [VariantChartController::class, 'updateRow'])->name('rows.update');
        Route::delete('rows/{row}',      [VariantChartController::class, 'destroyRow'])->name('rows.destroy');
        Route::post('rows/reorder',      [VariantChartController::class, 'reorderRows'])->name('rows.reorder');
        Route::post('values',            [VariantChartController::class, 'upsertValue'])->name('values.upsert');
    });
});
