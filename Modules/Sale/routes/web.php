<?php

use Illuminate\Support\Facades\Route;
use Modules\Sale\Http\Controllers\SaleController;

Route::middleware('auth')->prefix('sales')->name('sales.')->group(function () {
    Route::get('/', [SaleController::class, 'index'])->name('index');
    Route::get('/create', [SaleController::class, 'create'])->name('create');
    Route::post('/', [SaleController::class, 'store'])->name('store');
    Route::post('/bulk-status', [SaleController::class, 'bulkStatus'])->name('bulk-status');
    Route::post('/bulk-assign', [SaleController::class, 'bulkAssign'])->name('bulk-assign');
    Route::get('/bulk-print', [SaleController::class, 'bulkPrint'])->name('bulk-print');
    Route::get('/bulk-label', [SaleController::class, 'bulkLabel'])->name('bulk-label');
    Route::post('/bulk-send-to-courier', [SaleController::class, 'bulkSendToCourier'])->name('bulk-send-to-courier');
    Route::post('/{sale}/send-to-courier', [SaleController::class, 'sendToCourier'])->name('send-to-courier');
    Route::post('/{sale}/status', [SaleController::class, 'updateStatus'])->name('status');
    Route::post('/{sale}/assign', [SaleController::class, 'assignStaff'])->name('assign');
    Route::get('/{sale}/fraud-check', [SaleController::class, 'fraudCheck'])->name('fraud-check');
    Route::get('/{sale}/quick-view', [SaleController::class, 'quickView'])->name('quick-view');
    Route::post('/{sale}/refresh-courier-status', [SaleController::class, 'refreshCourierStatus'])->name('refresh-courier-status');
    Route::get('/{sale}', [SaleController::class, 'show'])->name('show');
    Route::get('/{sale}/edit', [SaleController::class, 'edit'])->name('edit');
    Route::put('/{sale}', [SaleController::class, 'update'])->name('update');
    Route::delete('/{sale}', [SaleController::class, 'destroy'])->name('destroy');
    Route::get('/{sale}/print', [SaleController::class, 'print'])->name('print');
    Route::get('/{sale}/label', [SaleController::class, 'label'])->name('label');
    Route::get('/{sale}/pdf', [SaleController::class, 'pdf'])->name('pdf');
    Route::post('/{sale}/email', [SaleController::class, 'email'])->name('email');
    Route::post('/{sale}/sms', [SaleController::class, 'sms'])->name('sms');
    Route::get('/{sale}/share', [SaleController::class, 'share'])->name('share');
});
