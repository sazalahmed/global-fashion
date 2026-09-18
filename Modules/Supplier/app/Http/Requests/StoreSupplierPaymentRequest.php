<?php

namespace Modules\Supplier\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'payment_account_id' => 'required|exists:payment_accounts,id',
            'payment_type'   => 'nullable|string|in:payment,advance',
            'payment_date'   => ['required', 'date', new \App\Rules\AllowedTransactionDate],
            'purchase_id'    => 'nullable|exists:purchases,id',
            'reference'      => 'nullable|string|max:100',
            'notes'          => 'nullable|string|max:500',

            // Split payments (optional — pay with multiple methods)
            'splits'                      => 'nullable|array|min:1',
            'splits.*.amount'             => 'required_with:splits|numeric|min:0.01',
            'splits.*.payment_account_id' => 'required_with:splits|exists:payment_accounts,id',
        ];
    }

    public function attributes(): array
    {
        return [
            'payment_method' => 'payment method',
            'payment_type'   => 'payment type',
            'payment_date'   => 'payment date',
            'purchase_id'    => 'purchase order',
        ];
    }
}
