<?php

namespace Modules\Setting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveSidebarConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sidebar'            => ['required', 'array'],
            'sidebar.*.label'    => ['nullable', 'string', 'max:100'],
            'sidebar.*.visible'  => ['nullable', 'boolean'],
            'sidebar.*.order'    => ['nullable', 'integer', 'min:0'],
        ];
    }
}
