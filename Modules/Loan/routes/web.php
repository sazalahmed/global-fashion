<?php

use Illuminate\Support\Facades\Route;
use Modules\Loan\Http\Controllers\LenderController;
use Modules\Loan\Http\Controllers\LoanController;
use Modules\Loan\Http\Controllers\PersonalLoanController;

Route::middleware('auth')->group(function () {

    // Lenders
    Route::prefix('lenders')->name('lenders.')->group(function () {
        Route::get('/', [LenderController::class, 'index'])->name('index');
        Route::get('/create', [LenderController::class, 'create'])->name('create');
        Route::post('/', [LenderController::class, 'store'])->name('store');
        Route::get('/{lender}', [LenderController::class, 'show'])->name('show');
        Route::patch('/{lender}/toggle-status', [LenderController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/{lender}/edit', [LenderController::class, 'edit'])->name('edit');
        Route::put('/{lender}', [LenderController::class, 'update'])->name('update');
        Route::delete('/{lender}', [LenderController::class, 'destroy'])->name('destroy');
    });

    // Loans
    Route::prefix('loans')->name('loans.')->group(function () {
        Route::get('/', [LoanController::class, 'index'])->name('index');
        Route::get('/create', [LoanController::class, 'create'])->name('create');
        Route::post('/', [LoanController::class, 'store'])->name('store');
        Route::get('/{loan}', [LoanController::class, 'show'])->name('show');
        Route::post('/{loan}/repayment', [LoanController::class, 'repayment'])->name('repayment');
        Route::put('/{loan}/reschedule', [LoanController::class, 'reschedule'])->name('reschedule');
        Route::post('/{loan}/cancel', [LoanController::class, 'cancel'])->name('cancel');
    });

    // Personal Loans (business lends to a borrower — running receivable account)
    Route::prefix('personal-loans')->name('personal-loans.')->group(function () {
        Route::get('/', [PersonalLoanController::class, 'index'])->name('index');
        Route::get('/create', [PersonalLoanController::class, 'create'])->name('create');
        Route::post('/', [PersonalLoanController::class, 'store'])->name('store');
        Route::get('/{borrower}', [PersonalLoanController::class, 'show'])->name('show');
        Route::get('/{borrower}/edit', [PersonalLoanController::class, 'edit'])->name('edit');
        Route::put('/{borrower}', [PersonalLoanController::class, 'update'])->name('update');
        Route::delete('/{borrower}', [PersonalLoanController::class, 'destroy'])->name('destroy');
        Route::post('/{borrower}/disburse', [PersonalLoanController::class, 'disburse'])->name('disburse');
        Route::post('/{borrower}/repay', [PersonalLoanController::class, 'repay'])->name('repay');
        Route::post('/{borrower}/take', [PersonalLoanController::class, 'take'])->name('take');
        Route::post('/{borrower}/pay-back', [PersonalLoanController::class, 'payBack'])->name('pay-back');
    });

    // The short-lived separate "Loans Taken" section was merged into Personal
    // Loans (two-way ledger per person) — keep old links working.
    Route::get('loans-taken', fn () => redirect()->route('personal-loans.index'));
    Route::get('loans-taken/{borrower}', fn (\Modules\Loan\Models\Borrower $borrower) => redirect()->route('personal-loans.show', $borrower));

});
