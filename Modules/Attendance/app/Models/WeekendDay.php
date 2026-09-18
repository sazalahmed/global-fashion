<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Branch\Models\Branch;

class WeekendDay extends Model
{
    protected $fillable = ['name', 'day_of_week', 'is_weekend', 'branch_id'];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_weekend'  => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
