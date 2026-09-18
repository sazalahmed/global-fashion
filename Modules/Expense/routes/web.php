<?php

use Illuminate\Support\Facades\Route;
use Modules\Expense\Http\Controllers\ExpenseCategoryController;
use Modules\Expense\Http\Controllers\ExpenseController;

Route::middleware('auth')->prefix('expense-categories')->name('expense-categories.')->group(function () {
    Route::get('/', [ExpenseCategoryController::class, 'index'])->name('index');
    Route::get('/create', [ExpenseCategoryController::class, 'create'])->name('create');
    Route::post('/', [ExpenseCategoryController::class, 'store'])->name('store');
    Route::post('/quick-add', [ExpenseCategoryController::class, 'quickAdd'])->name('quick-add');
    Route::patch('/{category}/toggle-status', [ExpenseCategoryController::class, 'toggleStatus'])->name('toggle-status');
    Route::get('/{category}/edit', [ExpenseCategoryController::class, 'edit'])->name('edit');
    Route::put('/{category}', [ExpenseCategoryController::class, 'update'])->name('update');
    Route::delete('/{category}', [ExpenseCategoryController::class, 'destroy'])->name('destroy');
});

Route::middleware('auth')->prefix('expenses')->name('expenses.')->group(function () {
    Route::get('/', [ExpenseController::class, 'index'])->name('index');
    Route::get('/create', [ExpenseController::class, 'create'])->name('create');
    Route::post('/', [ExpenseController::class, 'store'])->name('store');
    Route::get('/ledger', [ExpenseController::class, 'ledger'])->name('ledger');
    Route::get('/{expense}', [ExpenseController::class, 'show'])->name('show');
    Route::get('/{expense}/edit', [ExpenseController::class, 'edit'])->name('edit');
    Route::put('/{expense}', [ExpenseController::class, 'update'])->name('update');
    Route::delete('/{expense}', [ExpenseController::class, 'destroy'])->name('destroy');
    Route::post('/{expense}/approve', [ExpenseController::class, 'approve'])->name('approve');
    Route::post('/{expense}/reject', [ExpenseController::class, 'reject'])->name('reject');
    Route::post('/{expense}/mark-paid', [ExpenseController::class, 'markPaid'])->name('mark-paid');
    Route::post('/{expense}/cancel', [ExpenseController::class, 'cancel'])->name('cancel');
    Route::post('/{expense}/record-payment', [ExpenseController::class, 'recordPayment'])->name('record-payment');
    Route::get('/{expense}/print', [ExpenseController::class, 'print'])->name('print');
});
