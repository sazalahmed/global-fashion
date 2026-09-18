<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFlashDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $imageRule = $this->isMethod('POST') ? 'nullable' : 'nullable';

        return [
            'title' => 'required|string|max:255',
            'banner_image' => $imageRule . '|image|mimes:jpg,jpeg,png,webp|max:2048',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'is_active' => 'boolean',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.discount_type' => 'required|in:percentage,fixed',
            'products.*.discount_value' => 'required|numeric|min:0',
        ];
    }
}
