<?php

namespace Modules\Asset\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetCategory extends Model
{
    protected $fillable = [
        'name', 'useful_life_years', 'depreciation_rate',
        'depreciation_method', 'account_id',
        'depreciation_account_id', 'accumulated_depreciation_account_id',
    ];

    protected $casts = [
        'depreciation_rate' => 'decimal:2',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'account_id');
    }

    public function depreciationAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'depreciation_account_id');
    }

    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'accumulated_depreciation_account_id');
    }
}
