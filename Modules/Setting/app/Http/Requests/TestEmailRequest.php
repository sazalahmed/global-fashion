<?php

namespace Modules\Setting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'test_email' => ['required', 'email'],
        ];
    }

    public function attributes(): array
    {
        return [
            'test_email' => 'test email address',
        ];
    }
}
