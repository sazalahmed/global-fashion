<?php

namespace Modules\Report\Http\Requests;

class SalesReportRequest extends DateRangeRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'report_type' => ['nullable', 'string', 'in:summary,detailed,by_product,by_category,by_customer'],
            'year'        => ['nullable', 'integer', 'min:2020', 'max:2099'],
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'report_type' => 'report type',
        ]);
    }
}
