<?php

use Illuminate\Support\Facades\Route;
use Modules\POS\Http\Controllers\POSController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('pos', POSController::class)->names('pos');
});
