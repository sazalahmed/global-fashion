<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductController;

Route::middleware('auth')->prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/create', [ProductController::class, 'create'])->name('create');
    Route::post('/', [ProductController::class, 'store'])->name('store');
    Route::post('/generate-sku', [ProductController::class, 'generateSku'])->name('generate-sku');
    Route::post('/generate-barcode', [ProductController::class, 'generateBarcode'])->name('generate-barcode');
    Route::get('/ajax/variant-attributes', [ProductController::class, 'getVariantAttributes'])->name('ajax.variant-attributes');
    Route::post('/ajax/variant-attributes/{attribute}/values', [ProductController::class, 'storeVariantAttributeValue'])->name('ajax.store-variant-value');
    Route::get('/ajax/size-chart-defaults', [ProductController::class, 'sizeChartDefaults'])->name('ajax.size-chart-defaults');
    Route::post('/{product}/variants/generate', [ProductController::class, 'generateVariants'])->name('generate-variants');
    Route::put('/{product}/variants/bulk-update', [ProductController::class, 'bulkUpdateVariants'])->name('bulk-update-variants');
    Route::patch('/{product}/variants/{variant}/active', [ProductController::class, 'setVariantActive'])->name('variant-active');
    Route::delete('/{product}/variants/by-values', [ProductController::class, 'removeVariantValues'])->name('remove-variant-values');
    Route::delete('/{product}/variants/{variant}', [ProductController::class, 'destroyVariant'])->name('destroy-variant');
    Route::patch('/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('toggle-status');
    Route::post('/{product}/duplicate', [ProductController::class, 'duplicate'])->name('duplicate');
    Route::post('/reorder', [ProductController::class, 'reorder'])->name('reorder');

    // Combo management (moved from /admin/ecommerce/combos). Reuses the
    // Ecommerce ComboController; combos live in the unified products list.
    Route::get('combos/create', [\Modules\Ecommerce\Http\Controllers\ComboController::class, 'create'])->name('combos.create');
    Route::post('combos', [\Modules\Ecommerce\Http\Controllers\ComboController::class, 'store'])->name('combos.store');
    Route::get('combos/{combo}/edit', [\Modules\Ecommerce\Http\Controllers\ComboController::class, 'edit'])->name('combos.edit');
    Route::put('combos/{combo}', [\Modules\Ecommerce\Http\Controllers\ComboController::class, 'update'])->name('combos.update');
    Route::delete('combos/{combo}', [\Modules\Ecommerce\Http\Controllers\ComboController::class, 'destroy'])->name('combos.destroy');
    Route::patch('combos/{combo}/toggle-status', [\Modules\Ecommerce\Http\Controllers\ComboController::class, 'toggleStatus'])->name('combos.toggle-status');

    // Unified per-category (or global) drag-reorder of products + combos.
    Route::post('catalog/reorder', [ProductController::class, 'reorderCatalog'])->name('catalog.reorder');

    Route::get('/{product}', [ProductController::class, 'show'])->name('show');
    Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
    Route::put('/{product}', [ProductController::class, 'update'])->name('update');
    Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
});
