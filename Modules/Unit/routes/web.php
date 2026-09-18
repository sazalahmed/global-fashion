<?php

use Illuminate\Support\Facades\Route;
use Modules\Unit\Http\Controllers\UnitController;

Route::middleware('auth')->prefix('units')->name('units.')->group(function () {
    Route::get('/', [UnitController::class, 'index'])->name('index');
    Route::get('/create', [UnitController::class, 'create'])->name('create');
    Route::post('/', [UnitController::class, 'store'])->name('store');
    Route::post('/quick-store', [UnitController::class, 'quickStore'])->name('quick-store');
    Route::get('/{unit}/edit', [UnitController::class, 'edit'])->name('edit');
    Route::put('/{unit}', [UnitController::class, 'update'])->name('update');
    Route::patch('/{unit}/toggle-status', [UnitController::class, 'toggleStatus'])->name('toggle-status');
    Route::delete('/{unit}', [UnitController::class, 'destroy'])->name('destroy');
});
