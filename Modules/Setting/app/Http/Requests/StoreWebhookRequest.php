<?php

namespace Modules\Setting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'url'      => ['required', 'url', 'max:500'],
            'secret'   => ['nullable', 'string', 'max:255'],
            'events'   => ['required', 'array', 'min:1'],
            'events.*' => ['string'],
        ];
    }
}
