<?php

use Illuminate\Support\Facades\Route;
use Modules\Security\Http\Controllers\SecurityController;
use Modules\Security\Http\Controllers\TwoFactorController;
use Modules\Security\Http\Controllers\UserController;

Route::middleware('auth')->prefix('security')->name('security.')->group(function () {
    // Dashboard
    Route::get('/', [SecurityController::class, 'index'])->name('index');

    // Roles
    Route::get('/roles', [SecurityController::class, 'roles'])->name('roles');
    Route::get('/roles/create', [SecurityController::class, 'roleCreate'])->name('roles.create');
    Route::post('/roles', [SecurityController::class, 'roleStore'])->name('roles.store');
    Route::get('/roles/{role}/edit', [SecurityController::class, 'roleEdit'])->name('roles.edit');
    Route::put('/roles/{role}', [SecurityController::class, 'roleUpdate'])->name('roles.update');
    Route::delete('/roles/{role}', [SecurityController::class, 'roleDestroy'])->name('roles.destroy');

    // Users
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::patch('/users/{user}/reset-two-factor', [UserController::class, 'resetTwoFactor'])->name('users.reset-two-factor');

    // Self-service profile + password (work for any user, including the super admin)
    Route::get('/profile', [UserController::class, 'editProfile'])->name('profile');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::get('/change-password', [UserController::class, 'changePassword'])->name('change-password');
    Route::put('/change-password', [UserController::class, 'updatePassword'])->name('change-password.update');

    // Self-service Two-Factor (Google Authenticator) — acts on the current user only.
    Route::get('/two-factor',           [TwoFactorController::class, 'show'])->name('two-factor');
    Route::post('/two-factor/enable',   [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::post('/two-factor/confirm',  [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
    Route::delete('/two-factor',        [TwoFactorController::class, 'disable'])->name('two-factor.disable');

    // Backup
    Route::get('/backup', [SecurityController::class, 'backup'])->name('backup');
    Route::post('/backup', [SecurityController::class, 'backupCreate'])->name('backup.create');
    Route::post('/backup/restore', [SecurityController::class, 'backupRestore'])->name('backup.restore');
    Route::delete('/backup', [SecurityController::class, 'backupDelete'])->name('backup.delete');

    // API Keys
    Route::get('/api-keys', [SecurityController::class, 'apiKeys'])->name('api-keys');
    Route::get('/api-keys/create', [SecurityController::class, 'apiKeyCreate'])->name('api-keys.create');
    Route::post('/api-keys', [SecurityController::class, 'apiKeyStore'])->name('api-keys.store');
    Route::delete('/api-keys/{key}', [SecurityController::class, 'apiKeyDestroy'])->name('api-keys.destroy');
});
