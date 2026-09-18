<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = [
        'name', 'code', 'max_days', 'paid', 'requires_approval', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_days'          => 'integer',
            'paid'              => 'boolean',
            'requires_approval' => 'boolean',
            'is_active'         => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
