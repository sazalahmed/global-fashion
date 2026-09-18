<?php

use Illuminate\Support\Facades\Route;
use Modules\AiAssistant\Http\Controllers\AiAssistantController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('aiassistants', AiAssistantController::class)->names('aiassistant');
});
