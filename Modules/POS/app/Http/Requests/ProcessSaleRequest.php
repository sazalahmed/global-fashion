<?php

namespace Modules\POS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cart'                          => ['required', 'array', 'min:1'],
            'cart.*.product_id'             => ['required', 'integer'],
            'cart.*.variant_id'             => ['nullable', 'integer'],
            'cart.*.quantity'               => ['required', 'integer', 'min:1'],
            'cart.*.unit_price'             => ['required', 'numeric', 'min:0'],
            'cart.*.discount_amount'        => ['nullable', 'numeric', 'min:0'],
            'customer_id'                   => ['nullable', 'integer'],
            'walkin_customer_name'          => ['nullable', 'string', 'max:255'],
            'sale_date'                     => ['required', 'date', new \App\Rules\AllowedTransactionDate],
            'payments'                      => ['required', 'array', 'min:1'],
            'payments.*.amount'             => ['required', 'numeric', 'min:0'],
            'payments.*.method'             => ['nullable', 'string'],
            'payments.*.payment_account_id' => ['required', 'exists:payment_accounts,id'],
            'payments.*.reference'          => ['nullable', 'string'],
            'discount_type'                 => ['nullable', 'in:flat,percent'],
            'discount_value'                => ['nullable', 'numeric', 'min:0'],
            'tax_rate'                      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'note'                          => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'cart.*.product_id'             => 'product',
            'cart.*.quantity'               => 'quantity',
            'cart.*.unit_price'             => 'unit price',
            'payments.*.amount'             => 'payment amount',
            'payments.*.method'             => 'payment method',
            'payments.*.payment_account_id' => 'payment account',
        ];
    }
}
