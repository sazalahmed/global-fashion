<?php

use Illuminate\Support\Facades\Route;
use Modules\Report\Http\Controllers\ReportController;

Route::middleware('auth')->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('/dts', [ReportController::class, 'dts'])->name('dts');
    Route::get('/category-wise', [ReportController::class, 'categoryWise'])->name('category-wise');
    Route::get('/monthly-summary', [ReportController::class, 'monthlySummary'])->name('monthly-summary');
    Route::get('/detail-sales', [ReportController::class, 'detailSales'])->name('detail-sales');
    Route::get('/receivables-aging', [ReportController::class, 'receivablesAging'])->name('receivables-aging');
    Route::get('/cash-movement', [ReportController::class, 'cashMovement'])->name('cash-movement');
    Route::get('/supplier-payments', [ReportController::class, 'supplierPayments'])->name('supplier-payments');
    Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
    Route::get('/purchase', [ReportController::class, 'purchase'])->name('purchase');
    Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
    Route::get('/financial', [ReportController::class, 'financial'])->name('financial');
    Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
    Route::get('/staff', [ReportController::class, 'staff'])->name('staff');
    Route::get('/customer', [ReportController::class, 'customer'])->name('customer');
    Route::get('/custom', [ReportController::class, 'custom'])->name('custom');
    Route::post('/custom/generate', [ReportController::class, 'customGenerate'])->name('custom.generate');
    Route::get('/export/{type}', [ReportController::class, 'exportPdf'])->name('export');
});
