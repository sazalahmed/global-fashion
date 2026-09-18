<?php

namespace Modules\Setting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100', 'unique:tax_rates,name,NULL,id,deleted_at,NULL'],
            'rate'        => ['required', 'numeric', 'min:0', 'max:100'],
            'type'        => ['required', 'in:percentage,fixed,exempt'],
            'apply_to'    => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_default'  => ['nullable', 'boolean'],
            'is_active'   => ['nullable', 'boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'is_default' => $this->boolean('is_default'),
            'is_active'  => $this->has('is_active') ? $this->boolean('is_active') : true,
        ]);
    }
}
