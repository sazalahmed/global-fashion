<?php

namespace Modules\Report\Http\Requests;

class PurchaseReportRequest extends DateRangeRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'report_type' => ['nullable', 'string', 'in:summary,detailed,by_supplier,by_product'],
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'report_type' => 'report type',
        ]);
    }
}
