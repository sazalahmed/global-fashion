<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $slugUnique = 'unique:blog_posts,slug';
        if ($this->route('post')) {
            $slugUnique .= ',' . $this->route('post')->id;
        }

        return [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|' . $slugUnique,
            'excerpt' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'featured_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'blog_category_id' => 'nullable|exists:blog_categories,id',
            'tags' => 'nullable|string|max:500',
            'is_published' => 'boolean',
            'show_on_homepage' => 'boolean',
            'published_at' => 'nullable|date',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:300',
            'seo_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }
}
