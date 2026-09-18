<?php

namespace Modules\Loan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLenderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => 'required|string|max:255',
            'company_name'    => 'nullable|string|max:255',
            'phone'           => 'nullable|string|max:50',
            'email'           => 'nullable|email|max:255',
            'photo'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'address'         => 'nullable|string',
            'bank_name'       => 'nullable|string|max:255',
            'account_number'  => 'nullable|string|max:100',
            'bank_branch'     => 'nullable|string|max:255',
            'status'          => 'nullable|in:active,inactive',
            'notes'           => 'nullable|string',
        ];
    }
}
