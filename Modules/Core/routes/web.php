<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\PushSubscriptionController;

// Core module provides layouts, partials, and shared components.
// Feature-specific routes live in each feature module.

Route::middleware(['auth', 'role:Super Admin'])->prefix('push')->name('push.')->group(function () {
    Route::post('/subscribe', [PushSubscriptionController::class, 'store'])->name('subscribe');
    Route::delete('/unsubscribe', [PushSubscriptionController::class, 'destroy'])->name('unsubscribe');
    Route::post('/test', [PushSubscriptionController::class, 'test'])->name('test');
});
