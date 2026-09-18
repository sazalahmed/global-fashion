<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RmWaste extends Model
{
    protected $table = 'rm_wastes';

    const TYPE_CUTTING = 'cutting';
    const TYPE_DEFECTIVE = 'defective_material';
    const TYPE_SPILLAGE = 'spillage';
    const TYPE_SHRINKAGE = 'shrinkage';
    const TYPE_OTHER = 'other';

    protected $fillable = [
        'production_order_id', 'lot_id', 'raw_material_id',
        'quantity_wasted', 'unit_cost', 'total_cost', 'waste_type',
        'is_normal', 'waste_percentage', 'notes', 'journal_entry_id',
        'created_by', 'waste_date',
    ];

    protected $casts = [
        'quantity_wasted' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'is_normal' => 'boolean',
        'waste_percentage' => 'decimal:2',
        'waste_date' => 'date',
    ];

    public static function getWasteTypes(): array
    {
        return [
            self::TYPE_CUTTING => 'Cutting Waste',
            self::TYPE_DEFECTIVE => 'Defective Material',
            self::TYPE_SPILLAGE => 'Spillage',
            self::TYPE_SHRINKAGE => 'Shrinkage',
            self::TYPE_OTHER => 'Other',
        ];
    }

    public function productionOrder(): BelongsTo { return $this->belongsTo(ProductionOrder::class); }
    public function lot(): BelongsTo { return $this->belongsTo(ProductionLot::class, 'lot_id'); }
    public function rawMaterial(): BelongsTo { return $this->belongsTo(RawMaterial::class); }
    public function creator(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'created_by'); }
}
