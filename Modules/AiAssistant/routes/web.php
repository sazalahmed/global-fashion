<?php

use Illuminate\Support\Facades\Route;
use Modules\AiAssistant\Http\Controllers\Admin\AiSettingsController;

/*
| AiAssistant admin routes.
| Mounted under /admin via RouteServiceProvider::mapWebRoutes() (which
| applies the `web` middleware and prefixes admin/ where applicable).
*/

Route::middleware(['auth', 'verified'])
    ->prefix('ai-assistant')
    ->name('admin.ai-assistant.')
    ->group(function () {
        Route::get('/', [AiSettingsController::class, 'index'])->name('settings');
        Route::put('/', [AiSettingsController::class, 'update'])->name('settings.update');
        Route::post('/test', [AiSettingsController::class, 'test'])->name('settings.test');
    });
