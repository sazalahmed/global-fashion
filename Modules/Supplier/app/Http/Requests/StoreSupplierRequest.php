<?php

namespace Modules\Supplier\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name'   => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone'          => 'required|string|max:20',
            'email'          => 'nullable|email|max:255',
            'division'       => 'nullable|string|max:100',
            'district'       => 'nullable|string|max:100',
            'area'           => 'nullable|string|max:255',
            'address'        => 'nullable|string|max:500',
            'bank_name'      => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'bank_branch'    => 'nullable|string|max:255',
            'routing_number' => 'nullable|string|max:20',
            'tin'            => 'nullable|string|max:50',
            'bin'            => 'nullable|string|max:50',
            'trade_license'  => 'nullable|string|max:100',
            'credit_limit'   => 'nullable|numeric|min:0',
            'payment_terms'  => 'nullable|string|max:50',
            'opening_balance' => 'nullable|numeric|min:0',
            'status'         => 'nullable|in:active,inactive',
            'notes'          => 'nullable|string|max:1000',
        ];
    }

    public function attributes(): array
    {
        return [
            'company_name'   => 'company name',
            'contact_person' => 'contact person',
            'account_number' => 'account number',
            'bank_branch'    => 'bank branch',
            'routing_number' => 'routing number',
            'trade_license'  => 'trade license',
            'credit_limit'   => 'credit limit',
            'payment_terms'  => 'payment terms',
            'opening_balance' => 'opening balance',
        ];
    }
}
