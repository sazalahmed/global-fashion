<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlogCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('blogCategory')?->id;

        return [
            'name' => 'required|string|max:100',
            'slug' => [
                'nullable', 'string', 'max:120',
                Rule::unique('blog_categories', 'slug')->ignore($categoryId),
            ],
            'is_active' => 'boolean',
        ];
    }
}
