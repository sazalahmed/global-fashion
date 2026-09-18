<?php

namespace Modules\AiAssistant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', new \App\Rules\PhoneNumber],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'product_id' => ['nullable', 'integer', 'min:1'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'variant_id' => ['nullable', 'integer', 'min:1'],
            'conversation_id' => ['nullable', 'string', 'size:36'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_phone.regex' => 'Phone must be 11 digits starting with 013–019 (e.g. 01712345678).',
        ];
    }
}
