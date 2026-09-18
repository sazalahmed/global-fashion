<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\PaymentController;
use Modules\Payment\Http\Controllers\PaymentAccountController;

Route::middleware('auth')->prefix('payments')->name('payments.')->group(function () {
    Route::get('/', [PaymentController::class, 'index'])->name('index');
    Route::get('/create', [PaymentController::class, 'create'])->name('create');
    Route::post('/', [PaymentController::class, 'store'])->name('store');
    Route::get('/advances', [PaymentController::class, 'advances'])->name('advances');

    // AJAX endpoints
    Route::get('/party-search', [PaymentController::class, 'partySearch'])->name('party-search');
    Route::get('/outstanding-invoices', [PaymentController::class, 'outstandingInvoices'])->name('outstanding-invoices');

    Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
    Route::get('/{payment}/print', [PaymentController::class, 'printReceipt'])->name('print');
    Route::get('/{payment}/pdf', [PaymentController::class, 'pdf'])->name('pdf');
    Route::delete('/{payment}', [PaymentController::class, 'destroy'])->name('destroy');
});

// Payment Accounts (Cash, Mobile Banking, Bank, Card)
Route::middleware('auth')->prefix('payment-accounts')->name('payment-accounts.')->group(function () {
    Route::get('/', [PaymentAccountController::class, 'index'])->name('index');
    Route::get('/create', [PaymentAccountController::class, 'create'])->name('create');
    Route::post('/', [PaymentAccountController::class, 'store'])->name('store');
    Route::get('/api/list', [PaymentAccountController::class, 'apiList'])->name('api.list');
    Route::get('/transfers', [PaymentAccountController::class, 'transfers'])->name('transfers');
    Route::post('/transfers', [PaymentAccountController::class, 'storeTransfer'])->name('transfers.store');
    Route::get('/banks', [PaymentAccountController::class, 'banks'])->name('banks');
    Route::post('/banks', [PaymentAccountController::class, 'storeBank'])->name('banks.store');
    Route::delete('/banks/{bank}', [PaymentAccountController::class, 'destroyBank'])->name('banks.destroy');
    Route::get('/mobile-banks', [PaymentAccountController::class, 'mobileBanks'])->name('mobile-banks');
    Route::post('/mobile-banks', [PaymentAccountController::class, 'storeMobileBank'])->name('mobile-banks.store');
    Route::delete('/mobile-banks/{mobileBank}', [PaymentAccountController::class, 'destroyMobileBank'])->name('mobile-banks.destroy');
    Route::patch('/{paymentAccount}/toggle-status', [PaymentAccountController::class, 'toggleStatus'])->name('toggle-status');
    Route::get('/{paymentAccount}/ledger', [PaymentAccountController::class, 'ledger'])->name('ledger');
    Route::post('/{paymentAccount}/charge', [PaymentAccountController::class, 'storeCharge'])->name('charge');
    Route::get('/{paymentAccount}/edit', [PaymentAccountController::class, 'edit'])->name('edit');
    Route::put('/{paymentAccount}', [PaymentAccountController::class, 'update'])->name('update');
    Route::delete('/{paymentAccount}', [PaymentAccountController::class, 'destroy'])->name('destroy');
});
