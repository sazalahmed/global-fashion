<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    use SoftDeletes, LogsActivity;

    protected string $activityLogName = 'payrolls';

    protected $fillable = [
        'payroll_number', 'month', 'branch_id', 'total_employees',
        'total_gross', 'total_deductions', 'total_net', 'status',
        'journal_entry_id', 'approved_by', 'created_by',
    ];

    protected $casts = [
        'total_gross' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\Modules\Branch\Models\Branch::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\JournalEntry::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function scopeDraft($query) { return $query->where('status', 'draft'); }
    public function scopeApproved($query) { return $query->where('status', 'approved'); }
}
