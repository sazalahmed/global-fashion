<?php

use Illuminate\Support\Facades\Route;
use Modules\SaleReturn\Http\Controllers\SaleReturnController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('salereturns', SaleReturnController::class)->names('salereturn');
});
