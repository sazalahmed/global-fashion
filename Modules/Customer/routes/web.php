<?php

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Controllers\AreaController;
use Modules\Customer\Http\Controllers\CustomerController;
use Modules\Customer\Http\Controllers\CustomerGroupController;

Route::middleware('auth')->prefix('customer-groups')->name('customer-groups.')->group(function () {
    Route::get('/', [CustomerGroupController::class, 'index'])->name('index');
    Route::post('/', [CustomerGroupController::class, 'store'])->name('store');
    Route::put('/{group}', [CustomerGroupController::class, 'update'])->name('update');
    Route::patch('/{group}/toggle-status', [CustomerGroupController::class, 'toggleStatus'])->name('toggle-status');
    Route::delete('/{group}', [CustomerGroupController::class, 'destroy'])->name('destroy');
});

Route::middleware('auth')->prefix('customer-areas')->name('customer-areas.')->group(function () {
    Route::get('/', [AreaController::class, 'index'])->name('index');
    Route::post('/', [AreaController::class, 'store'])->name('store');
    Route::put('/{area}', [AreaController::class, 'update'])->name('update');
    Route::patch('/{area}/toggle-status', [AreaController::class, 'toggleStatus'])->name('toggle-status');
    Route::delete('/{area}', [AreaController::class, 'destroy'])->name('destroy');
    Route::get('/{area}/children', [AreaController::class, 'children'])->name('children');
});

Route::middleware('auth')->prefix('customers')->name('customers.')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('index');
    Route::get('/search', [CustomerController::class, 'search'])->name('search');
    Route::get('/thanas/{district}', [CustomerController::class, 'thanasByDistrict'])->name('thanas');
    Route::get('/create', [CustomerController::class, 'create'])->name('create');
    Route::post('/', [CustomerController::class, 'store'])->name('store');
    Route::post('/quick-store', [CustomerController::class, 'quickStore'])->name('quick-store');
    Route::get('/ledger', [CustomerController::class, 'ledger'])->name('ledger');
    Route::get('/due-receive', [CustomerController::class, 'dueReceive'])->name('due-receive');
    Route::get('/advances', [CustomerController::class, 'advances'])->name('advances');
    Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
    Route::get('/{customer}/tab/{tab}', [CustomerController::class, 'tab'])->name('tab');
    Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('edit');
    Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
    Route::patch('/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('toggle-status');
    Route::post('/{customer}/offset-due', [CustomerController::class, 'offsetDue'])->name('offset-due');
    Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');
});
