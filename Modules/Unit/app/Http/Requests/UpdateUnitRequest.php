<?php

namespace Modules\Unit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $unitId = $this->route('unit') instanceof \Modules\Unit\Models\Unit
            ? $this->route('unit')->id
            : $this->route('unit');

        return [
            'name' => 'required|string|max:100',
            'short_name' => 'required|string|max:20',
            'unit_type' => 'required|in:base,sub',
            'base_unit_id' => [
                'required_if:unit_type,sub',
                'nullable',
                'integer',
                'exists:units,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($unitId) {
                    if ($value && (int) $value === (int) $unitId) {
                        $fail('A unit cannot be its own base unit.');
                    }
                },
            ],
            'conversion_factor' => 'required_if:unit_type,sub|nullable|numeric|gt:0',
            'allow_decimal' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
        ];
    }

    /**
     * Get custom attribute names for error messages.
     */
    public function attributes(): array
    {
        return [
            'base_unit_id' => 'base unit',
            'short_name' => 'short name',
            'unit_type' => 'unit type',
            'conversion_factor' => 'conversion factor',
            'allow_decimal' => 'allow decimal',
        ];
    }
}
