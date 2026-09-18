<?php

namespace Modules\AiAssistant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SpeakRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:2000'],
            'message_id' => ['nullable', 'string', 'size:36'],
        ];
    }
}
