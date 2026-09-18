<?php

use Illuminate\Support\Facades\Route;
use Modules\Setting\Http\Controllers\EmailSettingController;
use Modules\Setting\Http\Controllers\SettingController;
use Modules\Setting\Http\Controllers\PrinterController;
use Modules\Setting\Http\Controllers\SystemLogController;
use Modules\Setting\Http\Controllers\TaxRateController;

Route::middleware('auth')->prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [SettingController::class, 'index'])->name('index');
    Route::put('/{group}', [SettingController::class, 'update'])->name('update');

    // A section URL (e.g. /admin/settings/general) only exists as PUT for saving.
    // Browsers hitting it with GET previously got a 405 — redirect them to the
    // settings page with the matching tab pre-selected instead.
    Route::get('/{group}', function (string $group) {
        return redirect()
            ->route('settings.index')
            ->with('active_section', SettingController::GROUP_SECTIONS[$group] ?? 'businessSettings');
    })->whereIn('group', [...array_keys(SettingController::GROUP_SECTIONS), 'general'])->name('section.redirect');

    // Email Configuration & Templates
    Route::get('/email', [EmailSettingController::class, 'config'])->name('email');
    Route::post('/email', [EmailSettingController::class, 'saveConfig'])->name('email.save');
    Route::post('/email/test', [EmailSettingController::class, 'testEmail'])->name('email.test');
    Route::get('/email/templates/{template}/edit', [EmailSettingController::class, 'templateEdit'])->name('email.templates.edit');
    Route::put('/email/templates/{template}', [EmailSettingController::class, 'templateUpdate'])->name('email.templates.update');

    // Sidebar Configuration
    Route::get('/sidebar', [SettingController::class, 'sidebarConfig'])->name('sidebar');
    Route::post('/sidebar', [SettingController::class, 'saveSidebarConfig'])->name('sidebar.save');

    // System Maintenance & Cache
    Route::post('/maintenance', [SettingController::class, 'toggleMaintenance'])->name('maintenance');
    Route::post('/clear-cache', [SettingController::class, 'clearCache'])->name('clear-cache');
    Route::post('/manifest/generate', [SettingController::class, 'generateManifest'])->name('manifest.generate');

    // System Logs
    Route::get('/system-logs', [SystemLogController::class, 'index'])->name('system-logs.index');
    Route::get('/system-logs/{filename}', [SystemLogController::class, 'show'])->name('system-logs.show');
    Route::get('/system-logs/{filename}/download', [SystemLogController::class, 'download'])->name('system-logs.download');
    Route::post('/system-logs/{filename}/clear', [SystemLogController::class, 'clear'])->name('system-logs.clear');
    Route::delete('/system-logs/{filename}', [SystemLogController::class, 'delete'])->name('system-logs.delete');

    // Printers
    Route::get('/printers', [PrinterController::class, 'index'])->name('printers.index');
    Route::get('/printers/create', [PrinterController::class, 'create'])->name('printers.create');
    Route::post('/printers', [PrinterController::class, 'store'])->name('printers.store');
    Route::get('/printers/{printer}/edit', [PrinterController::class, 'edit'])->name('printers.edit');
    Route::put('/printers/{printer}', [PrinterController::class, 'update'])->name('printers.update');
    Route::patch('/printers/{printer}/toggle-status', [PrinterController::class, 'toggleStatus'])->name('printers.toggle-status');
    Route::delete('/printers/{printer}', [PrinterController::class, 'destroy'])->name('printers.destroy');
    Route::post('/printers/{printer}/test', [PrinterController::class, 'testConnection'])->name('printers.test');

    // Tax Rates CRUD
    Route::post('/tax-rates', [TaxRateController::class, 'store'])->name('tax-rates.store');
    Route::put('/tax-rates/{tax_rate}', [TaxRateController::class, 'update'])->name('tax-rates.update');
    Route::delete('/tax-rates/{tax_rate}', [TaxRateController::class, 'destroy'])->name('tax-rates.destroy');

    // Courier connection test
    Route::post('/courier/{slug}/test', [SettingController::class, 'testCourierConnection'])
        ->whereIn('slug', ['steadfast', 'pathao'])
        ->name('courier.test');

    // SMS gateway connection test
    Route::post('/sms/test', [SettingController::class, 'testSmsConnection'])->name('sms.test');
    Route::post('/sms/send-test', [SettingController::class, 'sendTestSms'])->name('sms.send-test');

    // Webhooks
    Route::post('/webhooks', [SettingController::class, 'webhookStore'])->name('webhooks.store');
    Route::post('/webhooks/{webhook}/toggle', [SettingController::class, 'webhookToggle'])->name('webhooks.toggle');
    Route::post('/webhooks/{webhook}/test', [SettingController::class, 'webhookTest'])->name('webhooks.test');
    Route::delete('/webhooks/{webhook}', [SettingController::class, 'webhookDestroy'])->name('webhooks.destroy');
});
