<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncompleteCheckout extends Model
{
    protected $fillable = [
        'token',
        'sale_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'address',
        'district_id',
        'shipping_zone_id',
        'cart',
        'converted_order_id',
    ];

    protected function casts(): array
    {
        return [
            'cart' => 'array',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(\Modules\Sale\Models\Sale::class);
    }

    public function convertedOrder(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrder::class, 'converted_order_id');
    }

    /** Captures that have not been placed as a real order yet. */
    public function scopeUnconverted(Builder $query): Builder
    {
        return $query->whereNull('converted_order_id');
    }
}
