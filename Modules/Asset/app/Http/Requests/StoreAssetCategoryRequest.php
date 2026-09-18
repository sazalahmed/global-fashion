<?php

namespace Modules\Asset\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Fill the NOT-NULL numeric columns and the method with their schema
     * defaults when left blank, so an empty form field never inserts NULL.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'depreciation_method' => $this->input('depreciation_method') ?: 'straight_line',
            'useful_life_years'   => $this->filled('useful_life_years') ? $this->input('useful_life_years') : 5,
            'depreciation_rate'   => $this->filled('depreciation_rate') ? $this->input('depreciation_rate') : 20,
        ]);
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id ?? $this->route('category');

        return [
            'name'                                => ['required', 'string', 'max:120', Rule::unique('asset_categories', 'name')->ignore($categoryId)],
            'useful_life_years'                   => ['required', 'integer', 'min:1', 'max:100'],
            'depreciation_rate'                   => ['required', 'numeric', 'min:0', 'max:100'],
            'depreciation_method'                 => ['required', 'in:straight_line,declining_balance'],
            'account_id'                          => ['nullable', 'integer', 'exists:accounts,id'],
            'depreciation_account_id'             => ['nullable', 'integer', 'exists:accounts,id'],
            'accumulated_depreciation_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
        ];
    }
}
