<?php

use Illuminate\Support\Facades\Route;
use Modules\PurchaseReturn\Http\Controllers\PurchaseReturnController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('purchasereturns', PurchaseReturnController::class)->names('purchasereturn');
});
