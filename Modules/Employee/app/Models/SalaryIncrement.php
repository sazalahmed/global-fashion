<?php

namespace Modules\Employee\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryIncrement extends Model
{
    protected $fillable = [
        'employee_id', 'previous_salary', 'new_salary',
        'increment_type', 'increment_value', 'note',
        'incremented_by', 'applied_at',
    ];

    protected $casts = [
        'previous_salary' => 'decimal:2',
        'new_salary'      => 'decimal:2',
        'increment_value' => 'decimal:3',
        'applied_at'      => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function incrementedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'incremented_by');
    }
}
