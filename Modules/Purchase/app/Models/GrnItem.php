<?php

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrnItem extends Model
{
    protected $fillable = [
        'goods_receive_note_id',
        'purchase_item_id',
        'product_id',
        'variant_id',
        'quantity_received',
        'quantity_accepted',
        'quantity_rejected',
        'reject_reason',
    ];

    protected $casts = [
        'quantity_received' => 'decimal:4',
        'quantity_accepted' => 'decimal:4',
        'quantity_rejected' => 'decimal:4',
    ];

    public function goodsReceiveNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiveNote::class);
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
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
