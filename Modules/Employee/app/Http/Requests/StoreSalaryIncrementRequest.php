<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalaryIncrementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'increment_type'  => ['required', 'in:amount,percentage'],
            'increment_value' => ['required', 'numeric', 'min:0.01'],
            'applied_at'      => ['nullable', 'date'],
            'note'            => ['nullable', 'string', 'max:255'],
        ];
    }
}
