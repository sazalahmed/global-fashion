<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id', 'branch_id', 'attendance_date',
        'check_in', 'check_out', 'hours_worked',
        'status', 'is_overtime', 'overtime_hours', 'late_minutes', 'note',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'hours_worked' => 'decimal:2',
        'is_overtime' => 'boolean',
        'overtime_hours' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\Modules\Employee\Models\Employee::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\Modules\Branch\Models\Branch::class);
    }

    public function scopeByMonth($query, string $month)
    {
        return $query->where('attendance_date', 'like', $month . '%');
    }

    public function scopeByEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }
}
