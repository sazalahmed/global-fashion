<?php

namespace Modules\Setting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:150'],
            'subject'   => ['required', 'string', 'max:255'],
            'body'      => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
