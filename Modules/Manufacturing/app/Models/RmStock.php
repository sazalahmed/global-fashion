<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RmStock extends Model
{
    protected $table = 'rm_stocks';

    protected $fillable = [
        'raw_material_id',
        'quantity',
        'avg_cost',
        'last_cost',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'avg_cost' => 'decimal:2',
        'last_cost' => 'decimal:2',
    ];

    // Relationships
    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }

    // Scopes
    public function scopeByMaterial(Builder $query, int $rawMaterialId): Builder
    {
        return $query->where('raw_material_id', $rawMaterialId);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereHas('rawMaterial', function ($q) {
            $q->whereColumn('rm_stocks.quantity', '<=', 'raw_materials.reorder_level');
        });
    }
}
