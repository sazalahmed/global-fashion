<?php

use Illuminate\Support\Facades\Route;
use Modules\Unit\Http\Controllers\UnitController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('units', UnitController::class)->names('unit');
});
