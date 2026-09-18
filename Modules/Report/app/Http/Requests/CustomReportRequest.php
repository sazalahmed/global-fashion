<?php

namespace Modules\Report\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_type' => ['required', 'in:sales,purchase,inventory,financial'],
            'date_from'   => ['nullable', 'date'],
            'date_to'     => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    public function attributes(): array
    {
        return [
            'report_type' => 'report type',
            'date_from'   => 'from date',
            'date_to'     => 'to date',
        ];
    }
}
