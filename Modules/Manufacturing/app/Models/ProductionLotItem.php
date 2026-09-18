<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionLotItem extends Model
{
    protected $table = 'production_lot_items';

    protected $fillable = [
        'lot_id', 'production_order_item_id', 'catalog_id', 'color_id', 'size_id',
        'product_id', 'variant_id', 'quantity_received', 'quantity_damaged', 'quantity_good', 'unit_cost',
    ];

    protected $casts = [
        'quantity_received' => 'integer',
        'quantity_damaged' => 'integer',
        'quantity_good' => 'integer',
        'unit_cost' => 'decimal:2',
    ];

    public function lot(): BelongsTo { return $this->belongsTo(ProductionLot::class, 'lot_id'); }
    public function productionOrderItem(): BelongsTo { return $this->belongsTo(ProductionOrderItem::class); }
    public function catalog(): BelongsTo { return $this->belongsTo(Catalog::class); }
    public function color(): BelongsTo { return $this->belongsTo(MfgColor::class, 'color_id'); }
    public function size(): BelongsTo { return $this->belongsTo(MfgSize::class, 'size_id'); }
    public function product(): BelongsTo { return $this->belongsTo(\Modules\Product\Models\Product::class); }
    public function variant(): BelongsTo { return $this->belongsTo(\Modules\Variant\Models\ProductVariant::class, 'variant_id'); }
}
