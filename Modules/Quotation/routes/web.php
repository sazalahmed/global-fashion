<?php

use Illuminate\Support\Facades\Route;
use Modules\Quotation\Http\Controllers\QuotationController;

Route::middleware('auth')->group(function () {
    Route::get('quotations/search-products', [QuotationController::class, 'searchProducts'])
        ->name('quotations.search-products');

    // Bulk actions — declared before the resource route so the literal segments
    // are not captured by quotations/{quotation}.
    Route::post('quotations/bulk-status', [QuotationController::class, 'bulkStatus'])
        ->name('quotations.bulk-status');
    Route::delete('quotations/bulk-delete', [QuotationController::class, 'bulkDelete'])
        ->name('quotations.bulk-delete');

    Route::resource('quotations', QuotationController::class)->names('quotations');

    Route::post('quotations/{quotation}/convert-to-sale', [QuotationController::class, 'convertToSale'])
        ->name('quotations.convert-to-sale');

    Route::post('quotations/{quotation}/duplicate', [QuotationController::class, 'duplicate'])
        ->name('quotations.duplicate');

    Route::get('quotations/{quotation}/print', [QuotationController::class, 'print'])
        ->name('quotations.print');

    Route::get('quotations/{quotation}/pdf', [QuotationController::class, 'pdf'])
        ->name('quotations.pdf');
});
