<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderMaterial extends Model
{
    protected $table = 'production_order_materials';

    protected $fillable = [
        'production_order_id', 'raw_material_id', 'planned_quantity',
        'expected_return_quantity', 'actual_issued_quantity', 'actual_returned_quantity',
        'unit_cost', 'estimated_consumption', 'notes',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:4',
        'expected_return_quantity' => 'decimal:4',
        'actual_issued_quantity' => 'decimal:4',
        'actual_returned_quantity' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'estimated_consumption' => 'decimal:4',
    ];

    public function getRemainingToIssueAttribute(): float
    {
        return (float) $this->planned_quantity - (float) $this->actual_issued_quantity;
    }

    public function getRemainingToReturnAttribute(): float
    {
        return (float) $this->expected_return_quantity - (float) $this->actual_returned_quantity;
    }

    public function productionOrder(): BelongsTo { return $this->belongsTo(ProductionOrder::class); }
    public function rawMaterial(): BelongsTo { return $this->belongsTo(RawMaterial::class); }
}
