<?php

namespace Modules\Report\Http\Requests;

class FinancialReportRequest extends DateRangeRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'year'        => ['nullable', 'integer', 'min:2020', 'max:2099'],
            'report_type' => ['nullable', 'string', 'in:profit_loss,balance_sheet,cash_flow,trial_balance'],
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'report_type' => 'report type',
        ]);
    }
}
