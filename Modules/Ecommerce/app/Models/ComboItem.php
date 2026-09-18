<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Product;
use Modules\Variant\Models\ProductVariant;

class ComboItem extends Model
{
    protected $fillable = ['combo_id', 'product_id', 'variant_id', 'quantity', 'sort_order'];

    protected $casts = ['quantity' => 'integer', 'sort_order' => 'integer'];

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
