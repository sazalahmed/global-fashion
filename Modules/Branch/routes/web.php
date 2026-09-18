<?php

use Illuminate\Support\Facades\Route;
use Modules\Branch\Http\Controllers\BranchController;

Route::middleware('auth')->prefix('branches')->name('branches.')->group(function () {
    Route::get('/', [BranchController::class, 'index'])->name('index');
    Route::get('/create', [BranchController::class, 'create'])->name('create');
    Route::post('/', [BranchController::class, 'store'])->name('store');
    Route::get('/{branch}', [BranchController::class, 'show'])->name('show');
    Route::get('/{branch}/edit', [BranchController::class, 'edit'])->name('edit');
    Route::put('/{branch}', [BranchController::class, 'update'])->name('update');
    Route::patch('/{branch}/toggle-status', [BranchController::class, 'toggleStatus'])->name('toggle-status');
    Route::delete('/{branch}', [BranchController::class, 'destroy'])->name('destroy');
});
