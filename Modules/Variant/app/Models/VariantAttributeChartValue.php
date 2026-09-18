<?php

namespace Modules\Variant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VariantAttributeChartValue extends Model
{
    protected $fillable = [
        'variant_attribute_chart_row_id',
        'variant_attribute_value_id',
        'value',
    ];

    protected $casts = [
        'variant_attribute_chart_row_id' => 'integer',
        'variant_attribute_value_id'     => 'integer',
    ];

    public function row(): BelongsTo
    {
        return $this->belongsTo(VariantAttributeChartRow::class, 'variant_attribute_chart_row_id');
    }

    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(VariantAttributeValue::class, 'variant_attribute_value_id');
    }
}
