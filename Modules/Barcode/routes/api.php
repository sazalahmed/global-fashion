<?php

use Illuminate\Support\Facades\Route;
use Modules\Barcode\Http\Controllers\BarcodeController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('barcodes', BarcodeController::class)->names('barcode');
});
