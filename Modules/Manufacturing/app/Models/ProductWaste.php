<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductWaste extends Model
{
    protected $table = 'product_wastes';

    const TYPE_QUALITY_REJECTION = 'quality_rejection';
    const TYPE_MANUFACTURING_DEFECT = 'manufacturing_defect';
    const TYPE_UNRECOVERABLE = 'unrecoverable';
    const TYPE_OTHER = 'other';

    protected $fillable = [
        'production_order_id', 'lot_id', 'catalog_id', 'color_id', 'size_id',
        'product_id', 'variant_id', 'quantity_wasted', 'unit_cost', 'total_cost',
        'waste_type', 'is_normal', 'waste_percentage', 'notes',
        'journal_entry_id', 'created_by', 'waste_date',
    ];

    protected $casts = [
        'quantity_wasted' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'is_normal' => 'boolean',
        'waste_percentage' => 'decimal:2',
        'waste_date' => 'date',
    ];

    public static function getWasteTypes(): array
    {
        return [
            self::TYPE_QUALITY_REJECTION => 'Quality Rejection',
            self::TYPE_MANUFACTURING_DEFECT => 'Manufacturing Defect',
            self::TYPE_UNRECOVERABLE => 'Unrecoverable',
            self::TYPE_OTHER => 'Other',
        ];
    }

    public function productionOrder(): BelongsTo { return $this->belongsTo(ProductionOrder::class); }
    public function lot(): BelongsTo { return $this->belongsTo(ProductionLot::class, 'lot_id'); }
    public function catalog(): BelongsTo { return $this->belongsTo(Catalog::class); }
    public function color(): BelongsTo { return $this->belongsTo(MfgColor::class, 'color_id'); }
    public function size(): BelongsTo { return $this->belongsTo(MfgSize::class, 'size_id'); }
    public function product(): BelongsTo { return $this->belongsTo(\Modules\Product\Models\Product::class); }
    public function variant(): BelongsTo { return $this->belongsTo(\Modules\Variant\Models\ProductVariant::class, 'variant_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'created_by'); }
}
