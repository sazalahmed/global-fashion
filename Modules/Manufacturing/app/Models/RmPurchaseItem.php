<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RmPurchaseItem extends Model
{
    protected $table = 'rm_purchase_items';

    protected $fillable = [
        'purchase_order_id',
        'raw_material_id',
        'quantity',
        'unit_price',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'line_total',
        'received_quantity',
        'attachment_path',
        'attachment_name',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'received_quantity' => 'decimal:4',
    ];

    // Relationships
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(RmPurchaseOrder::class, 'purchase_order_id');
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }

    // Accessors
    public function getRemainingQuantityAttribute(): float
    {
        return (float) $this->quantity - (float) $this->received_quantity;
    }
}
