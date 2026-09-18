<?php

namespace Modules\Variant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVariantAttributeRequest extends FormRequest
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
        $variant = $this->route('variant');
        $attributeId = $variant instanceof \Modules\Variant\Models\VariantAttribute ? $variant->id : $variant;

        return [
            'name'              => 'required|string|max:100|unique:variant_attributes,name,' . $attributeId,
            'display_type'      => 'required|string|in:button,color_swatch',
            'status'            => 'required|string|in:active,inactive',
            'values'            => 'required|array|min:1',
            'values.*.id'       => 'nullable|integer|exists:variant_attribute_values,id',
            'values.*.name'     => 'required|string|max:100',
            'values.*.color_code' => 'nullable|string|max:7',
            'values.*.sort_order' => 'nullable|integer|min:0',
        ];
    }
}
