<?php

namespace Modules\Quotation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuotationRequest extends FormRequest
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
            'customer_id'            => ['nullable', 'exists:customers,id'],
            'branch_id'              => ['nullable', 'exists:branches,id'],
            'quotation_date'         => ['required', 'date', new \App\Rules\AllowedTransactionDate],
            'valid_until'            => ['required', 'date', 'after_or_equal:quotation_date'],
            'reference'              => ['nullable', 'string', 'max:50'],
            'discount_type'          => ['nullable', 'in:fixed,percentage'],
            'discount_value'         => ['nullable', 'numeric', 'min:0'],
            'tax_rate'               => ['nullable', 'numeric', 'min:0', 'max:100'],
            'shipping_charge'        => ['nullable', 'numeric', 'min:0'],
            'notes'                  => ['nullable', 'string', 'max:2000'],
            'terms'                  => ['nullable', 'string', 'max:5000'],
            'billing_address'        => ['nullable', 'string', 'max:1000'],
            'shipping_address'       => ['nullable', 'string', 'max:1000'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'exists:products,id'],
            'items.*.variant_id'     => ['nullable', 'exists:product_variants,id'],
            'items.*.custom_note'    => ['nullable', 'string', 'max:500'],
            'items.*.variant_breakdown' => ['nullable', 'string'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
            'items.*.unit_price'     => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'branch_id'               => 'branch',
            'customer_id'             => 'customer',
            'quotation_date'          => 'quotation date',
            'valid_until'             => 'valid until date',
            'discount_type'           => 'discount type',
            'discount_value'          => 'discount value',
            'tax_rate'                => 'tax rate',
            'shipping_charge'         => 'shipping charge',
            'items.*.product_id'      => 'product',
            'items.*.quantity'        => 'quantity',
            'items.*.unit_price'      => 'unit price',
            'items.*.discount_amount' => 'item discount',
        ];
    }
}
