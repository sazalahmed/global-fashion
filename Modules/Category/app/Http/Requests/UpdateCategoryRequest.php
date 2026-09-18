<?php

namespace Modules\Category\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug,' . ($this->route('category')?->id ?? $this->route('category')),
            'parent_id' => 'nullable|integer|exists:categories,id',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
            'show_in_menu' => 'boolean',
            'show_in_top' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'remove_image' => 'boolean',
        ];
    }

    /**
     * Coerce the visibility checkboxes to booleans (unchecked = false).
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'show_in_menu' => $this->boolean('show_in_menu'),
            'show_in_top' => $this->boolean('show_in_top'),
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    /**
     * Get custom attribute names for error messages.
     */
    public function attributes(): array
    {
        return [
            'parent_id' => 'parent category',
            'meta_title' => 'meta title',
            'meta_description' => 'meta description',
            'sort_order' => 'sort order',
        ];
    }
}
