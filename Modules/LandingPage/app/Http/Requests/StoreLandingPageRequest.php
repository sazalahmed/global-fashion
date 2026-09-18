<?php

namespace Modules\LandingPage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLandingPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                    => ['required', 'string', 'max:255'],
            'template'                => ['required', 'string', 'in:template-1,template-2,template-3,template-4,template-5'],
            'hero_title'              => ['required', 'string'],
            'hero_subtitle'           => ['nullable', 'string'],
            'hero_image'              => ['nullable', 'image', 'max:2048'],
            'offer_price'             => ['nullable', 'numeric', 'min:0'],
            'original_price'          => ['nullable', 'numeric', 'min:0'],
            'video_url'               => ['nullable', 'url'],
            'product_ids'             => ['required', 'array', 'min:1', 'max:10'],
            'product_ids.*'           => ['required', 'integer', 'exists:products,id'],
            'delivery_inside_dhaka'   => ['required', 'numeric', 'min:0'],
            'delivery_outside_dhaka'  => ['required', 'numeric', 'min:0'],
            'contact_phone'           => ['nullable', 'string', 'max:20'],
            'custom_css'              => ['nullable', 'string'],
            'meta_title'              => ['nullable', 'string', 'max:255'],
            'meta_description'        => ['nullable', 'string', 'max:500'],

            // JSON sections
            'faqs'                    => ['nullable', 'array'],
            'faqs.*.question'         => ['nullable', 'string'],
            'faqs.*.answer'           => ['nullable', 'string'],
            'benefits'                => ['nullable', 'array'],
            'benefits.*.text'         => ['nullable', 'string'],
            'benefits.*.title'        => ['nullable', 'string'],
            'benefits.*.description'  => ['nullable', 'string'],
            'sizes'                   => ['nullable', 'array'],
            'sizes.*.size'            => ['nullable', 'string'],
            'sizes.*.chest'           => ['nullable', 'string'],
            'sizes.*.length'          => ['nullable', 'string'],
            'details'                 => ['nullable', 'array'],
            'details.*.title'         => ['nullable', 'string'],
            'details.*.text'          => ['nullable', 'string'],
            'ingredients'             => ['nullable', 'array'],
            'ingredients.*.text'      => ['nullable', 'string'],
        ];
    }
}
