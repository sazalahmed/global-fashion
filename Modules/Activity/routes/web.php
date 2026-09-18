<?php

use Illuminate\Support\Facades\Route;
use Modules\Activity\Http\Controllers\ActivityController;

Route::middleware('auth')->prefix('activity')->name('activities.')->group(function () {
    Route::get('/', [ActivityController::class, 'index'])->name('index');
    Route::post('/clear', [ActivityController::class, 'clear'])->name('clear');
    Route::get('/{activity}', [ActivityController::class, 'show'])->name('show');
});
