<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Ecommerce\Models\Page;

class StorePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pageId = $this->route('page')?->id;

        return [
            'title'   => 'required|string|max:255',
            'slug'    => [
                'nullable', 'string', 'max:255',
                Rule::unique('pages', 'slug')->ignore($pageId),
                Rule::notIn(Page::RESERVED_SLUGS),
            ],
            'content' => 'nullable|string',
            'is_published'    => 'boolean',
            'seo_title'       => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:300',
            'seo_image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }
}
