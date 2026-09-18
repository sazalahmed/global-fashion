<?php

namespace Modules\Report\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StaffReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from'  => ['nullable', 'date'],
            'date_to'    => ['nullable', 'date', 'after_or_equal:date_from'],
            'branch_id'  => ['nullable', 'integer', 'exists:branches,id'],
            'department' => ['nullable', 'string', 'max:100'],
            'status'     => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    public function attributes(): array
    {
        return [
            'date_from'  => 'from date',
            'date_to'    => 'to date',
            'branch_id'  => 'branch',
        ];
    }
}
