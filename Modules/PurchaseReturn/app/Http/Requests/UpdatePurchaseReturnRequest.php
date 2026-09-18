<?php

namespace Modules\PurchaseReturn\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseReturnRequest extends FormRequest
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
            'return_date'        => ['required', 'date'],
            'reason'             => ['nullable', 'string', 'max:255'],
            'notes'              => ['nullable', 'string', 'max:1000'],
            'branch_id'          => ['nullable', 'exists:branches,id'],
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
            'items.*.product_id' => 'product',
            'items.*.quantity'   => 'quantity',
            'items.*.unit_price' => 'unit price',
            'items.*.tax_amount' => 'tax',
        ];
    }
}
