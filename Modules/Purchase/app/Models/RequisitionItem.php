<?php

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitionItem extends Model
{
    protected $fillable = [
        'requisition_id', 'product_id', 'variant_id', 'quantity', 'note',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\Modules\Product\Models\Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(\Modules\Variant\Models\ProductVariant::class, 'variant_id');
    }
}
