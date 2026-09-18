<?php

namespace Modules\Sale\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Product;
use Modules\Variant\Models\ProductVariant;

class SaleItem extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sale_id',
        'product_id',
        'variant_id',
        'variant_label',
        'product_name',
        'product_sku',
        'quantity',
        'batch_no',
        'expired_date',
        'unit_price',
        'discount_amount',
        'tax_amount',
        'subtotal',
        // Combo provenance (null for plain product lines).
        'combo_id',
        'combo_group',
        'combo_name',
        'combo_price',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'combo_price' => 'decimal:2',
            'quantity' => 'integer',
            'expired_date' => 'date',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /** The combo this line was expanded from (null for plain product lines). */
    public function combo(): BelongsTo
    {
        return $this->belongsTo(\Modules\Ecommerce\Models\Combo::class, 'combo_id');
    }
}
