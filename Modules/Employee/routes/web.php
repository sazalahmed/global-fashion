<?php

use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\DepartmentController;
use Modules\Employee\Http\Controllers\DesignationController;
use Modules\Employee\Http\Controllers\EmployeeController;

Route::middleware('auth')->group(function () {
    Route::prefix('departments')->name('departments.')->group(function () {
        Route::get('/', [DepartmentController::class, 'index'])->name('index');
        Route::post('/', [DepartmentController::class, 'store'])->name('store');
        Route::put('/{department}', [DepartmentController::class, 'update'])->name('update');
        Route::patch('/{department}/toggle-status', [DepartmentController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{department}', [DepartmentController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('designations')->name('designations.')->group(function () {
        Route::get('/', [DesignationController::class, 'index'])->name('index');
        Route::post('/', [DesignationController::class, 'store'])->name('store');
        Route::put('/{designation}', [DesignationController::class, 'update'])->name('update');
        Route::patch('/{designation}/toggle-status', [DesignationController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{designation}', [DesignationController::class, 'destroy'])->name('destroy');
    });
});

Route::middleware('auth')->prefix('employees')->name('employee.')->group(function () {
    Route::get('/', [EmployeeController::class, 'index'])->name('index');
    Route::get('/create', [EmployeeController::class, 'create'])->name('create');
    Route::post('/', [EmployeeController::class, 'store'])->name('store');

    // Salary increments (declared before the /{employee} catch so the literal
    // "salary-increments" segment isn't captured as an employee id).
    Route::post('/{employee}/salary-increments', [EmployeeController::class, 'salaryIncrementStore'])->name('salary-increment.store');
    Route::put('/salary-increments/{increment}', [EmployeeController::class, 'salaryIncrementUpdate'])->name('salary-increment.update');
    Route::delete('/salary-increments/{increment}', [EmployeeController::class, 'salaryIncrementDestroy'])->name('salary-increment.destroy');

    // Employee advances (money given ahead of salary, recovered later)
    Route::get('/{employee}/advance-ledger', [EmployeeController::class, 'advanceLedger'])->name('advance-ledger');
    Route::get('/{employee}/full-ledger', [EmployeeController::class, 'fullLedger'])->name('full-ledger');
    Route::post('/{employee}/advance', [EmployeeController::class, 'giveAdvance'])->name('advance.store');
    Route::post('/{employee}/advance-recovery', [EmployeeController::class, 'recordRecovery'])->name('advance-recovery.store');

    Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
    Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
    Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
    Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
});
