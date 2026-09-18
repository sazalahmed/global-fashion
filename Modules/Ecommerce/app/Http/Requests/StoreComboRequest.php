<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComboRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'description'        => ['nullable', 'string'],
            'thumbnail'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'combo_price'        => ['required', 'numeric', 'min:0'],
            'is_active'          => ['nullable', 'boolean'],
            'size_required'      => ['nullable', 'boolean'],
            'sort_order'         => ['nullable', 'integer', 'min:0'],
            'categories'         => ['nullable', 'array'],
            'categories.*'       => ['integer', 'exists:categories,id'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'gallery'            => ['nullable', 'array'],
            'gallery.*'          => ['nullable', 'string', 'max:255'],
        ];
    }
}
