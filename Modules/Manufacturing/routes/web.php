<?php

use Illuminate\Support\Facades\Route;
use Modules\Manufacturing\Http\Controllers\FactoryController;
use Modules\Manufacturing\Http\Controllers\CatalogController;
use Modules\Manufacturing\Http\Controllers\MfgColorController;
use Modules\Manufacturing\Http\Controllers\MfgSizeController;
use Modules\Manufacturing\Http\Controllers\RawMaterialController;
use Modules\Manufacturing\Http\Controllers\RawMaterialSupplierController;
use Modules\Manufacturing\Http\Controllers\RmPurchaseController;
use Modules\Manufacturing\Http\Controllers\RmReceiveController;
use Modules\Manufacturing\Http\Controllers\ProductionOrderController;
use Modules\Manufacturing\Http\Controllers\FabricIssuanceController;
use Modules\Manufacturing\Http\Controllers\FabricReturnController;
use Modules\Manufacturing\Http\Controllers\ProductionLotController;
use Modules\Manufacturing\Http\Controllers\ProductionDamageController;
use Modules\Manufacturing\Http\Controllers\RmWasteController;
use Modules\Manufacturing\Http\Controllers\ProductWasteController;
use Modules\Manufacturing\Http\Controllers\RmSupplierPaymentController;
use Modules\Manufacturing\Http\Controllers\FactoryPaymentController;
use Modules\Manufacturing\Http\Controllers\ManufacturingDashboardController;
use Modules\Manufacturing\Http\Controllers\ManufacturingReportController;

Route::middleware('auth')->prefix('manufacturing')->name('manufacturing.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [ManufacturingDashboardController::class, 'index'])->name('dashboard');

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/rm-stock', [ManufacturingReportController::class, 'rmStock'])->name('rm-stock');
        Route::get('/production', [ManufacturingReportController::class, 'production'])->name('production');
        Route::get('/damage', [ManufacturingReportController::class, 'damage'])->name('damage');
        Route::get('/waste', [ManufacturingReportController::class, 'waste'])->name('waste');
        Route::get('/cost-analysis', [ManufacturingReportController::class, 'costAnalysis'])->name('cost-analysis');
    });

    // Factories
    Route::prefix('factories')->name('factories.')->group(function () {
        Route::get('/', [FactoryController::class, 'index'])->name('index');
        Route::get('/create', [FactoryController::class, 'create'])->name('create');
        Route::post('/', [FactoryController::class, 'store'])->name('store');
        Route::get('/{factory}', [FactoryController::class, 'show'])->name('show');
        Route::get('/{factory}/edit', [FactoryController::class, 'edit'])->name('edit');
        Route::put('/{factory}', [FactoryController::class, 'update'])->name('update');
        Route::patch('/{factory}/toggle-status', [FactoryController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{factory}', [FactoryController::class, 'destroy'])->name('destroy');

        // Factory Payments
        Route::get('/{factory}/payments', [FactoryPaymentController::class, 'index'])->name('payments.index');
        Route::post('/{factory}/payments', [FactoryPaymentController::class, 'store'])->name('payments.store');
    });

    // Catalogs
    Route::prefix('catalogs')->name('catalogs.')->group(function () {
        Route::get('/', [CatalogController::class, 'index'])->name('index');
        Route::get('/create', [CatalogController::class, 'create'])->name('create');
        Route::post('/', [CatalogController::class, 'store'])->name('store');
        Route::get('/{catalog}/edit', [CatalogController::class, 'edit'])->name('edit');
        Route::put('/{catalog}', [CatalogController::class, 'update'])->name('update');
        Route::patch('/{catalog}/toggle-status', [CatalogController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{catalog}', [CatalogController::class, 'destroy'])->name('destroy');
    });

    // Colors (inline AJAX)
    Route::prefix('colors')->name('colors.')->group(function () {
        Route::get('/', [MfgColorController::class, 'index'])->name('index');
        Route::post('/', [MfgColorController::class, 'store'])->name('store');
        Route::put('/{color}', [MfgColorController::class, 'update'])->name('update');
        Route::patch('/{color}/toggle-status', [MfgColorController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{color}', [MfgColorController::class, 'destroy'])->name('destroy');
    });

    // Sizes (inline AJAX)
    Route::prefix('sizes')->name('sizes.')->group(function () {
        Route::get('/', [MfgSizeController::class, 'index'])->name('index');
        Route::post('/', [MfgSizeController::class, 'store'])->name('store');
        Route::put('/{size}', [MfgSizeController::class, 'update'])->name('update');
        Route::patch('/{size}/toggle-status', [MfgSizeController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{size}', [MfgSizeController::class, 'destroy'])->name('destroy');
    });

    // Raw Materials
    Route::prefix('raw-materials')->name('raw-materials.')->group(function () {
        Route::get('/', [RawMaterialController::class, 'index'])->name('index');
        Route::get('/create', [RawMaterialController::class, 'create'])->name('create');
        Route::post('/', [RawMaterialController::class, 'store'])->name('store');
        Route::get('/{rawMaterial}', [RawMaterialController::class, 'show'])->name('show');
        Route::get('/{rawMaterial}/edit', [RawMaterialController::class, 'edit'])->name('edit');
        Route::put('/{rawMaterial}', [RawMaterialController::class, 'update'])->name('update');
        Route::patch('/{rawMaterial}/toggle-status', [RawMaterialController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{rawMaterial}', [RawMaterialController::class, 'destroy'])->name('destroy');
    });

    // Raw Material Suppliers
    Route::prefix('raw-material-suppliers')->name('suppliers.')->group(function () {
        Route::get('/', [RawMaterialSupplierController::class, 'index'])->name('index');
        Route::get('/create', [RawMaterialSupplierController::class, 'create'])->name('create');
        Route::post('/', [RawMaterialSupplierController::class, 'store'])->name('store');
        Route::get('/{supplier}', [RawMaterialSupplierController::class, 'show'])->name('show');
        Route::get('/{supplier}/edit', [RawMaterialSupplierController::class, 'edit'])->name('edit');
        Route::put('/{supplier}', [RawMaterialSupplierController::class, 'update'])->name('update');
        Route::patch('/{supplier}/toggle-status', [RawMaterialSupplierController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{supplier}', [RawMaterialSupplierController::class, 'destroy'])->name('destroy');

        // RM Supplier Payments
        Route::get('/{supplier}/payments', [RmSupplierPaymentController::class, 'index'])->name('payments.index');
        Route::post('/{supplier}/payments', [RmSupplierPaymentController::class, 'store'])->name('payments.store');
    });

    // RM Purchase Orders
    Route::prefix('rm-purchases')->name('rm-purchases.')->group(function () {
        Route::get('/', [RmPurchaseController::class, 'index'])->name('index');
        Route::get('/create', [RmPurchaseController::class, 'create'])->name('create');
        Route::post('/', [RmPurchaseController::class, 'store'])->name('store');
        Route::get('/{rmPurchase}', [RmPurchaseController::class, 'show'])->name('show');
        Route::get('/{rmPurchase}/edit', [RmPurchaseController::class, 'edit'])->name('edit');
        Route::put('/{rmPurchase}', [RmPurchaseController::class, 'update'])->name('update');
        Route::delete('/{rmPurchase}', [RmPurchaseController::class, 'destroy'])->name('destroy');
        Route::post('/{rmPurchase}/approve', [RmPurchaseController::class, 'approve'])->name('approve');
        Route::post('/{rmPurchase}/cancel', [RmPurchaseController::class, 'cancel'])->name('cancel');
        Route::get('/{rmPurchase}/receive', [RmReceiveController::class, 'create'])->name('receive.create');
        Route::post('/{rmPurchase}/receive', [RmReceiveController::class, 'store'])->name('receive.store');
    });

    // Production Orders
    Route::prefix('production-orders')->name('production-orders.')->group(function () {
        Route::get('/', [ProductionOrderController::class, 'index'])->name('index');
        Route::get('/create', [ProductionOrderController::class, 'create'])->name('create');
        Route::post('/', [ProductionOrderController::class, 'store'])->name('store');
        Route::get('/{productionOrder}', [ProductionOrderController::class, 'show'])->name('show');
        Route::get('/{productionOrder}/edit', [ProductionOrderController::class, 'edit'])->name('edit');
        Route::put('/{productionOrder}', [ProductionOrderController::class, 'update'])->name('update');
        Route::delete('/{productionOrder}', [ProductionOrderController::class, 'destroy'])->name('destroy');
        Route::post('/{productionOrder}/approve', [ProductionOrderController::class, 'approve'])->name('approve');
        Route::post('/{productionOrder}/cancel', [ProductionOrderController::class, 'cancel'])->name('cancel');
        Route::post('/{productionOrder}/complete', [ProductionOrderController::class, 'complete'])->name('complete');

        // Fabric Issuance
        Route::get('/{productionOrder}/issue-fabric', [FabricIssuanceController::class, 'create'])->name('issue-fabric.create');
        Route::post('/{productionOrder}/issue-fabric', [FabricIssuanceController::class, 'store'])->name('issue-fabric.store');

        // Fabric Return
        Route::get('/{productionOrder}/return-fabric', [FabricReturnController::class, 'create'])->name('return-fabric.create');
        Route::post('/{productionOrder}/return-fabric', [FabricReturnController::class, 'store'])->name('return-fabric.store');

        // Production Lots
        Route::get('/{productionOrder}/lots/create', [ProductionLotController::class, 'create'])->name('lots.create');
        Route::post('/{productionOrder}/lots', [ProductionLotController::class, 'store'])->name('lots.store');
        Route::get('/{productionOrder}/lots/{lot}', [ProductionLotController::class, 'show'])->name('lots.show');
    });

    // Damages
    Route::prefix('damages')->name('damages.')->group(function () {
        Route::get('/', [ProductionDamageController::class, 'index'])->name('index');
        Route::post('/', [ProductionDamageController::class, 'store'])->name('store');
        Route::post('/{damage}/compensations', [ProductionDamageController::class, 'recordCompensation'])->name('compensations.store');
    });

    // Wastes
    Route::prefix('wastes')->name('wastes.')->group(function () {
        Route::get('/raw-materials', [RmWasteController::class, 'index'])->name('rm.index');
        Route::post('/raw-materials', [RmWasteController::class, 'store'])->name('rm.store');
        Route::get('/products', [ProductWasteController::class, 'index'])->name('products.index');
        Route::post('/products', [ProductWasteController::class, 'store'])->name('products.store');
    });
});
