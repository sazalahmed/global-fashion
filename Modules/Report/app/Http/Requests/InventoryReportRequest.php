<?php

namespace Modules\Report\Http\Requests;

class InventoryReportRequest extends DateRangeRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'product_id'   => ['nullable', 'integer', 'exists:products,id'],
            'source_type'  => ['nullable', 'string'],
            'low_stock'    => ['nullable', 'boolean'],
            'report_type'  => ['nullable', 'string', 'in:summary,movement,low_stock'],
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'product_id'   => 'product',
            'source_type'  => 'source type',
            'low_stock'    => 'low stock',
            'report_type'  => 'report type',
        ]);
    }
}
