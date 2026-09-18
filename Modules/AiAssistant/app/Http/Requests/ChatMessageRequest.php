<?php

namespace Modules\AiAssistant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000'],
            'conversation_id' => ['nullable', 'string', 'size:36'],
            'focus_stack' => ['nullable', 'array', 'max:5'],
            'focus_stack.*' => ['integer', 'min:1'],
        ];
    }
}
