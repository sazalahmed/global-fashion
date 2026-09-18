<?php

use Illuminate\Support\Facades\Route;
use Modules\Variant\Http\Controllers\VariantController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('variants', VariantController::class)->names('variant');
});
