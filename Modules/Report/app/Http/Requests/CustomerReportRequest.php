<?php

namespace Modules\Report\Http\Requests;

class CustomerReportRequest extends DateRangeRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'report_type' => ['nullable', 'string', 'in:summary,detailed,top_customers,receivables'],
            'limit'       => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'customer_id' => 'customer',
            'report_type' => 'report type',
        ]);
    }
}
