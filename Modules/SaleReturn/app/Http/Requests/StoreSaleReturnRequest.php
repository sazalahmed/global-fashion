<?php

namespace Modules\SaleReturn\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleReturnRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'sale_id'              => 'required|exists:sales,id',
            'customer_id'          => 'nullable|exists:customers,id',
            'branch_id'            => 'required|exists:branches,id',
            'return_date'          => ['required', 'date', new \App\Rules\AllowedTransactionDate],
            'reason'               => 'required|string|max:500',
            'refund_method'        => 'required|exists:payment_accounts,id',
            'notes'                => 'nullable|string|max:2000',
            'items'                => 'required|array|min:1',
            'items.*.sale_item_id' => 'nullable|exists:sale_items,id',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.variant_id'   => 'nullable|exists:product_variants,id',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.tax_amount'   => 'nullable|numeric|min:0',
            'items.*.condition'    => 'nullable|in:good,damaged',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'sale_id'              => 'sale',
            'customer_id'          => 'customer',
            'branch_id'            => 'branch',
            'return_date'          => 'return date',
            'refund_method'        => 'refund method',
            'items.*.product_id'   => 'product',
            'items.*.quantity'     => 'quantity',
            'items.*.unit_price'   => 'unit price',
            'items.*.tax_amount'   => 'tax amount',
            'items.*.condition'    => 'condition',
        ];
    }
}
