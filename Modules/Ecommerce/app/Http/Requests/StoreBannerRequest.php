<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $imageRule = $this->isMethod('POST') ? 'required' : 'nullable';

        return [
            'button_url' => 'nullable|string|max:500',
            'image' => $imageRule . '|image|mimes:jpg,jpeg,png,webp|max:2048',
            'position' => 'required|in:hero,promo_large',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ];
    }
}
