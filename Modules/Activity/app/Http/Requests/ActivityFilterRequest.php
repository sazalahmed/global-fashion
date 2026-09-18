<?php

namespace Modules\Activity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivityFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'       => ['nullable', 'string', 'max:255'],
            'log_name'     => ['nullable', 'string', 'max:100'],
            'event'        => ['nullable', 'string', 'in:created,updated,deleted'],
            'causer_id'    => ['nullable', 'integer', 'exists:users,id'],
            'subject_type' => ['nullable', 'string', 'max:255'],
            'from_date'    => ['nullable', 'date'],
            'to_date'      => ['nullable', 'date', 'after_or_equal:from_date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'search'       => 'search',
            'log_name'     => 'log name',
            'event'        => 'event',
            'causer_id'    => 'causer',
            'subject_type' => 'subject type',
            'from_date'    => 'from date',
            'to_date'      => 'to date',
        ];
    }
}
