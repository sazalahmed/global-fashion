<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalaryIncrementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'applied_at'      => ['required', 'date'],
            'increment_type'  => ['required', 'in:amount,percentage'],
            'increment_value' => ['required', 'numeric', 'min:0.01'],
            'note'            => ['nullable', 'string', 'max:255'],
        ];
    }
}
