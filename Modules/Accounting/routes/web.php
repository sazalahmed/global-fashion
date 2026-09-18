<?php

use Illuminate\Support\Facades\Route;
use Modules\Accounting\Http\Controllers\CapitalTransactionController;
use Modules\Accounting\Http\Controllers\ChartOfAccountsController;
use Modules\Accounting\Http\Controllers\InvestmentController;
use Modules\Accounting\Http\Controllers\JournalEntryController;
use Modules\Accounting\Http\Controllers\AccountingReportController;
use Modules\Accounting\Http\Controllers\BankReconciliationController;
use Modules\Accounting\Http\Controllers\CreditNoteController;
use Modules\Accounting\Http\Controllers\DebitNoteController;
use Modules\Accounting\Http\Controllers\PaymentReceiptController;
use Modules\Accounting\Http\Controllers\SimpleMoneyController;

// Simple-mode money pages — non-prefixed, top-level routes.
// These read from the same GL as the advanced views; they are just a
// friendlier presentation for non-accountant users.
Route::middleware('auth')->prefix('money')->name('money.')->group(function () {
    Route::get('/cashflow', [SimpleMoneyController::class, 'cashFlow'])->name('cashflow');
    Route::get('/expense', [SimpleMoneyController::class, 'expense'])->name('expense');
});

Route::middleware('auth')->prefix('capital-transactions')->name('capital-transactions.')->group(function () {
    Route::get('/', [CapitalTransactionController::class, 'index'])->name('index');
    Route::post('/', [CapitalTransactionController::class, 'store'])->name('store');
    Route::delete('/{capitalTransaction}', [CapitalTransactionController::class, 'destroy'])->name('destroy');
});

Route::middleware('auth')->prefix('accounting/investment')->name('investment.')->group(function () {
    Route::get('/', [InvestmentController::class, 'dashboard'])->name('dashboard');

    // Investors
    Route::get('/investors', [InvestmentController::class, 'investorsIndex'])->name('investors.index');
    Route::get('/investors/create', [InvestmentController::class, 'investorsCreate'])->name('investors.create');
    Route::post('/investors', [InvestmentController::class, 'investorsStore'])->name('investors.store');
    Route::get('/investors/{investor}', [InvestmentController::class, 'investorsShow'])->name('investors.show');
    Route::patch('/investors/{investor}/toggle-status', [InvestmentController::class, 'investorsToggleStatus'])->name('investors.toggle-status');
    Route::get('/investors/{investor}/edit', [InvestmentController::class, 'investorsEdit'])->name('investors.edit');
    Route::put('/investors/{investor}', [InvestmentController::class, 'investorsUpdate'])->name('investors.update');
    Route::delete('/investors/{investor}', [InvestmentController::class, 'investorsDestroy'])->name('investors.destroy');

    // Capital
    Route::get('/capital/create', [InvestmentController::class, 'capitalCreate'])->name('capital.create');
    Route::post('/capital', [InvestmentController::class, 'capitalStore'])->name('capital.store');
    Route::delete('/capital/{capital}', [InvestmentController::class, 'capitalDestroy'])->name('capital.destroy');

    // Distributions
    Route::get('/distributions', [InvestmentController::class, 'distributionsIndex'])->name('distributions.index');
    Route::get('/distributions/create', [InvestmentController::class, 'distributionsCreate'])->name('distributions.create');
    Route::post('/distributions', [InvestmentController::class, 'distributionsStore'])->name('distributions.store');
    Route::delete('/distributions/{distribution}', [InvestmentController::class, 'distributionsDestroy'])->name('distributions.destroy');
});

Route::middleware('auth')->prefix('accounting')->name('accounting.')->group(function () {
    // Chart of Accounts
    Route::get('/chart-of-accounts', [ChartOfAccountsController::class, 'index'])->name('chart-of-accounts');
    Route::get('/chart-of-accounts/create', [ChartOfAccountsController::class, 'create'])->name('chart-of-accounts.create');
    Route::post('/chart-of-accounts', [ChartOfAccountsController::class, 'store'])->name('chart-of-accounts.store');
    Route::patch('/chart-of-accounts/{account}/toggle-status', [ChartOfAccountsController::class, 'toggleStatus'])->name('chart-of-accounts.toggle-status');
    Route::get('/chart-of-accounts/{account}/edit', [ChartOfAccountsController::class, 'edit'])->name('chart-of-accounts.edit');
    Route::put('/chart-of-accounts/{account}', [ChartOfAccountsController::class, 'update'])->name('chart-of-accounts.update');
    Route::delete('/chart-of-accounts/{account}', [ChartOfAccountsController::class, 'destroy'])->name('chart-of-accounts.destroy');

    // Journal Entries
    Route::get('/journal-entries', [JournalEntryController::class, 'index'])->name('journal-entries');
    Route::get('/journal-entries/create', [JournalEntryController::class, 'create'])->name('journal-entries.create');
    Route::post('/journal-entries', [JournalEntryController::class, 'store'])->name('journal-entries.store');
    Route::get('/journal-entries/{entry}', [JournalEntryController::class, 'show'])->name('journal-entries.show');
    Route::post('/journal-entries/{entry}/post', [JournalEntryController::class, 'post'])->name('journal-entries.post');
    Route::patch('/journal-entries/{entry}/date', [JournalEntryController::class, 'updateDate'])->name('journal-entries.update-date');
    Route::post('/journal-entries/{entry}/void', [JournalEntryController::class, 'void'])->name('journal-entries.void');
    Route::delete('/journal-entries/{entry}', [JournalEntryController::class, 'destroy'])->name('journal-entries.destroy');

    // Financial Reports
    Route::get('/general-ledger', [AccountingReportController::class, 'generalLedger'])->name('general-ledger');
    Route::get('/trial-balance', [AccountingReportController::class, 'trialBalance'])->name('trial-balance');
    Route::get('/profit-loss', [AccountingReportController::class, 'profitLoss'])->name('profit-loss');
    Route::get('/balance-sheet', [AccountingReportController::class, 'balanceSheet'])->name('balance-sheet');
    Route::get('/cash-flow', [AccountingReportController::class, 'cashFlow'])->name('cash-flow');

    // Bank Reconciliation
    Route::get('/bank-reconciliation', [BankReconciliationController::class, 'index'])->name('bank-reconciliation');
    Route::post('/bank-reconciliation/start', [BankReconciliationController::class, 'start'])->name('bank-reconciliation.start');
    Route::get('/bank-reconciliation/{reconciliation}', [BankReconciliationController::class, 'show'])->name('bank-reconciliation.show');
    Route::post('/bank-reconciliation/{reconciliation}/import', [BankReconciliationController::class, 'importStatement'])->name('bank-reconciliation.import');
    Route::post('/bank-reconciliation/match', [BankReconciliationController::class, 'match'])->name('bank-reconciliation.match');
    Route::post('/bank-reconciliation/unmatch', [BankReconciliationController::class, 'unmatch'])->name('bank-reconciliation.unmatch');
    Route::post('/bank-reconciliation/{reconciliation}/complete', [BankReconciliationController::class, 'complete'])->name('bank-reconciliation.complete');

    // Credit Notes
    Route::get('/credit-notes', [CreditNoteController::class, 'index'])->name('credit-notes.index');
    Route::get('/credit-notes/create', [CreditNoteController::class, 'create'])->name('credit-notes.create');
    Route::post('/credit-notes', [CreditNoteController::class, 'store'])->name('credit-notes.store');
    Route::get('/credit-notes/{creditNote}', [CreditNoteController::class, 'show'])->name('credit-notes.show');
    Route::post('/credit-notes/{creditNote}/issue', [CreditNoteController::class, 'issue'])->name('credit-notes.issue');
    Route::post('/credit-notes/{creditNote}/cancel', [CreditNoteController::class, 'cancel'])->name('credit-notes.cancel');
    Route::get('/credit-notes/{creditNote}/print', [CreditNoteController::class, 'print'])->name('credit-notes.print');
    Route::get('/credit-notes/{creditNote}/pdf', [CreditNoteController::class, 'pdf'])->name('credit-notes.pdf');
    Route::delete('/credit-notes/{creditNote}', [CreditNoteController::class, 'destroy'])->name('credit-notes.destroy');

    // Debit Notes
    Route::get('/debit-notes', [DebitNoteController::class, 'index'])->name('debit-notes.index');
    Route::get('/debit-notes/create', [DebitNoteController::class, 'create'])->name('debit-notes.create');
    Route::post('/debit-notes', [DebitNoteController::class, 'store'])->name('debit-notes.store');
    Route::get('/debit-notes/{debitNote}', [DebitNoteController::class, 'show'])->name('debit-notes.show');
    Route::post('/debit-notes/{debitNote}/issue', [DebitNoteController::class, 'issue'])->name('debit-notes.issue');
    Route::post('/debit-notes/{debitNote}/cancel', [DebitNoteController::class, 'cancel'])->name('debit-notes.cancel');
    Route::get('/debit-notes/{debitNote}/print', [DebitNoteController::class, 'print'])->name('debit-notes.print');
    Route::get('/debit-notes/{debitNote}/pdf', [DebitNoteController::class, 'pdf'])->name('debit-notes.pdf');
    Route::delete('/debit-notes/{debitNote}', [DebitNoteController::class, 'destroy'])->name('debit-notes.destroy');

    // Payment Receipts
    Route::get('/receipts', [PaymentReceiptController::class, 'index'])->name('receipts.index');
    Route::get('/receipts/{receipt}', [PaymentReceiptController::class, 'show'])->name('receipts.show');
    Route::get('/receipts/{receipt}/print', [PaymentReceiptController::class, 'print'])->name('receipts.print');
});
