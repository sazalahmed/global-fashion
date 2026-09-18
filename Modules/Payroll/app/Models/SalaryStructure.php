<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryStructure extends Model
{
    protected $fillable = ['name', 'code', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function components(): HasMany
    {
        return $this->hasMany(SalaryStructureComponent::class)->orderBy('sort_order');
    }

    public function scopeActive($query) { return $query->where('is_active', true); }
}
