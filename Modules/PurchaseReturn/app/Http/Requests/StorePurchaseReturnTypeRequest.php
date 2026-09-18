<?php

namespace Modules\PurchaseReturn\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseReturnTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100', 'unique:purchase_return_types,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['nullable', 'boolean'],
        ];
    }
}
