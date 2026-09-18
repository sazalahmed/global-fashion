<?php

namespace Modules\Dashboard\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_date' => ['nullable', 'date'],
            'to_date'   => ['nullable', 'date', 'after_or_equal:from_date'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'period'    => ['nullable', 'string', 'in:today,week,month,quarter,year'],
        ];
    }

    public function attributes(): array
    {
        return [
            'from_date' => 'from date',
            'to_date'   => 'to date',
            'branch_id' => 'branch',
            'period'    => 'period',
        ];
    }
}
