<?php

use Illuminate\Support\Facades\Route;

// POS module disabled — every /pos/* endpoint returns 404. We keep the
// route NAMES registered (pos.index, pos.process-sale, etc.) so that any
// stray route('pos.X') call elsewhere in the codebase still resolves to
// a URL instead of throwing — that URL just won't be reachable.
//
// To re-enable: restore POSController bindings from git history (the
// commit just before this change) and the rest still works.
Route::middleware('auth')->prefix('pos')->name('pos.')->group(function () {
    $disabled = fn () => abort(404);

    Route::get('/', $disabled)->name('index');
    Route::post('/process-sale', $disabled)->name('process-sale');
    Route::get('/search-products', $disabled)->name('search-products');
    Route::get('/get-by-barcode', $disabled)->name('get-by-barcode');
    Route::get('/search-customers', $disabled)->name('search-customers');
    Route::get('/customer-advance', $disabled)->name('customer-advance');
    Route::post('/quick-add-customer', $disabled)->name('quick-add-customer');
    Route::get('/receipt/{sale}', $disabled)->name('receipt');
    Route::get('/settings', $disabled)->name('settings');
    Route::post('/settings', $disabled)->name('settings.save');
});
