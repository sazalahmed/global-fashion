<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Http\Controllers\AttendanceController;
use Modules\Attendance\Http\Controllers\AttendanceConfigController;
use Modules\Attendance\Http\Controllers\LeaveTypeController;

Route::middleware('auth')->prefix('attendance')->name('attendance.')->group(function () {
    // Config (weekends + holidays)
    Route::get('/config', [AttendanceConfigController::class, 'config'])->name('config');
    Route::post('/config/weekends', [AttendanceConfigController::class, 'saveWeekends'])->name('config.weekends');
    Route::post('/config/shift', [AttendanceConfigController::class, 'saveShift'])->name('config.shift');
    Route::post('/config/holidays', [AttendanceConfigController::class, 'storeHoliday'])->name('config.holidays.store');
    Route::put('/config/holidays/{holiday}', [AttendanceConfigController::class, 'updateHoliday'])->name('config.holidays.update');
    Route::delete('/config/holidays/{holiday}', [AttendanceConfigController::class, 'destroyHoliday'])->name('config.holidays.destroy');

    Route::get('/', [AttendanceController::class, 'index'])->name('index');
    Route::get('/create', [AttendanceController::class, 'create'])->name('create');
    Route::post('/', [AttendanceController::class, 'store'])->name('store');
    Route::get('/report', [AttendanceController::class, 'report'])->name('report');
    Route::get('/employee/{employee}', [AttendanceController::class, 'employeeLedger'])->name('employee-ledger');

    // Leave types (management)
    Route::get('/leave-types', [LeaveTypeController::class, 'index'])->name('leave-types.index');
    Route::post('/leave-types', [LeaveTypeController::class, 'store'])->name('leave-types.store');
    Route::put('/leave-types/{leaveType}', [LeaveTypeController::class, 'update'])->name('leave-types.update');
    Route::patch('/leave-types/{leaveType}/toggle-status', [LeaveTypeController::class, 'toggleStatus'])->name('leave-types.toggle-status');
    Route::delete('/leave-types/{leaveType}', [LeaveTypeController::class, 'destroy'])->name('leave-types.destroy');

    // Leave management
    Route::get('/leave', [AttendanceController::class, 'leave'])->name('leave');
    Route::get('/leave/create', [AttendanceController::class, 'leaveCreate'])->name('leave.create');
    Route::get('/leave/balance/{employee}', [AttendanceController::class, 'leaveBalance'])->name('leave.balance');
    Route::post('/leave', [AttendanceController::class, 'leaveStore'])->name('leave.store');
    Route::post('/leave/{leave}/approve', [AttendanceController::class, 'leaveApprove'])->name('leave.approve');
    Route::post('/leave/{leave}/reject', [AttendanceController::class, 'leaveReject'])->name('leave.reject');
});
