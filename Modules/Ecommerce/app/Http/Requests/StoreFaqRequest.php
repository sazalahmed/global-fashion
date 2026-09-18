<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question'  => 'required|string|max:500',
            'answer'    => 'required|string',
            'is_active' => 'boolean',
            'position'  => 'nullable|integer|min:0',
        ];
    }
}
