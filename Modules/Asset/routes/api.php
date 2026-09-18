<?php

use Illuminate\Support\Facades\Route;
use Modules\Asset\Http\Controllers\AssetController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('assets', AssetController::class)->names('asset');
});
