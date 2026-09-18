<?php

namespace Modules\Expense\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id ?? $this->route('category');

        return [
            'name'        => ['required', 'string', 'max:120', Rule::unique('expense_categories', 'name')->ignore($categoryId)],
            'parent_id'   => ['nullable', 'integer', 'exists:expense_categories,id'],
            'account_id'  => ['nullable', 'integer', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['nullable', 'boolean'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ];
    }
}
