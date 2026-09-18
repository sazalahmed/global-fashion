<?php

use Illuminate\Support\Facades\Route;
use Modules\AdSpend\Http\Controllers\AdSpendController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('adspends', AdSpendController::class)->names('adspend');
});
