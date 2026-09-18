<?php

namespace Modules\PurchaseReturn\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purchase_id'        => ['required', 'exists:purchases,id'],
            'supplier_id'        => ['required', 'exists:suppliers,id'],
            'return_date'        => ['required', 'date', new \App\Rules\AllowedTransactionDate],
            'reason'             => ['nullable', 'string', 'max:255'],
            'refunded_amount'    => ['nullable', 'numeric', 'min:0'],
            'refund_account_id'  => ['nullable', 'exists:payment_accounts,id'],
            'notes'              => ['nullable', 'string', 'max:1000'],
            'branch_id'          => ['nullable', 'exists:branches,id'],
            'status'             => ['nullable', 'in:draft,confirmed'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.reason'     => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'purchase_id'        => 'purchase',
            'supplier_id'        => 'supplier',
            'return_date'        => 'return date',
            'refund_account_id'  => 'refund account',
            'items.*.product_id' => 'product',
            'items.*.quantity'   => 'quantity',
            'items.*.unit_price' => 'unit price',
            'items.*.tax_amount' => 'tax',
        ];
    }

    /**
     * When the supplier refunds cash, a refund account is required.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ((float) $this->input('refunded_amount', 0) > 0
                && empty($this->input('refund_account_id'))) {
                $validator->errors()->add('refund_account_id', __('Select the account the refund was received into.'));
            }
        });
    }
}
