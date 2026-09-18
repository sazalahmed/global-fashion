<?php

use Illuminate\Support\Facades\Route;
use Modules\Payroll\Http\Controllers\PayrollController;

Route::middleware('auth')->prefix('payroll')->name('payroll.')->group(function () {
    Route::get('/', [PayrollController::class, 'index'])->name('index');
    Route::get('/generate', [PayrollController::class, 'generate'])->name('generate');
    Route::post('/generate', [PayrollController::class, 'generateStore'])->name('generate.store');
    Route::get('/salary-structures', [PayrollController::class, 'salaryStructures'])->name('salary-structures');
    Route::get('/salary-structures/create', [PayrollController::class, 'salaryStructureCreate'])->name('salary-structures.create');
    Route::post('/salary-structures', [PayrollController::class, 'salaryStructureStore'])->name('salary-structures.store');
    Route::get('/salary-structures/{salaryStructure}', [PayrollController::class, 'salaryStructureShow'])->name('salary-structures.show');
    Route::get('/salary-structures/{salaryStructure}/edit', [PayrollController::class, 'salaryStructureEdit'])->name('salary-structures.edit');
    Route::put('/salary-structures/{salaryStructure}', [PayrollController::class, 'salaryStructureUpdate'])->name('salary-structures.update');
    Route::patch('/salary-structures/{salaryStructure}/toggle-status', [PayrollController::class, 'salaryStructureToggleStatus'])->name('salary-structures.toggle-status');
    Route::delete('/salary-structures/{salaryStructure}', [PayrollController::class, 'salaryStructureDestroy'])->name('salary-structures.destroy');
    // Per-employee payroll line actions (declared before /{payroll} so the
    // literal "items" segment isn't captured as a payroll id).
    Route::put('/items/{item}', [PayrollController::class, 'updateItem'])->name('items.update');
    Route::post('/items/{item}/approve', [PayrollController::class, 'approveItem'])->name('items.approve');
    Route::post('/items/{item}/unapprove', [PayrollController::class, 'unapproveItem'])->name('items.unapprove');
    Route::post('/items/{item}/pay', [PayrollController::class, 'payItem'])->name('items.pay');
    Route::post('/items/{item}/undo-pay', [PayrollController::class, 'undoPayItem'])->name('items.undo-pay');
    Route::get('/items/{item}/payslip', [PayrollController::class, 'payslip'])->name('items.payslip');

    Route::delete('/{payroll}', [PayrollController::class, 'destroy'])->name('destroy');
    Route::get('/{payroll}', [PayrollController::class, 'show'])->name('show');
    Route::post('/{payroll}/approve', [PayrollController::class, 'approve'])->name('approve');
    Route::post('/{payroll}/mark-paid', [PayrollController::class, 'markPaid'])->name('mark-paid');
    Route::post('/{payroll}/cancel', [PayrollController::class, 'cancel'])->name('cancel');
    Route::get('/{payroll}/print', [PayrollController::class, 'print'])->name('print');
});
