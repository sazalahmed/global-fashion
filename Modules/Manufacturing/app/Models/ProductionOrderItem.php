<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrderItem extends Model
{
    protected $table = 'production_order_items';

    protected $fillable = [
        'production_order_id', 'catalog_id', 'color_id', 'size_id',
        'product_id', 'variant_id', 'quantity', 'received_quantity',
        'damaged_quantity', 'making_cost_per_unit', 'current_cost_per_unit',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'received_quantity' => 'integer',
        'damaged_quantity' => 'integer',
        'making_cost_per_unit' => 'decimal:2',
        'current_cost_per_unit' => 'decimal:2',
    ];

    public function getRemainingQuantityAttribute(): int
    {
        return $this->quantity - $this->received_quantity;
    }

    public function productionOrder(): BelongsTo { return $this->belongsTo(ProductionOrder::class); }
    public function catalog(): BelongsTo { return $this->belongsTo(Catalog::class); }
    public function color(): BelongsTo { return $this->belongsTo(MfgColor::class, 'color_id'); }
    public function size(): BelongsTo { return $this->belongsTo(MfgSize::class, 'size_id'); }
    public function product(): BelongsTo { return $this->belongsTo(\Modules\Product\Models\Product::class); }
    public function variant(): BelongsTo { return $this->belongsTo(\Modules\Variant\Models\ProductVariant::class, 'variant_id'); }
    public function lotItems(): HasMany { return $this->hasMany(ProductionLotItem::class); }
}
