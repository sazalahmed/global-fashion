<?php

namespace Modules\Purchase\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Branch\Models\Branch;

class UpdatePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->filled('branch_id')) {
            $branchId = auth()->user()->branch_id
                ?? Branch::where('is_active', true)->value('id');
            if ($branchId) {
                $this->merge(['branch_id' => $branchId]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'supplier_id'        => 'required|exists:suppliers,id',
            'branch_id'          => 'nullable|exists:branches,id',
            'po_date'            => 'required|date',
            'expected_delivery'  => 'nullable|date|after_or_equal:po_date',
            'payment_terms'      => 'nullable|string|max:50',
            'shipping_cost'      => 'nullable|numeric|min:0',
            'order_discount'     => 'nullable|numeric|min:0',
            'order_tax_mode'     => 'nullable|in:percent,flat',
            'order_tax_rate'     => 'nullable|numeric|min:0|max:100',
            'order_tax_amount'   => 'nullable|numeric|min:0',
            'supplier_invoice_ref' => 'nullable|string|max:100',
            'notes'              => 'nullable|string|max:1000',
            'internal_notes'     => 'nullable|string|max:1000',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|integer',
            'items.*.unit_id'    => 'nullable|integer',
            'items.*.qty'        => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount'   => 'nullable|numeric|min:0',
            'items.*.tax_rate'   => 'nullable|numeric|min:0|max:100',
            'payments'                      => 'nullable|array',
            'payments.*.amount'             => 'nullable|numeric|min:0',
            'payments.*.payment_account_id' => 'nullable|exists:payment_accounts,id',
            'payments.*.reference'          => 'nullable|string|max:100',
        ];
    }

    /**
     * A payment account is required for any payment row with an amount entered.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ((array) $this->input('payments', []) as $i => $payment) {
                $amount = (float) ($payment['amount'] ?? 0);
                if ($amount > 0 && empty($payment['payment_account_id'])) {
                    $validator->errors()->add(
                        "payments.{$i}.payment_account_id",
                        __('Select a payment account for the amount entered.')
                    );
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'supplier_id'        => 'supplier',
            'branch_id'          => 'branch',
            'po_date'            => 'purchase date',
            'expected_delivery'  => 'expected delivery date',
            'items.*.product_id' => 'product',
            'items.*.qty'        => 'quantity',
            'items.*.unit_price' => 'unit price',
        ];
    }
}
