<?php

namespace Modules\Activity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClearActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'log_name'        => ['nullable', 'string', 'max:100'],
            'older_than_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }

    public function attributes(): array
    {
        return [
            'log_name'        => 'log name',
            'older_than_days' => 'older than days',
        ];
    }
}
