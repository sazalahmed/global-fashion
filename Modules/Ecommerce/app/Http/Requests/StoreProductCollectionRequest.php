<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $slugUnique = 'unique:product_collections,slug';
        if ($this->route('collection')) {
            $slugUnique .= ',' . $this->route('collection')->id;
        }

        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|' . $slugUnique,
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:manual,auto',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            'filter_rules' => 'nullable|array',
            'filter_rules.category_ids' => 'nullable|array',
            'filter_rules.category_ids.*' => 'exists:categories,id',
            'filter_rules.brand_ids' => 'nullable|array',
            'filter_rules.brand_ids.*' => 'exists:brands,id',
            'filter_rules.sort_by' => 'nullable|in:latest,price_low,price_high,name_asc',
            'filter_rules.limit' => 'nullable|integer|min:1|max:50',
            'filter_rules.price_min' => 'nullable|numeric|min:0',
            'filter_rules.price_max' => 'nullable|numeric|min:0',
        ];
    }
}
