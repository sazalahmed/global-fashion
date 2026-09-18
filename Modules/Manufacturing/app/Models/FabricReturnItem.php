<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FabricReturnItem extends Model
{
    protected $table = 'fabric_return_items';

    const CONDITION_GOOD = 'good';
    const CONDITION_DAMAGED = 'damaged';
    const CONDITION_PARTIAL = 'partial_usable';

    protected $fillable = [
        'return_id', 'raw_material_id', 'bom_item_id',
        'quantity_returned', 'unit_cost', 'line_total', 'condition', 'notes',
    ];

    protected $casts = [
        'quantity_returned' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public static function getConditions(): array
    {
        return [
            self::CONDITION_GOOD => 'Good',
            self::CONDITION_DAMAGED => 'Damaged',
            self::CONDITION_PARTIAL => 'Partially Usable',
        ];
    }

    public function fabricReturn(): BelongsTo { return $this->belongsTo(FabricReturn::class, 'return_id'); }
    public function rawMaterial(): BelongsTo { return $this->belongsTo(RawMaterial::class); }
    public function bomItem(): BelongsTo { return $this->belongsTo(ProductionOrderMaterial::class, 'bom_item_id'); }
}
