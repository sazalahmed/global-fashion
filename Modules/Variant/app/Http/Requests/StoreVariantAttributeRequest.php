<?php

namespace Modules\Variant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVariantAttributeRequest extends FormRequest
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
        return [
            'name'              => 'required|string|max:100|unique:variant_attributes,name',
            'display_type'      => 'required|string|in:button,color_swatch',
            'status'            => 'required|string|in:active,inactive',
            'values'            => 'required|array|min:1',
            'values.*.name'     => 'required|string|max:100',
            'values.*.color_code' => 'nullable|string|max:7',
            'values.*.sort_order' => 'nullable|integer|min:0',
        ];
    }
}
