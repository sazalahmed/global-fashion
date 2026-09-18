<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Variant\Models\VariantAttributeChartRow;
use Modules\Variant\Models\VariantAttributeValue;

class ProductSizeChartValue extends Model
{
    protected $fillable = [
        'product_id',
        'variant_attribute_chart_row_id',
        'variant_attribute_value_id',
        'value',
    ];

    protected $casts = [
        'product_id'                     => 'integer',
        'variant_attribute_chart_row_id' => 'integer',
        'variant_attribute_value_id'     => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function row(): BelongsTo
    {
        return $this->belongsTo(VariantAttributeChartRow::class, 'variant_attribute_chart_row_id');
    }

    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(VariantAttributeValue::class, 'variant_attribute_value_id');
    }
}
