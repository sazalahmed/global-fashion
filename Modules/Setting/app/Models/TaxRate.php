<?php

namespace Modules\Setting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxRate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'rate',
        'type',
        'apply_to',
        'description',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'rate'       => 'decimal:4',
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
