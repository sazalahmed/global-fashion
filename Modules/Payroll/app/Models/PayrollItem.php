<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_id', 'employee_id', 'salary_structure_id',
        'basic_salary', 'overtime', 'overtime_hours', 'bonus', 'commission',
        'gross_salary', 'total_earnings',
        'total_deductions', 'net_salary', 'advance_deduction', 'absent_deduction',
        'earnings_breakdown', 'deductions_breakdown',
        'working_days', 'present_days', 'absent_days',
        'status', 'approved_at', 'approved_by',
        'payment_status', 'payment_method',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'overtime' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'bonus' => 'decimal:2',
        'commission' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'advance_deduction' => 'decimal:2',
        'absent_deduction' => 'decimal:2',
        'approved_at' => 'datetime',
        'earnings_breakdown' => 'array',
        'deductions_breakdown' => 'array',
    ];

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\Modules\Employee\Models\Employee::class);
    }

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class);
    }
}
