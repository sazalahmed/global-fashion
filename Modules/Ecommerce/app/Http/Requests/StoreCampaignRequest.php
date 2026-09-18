<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $campaignId = $this->route('campaign')?->id;

        return [
            'name'           => ['required', 'string', 'max:150'],
            'slug'           => [
                'required', 'string', 'max:150', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('campaigns', 'slug')->ignore($campaignId)->whereNull('deleted_at'),
            ],
            'description'    => ['nullable', 'string', 'max:2000'],
            'starts_at'      => ['required', 'date'],
            'ends_at'        => ['required', 'date', 'after:starts_at'],
            'scope'          => ['required', 'in:all,categories,products'],
            'discount_type'  => ['required', 'in:percentage,flat'],
            'discount_value' => [
                'required', 'numeric', 'min:0',
                $this->input('discount_type') === 'percentage' ? 'max:100' : 'max:99999999',
            ],
            'priority'       => ['nullable', 'integer', 'min:0', 'max:1000'],
            'badge_label'    => ['nullable', 'string', 'max:50'],
            'is_active'      => ['nullable', 'boolean'],
            'category_ids'   => ['array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'product_ids'    => ['array'],
            'product_ids.*'  => ['integer', 'exists:products,id'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
            'priority'  => $this->input('priority') ?: 0,
        ]);
    }

    /**
     * Validated zone fields ready for Campaign::fill(). The pivots
     * (category_ids / product_ids) are returned separately by ids().
     */
    public function attributes(): array
    {
        return [
            'category_ids.*' => 'category',
            'product_ids.*'  => 'product',
        ];
    }

    public function toCampaignAttributes(): array
    {
        $v = $this->validated();
        return [
            'name'           => $v['name'],
            'slug'           => $v['slug'],
            'description'    => $v['description'] ?? null,
            'starts_at'      => $v['starts_at'],
            'ends_at'        => $v['ends_at'],
            'scope'          => $v['scope'],
            'discount_type'  => $v['discount_type'],
            'discount_value' => $v['discount_value'],
            'priority'       => $v['priority'] ?? 0,
            'badge_label'    => $v['badge_label'] ?? null,
            'is_active'      => (bool) ($v['is_active'] ?? true),
        ];
    }

    public function categoryIds(): array
    {
        return $this->input('scope') === 'categories'
            ? array_map('intval', $this->input('category_ids', []))
            : [];
    }

    public function productIds(): array
    {
        return $this->input('scope') === 'products'
            ? array_map('intval', $this->input('product_ids', []))
            : [];
    }
}
