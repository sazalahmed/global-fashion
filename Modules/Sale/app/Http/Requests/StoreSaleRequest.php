<?php

namespace Modules\Sale\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Drop empty advance/payment rows before validation so an untouched
     * payment section doesn't trip the required_with rules.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('payments')) {
            return;
        }

        $payments = array_filter((array) $this->input('payments'), function ($p) {
            return is_array($p) && isset($p['amount']) && $p['amount'] !== '' && $p['amount'] !== null;
        });

        $this->merge(['payments' => array_values($payments)]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $statuses = array_keys(\Modules\Sale\Models\Sale::STATUSES);

        return [
            'customer_id'             => ['nullable', 'exists:customers,id'],
            'customer_name_snapshot'  => ['nullable', 'string', 'max:150'],
            'customer_phone_snapshot' => ['nullable', 'string', 'max:30'],
            'customer_address'        => ['nullable', 'string', 'max:1000'],
            'billing_address'         => ['nullable', 'string', 'max:1000'],
            'district_id'             => ['nullable', 'integer', 'exists:districts,id'],
            'thana_id'                => ['nullable', 'integer', 'exists:thanas,id'],
            'invoice_date'            => ['required', 'date', new \App\Rules\AllowedTransactionDate],
            'due_date'                => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'branch_id'               => ['nullable', 'exists:branches,id'],
            'source'                  => ['nullable', 'in:pos,store,ecommerce'],
            'price_type'              => ['nullable', 'in:regular,wholesale,resell'],
            'discount_type'           => ['nullable', 'in:fixed,percentage'],
            'discount_value'          => ['nullable', 'numeric', 'min:0'],
            'tax_rate'                => ['nullable', 'numeric', 'min:0', 'max:100'],
            'shipping_charge'         => ['nullable', 'numeric', 'min:0'],
            'sale_status'             => ['nullable', 'in:' . implode(',', $statuses)],
            'notes'                   => ['nullable', 'string', 'max:2000'],
            'item_description'        => ['nullable', 'string', 'max:2000'],
            'staff_note'              => ['nullable', 'string', 'max:2000'],
            'items'                   => ['required', 'array', 'min:1'],
            'items.*.product_id'      => ['required', 'exists:products,id'],
            'items.*.variant_id'      => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.variant_label'   => ['nullable', 'string', 'max:80'],
            'items.*.quantity'        => ['required', 'integer', 'min:1'],
            'items.*.batch_no'        => ['nullable', 'string', 'max:80'],
            'items.*.expired_date'    => ['nullable', 'date'],
            'items.*.price'           => ['required', 'numeric', 'min:0'],
            'items.*.discount'        => ['nullable', 'numeric', 'min:0'],
            'items.*.tax'             => ['nullable', 'numeric', 'min:0'],
            'items.*.combo_id'        => ['nullable', 'integer', 'exists:combos,id'],
            'items.*.combo_group'     => ['nullable', 'string', 'max:64'],
            'items.*.combo_name'      => ['nullable', 'string', 'max:255'],
            'items.*.combo_price'     => ['nullable', 'numeric', 'min:0'],
            'payments'                       => ['nullable', 'array'],
            'payments.*.amount'              => ['required_with:payments', 'numeric', 'min:0'],
            // Optional — an advance with no account picked falls back to the
            // default (Cash) payment account in SaleService.
            'payments.*.payment_account_id'  => ['nullable', 'exists:payment_accounts,id'],
            'action'               => ['nullable', 'in:draft,create'],
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'invoice_date'         => 'sale date',
            'branch_id'            => 'branch',
            'customer_id'          => 'customer',
            'discount_type'        => 'discount type',
            'discount_value'       => 'discount value',
            'tax_rate'             => 'tax rate',
            'shipping_charge'      => 'shipping charge',
            'items.*.product_id'   => 'product',
            'items.*.quantity'     => 'quantity',
            'items.*.price'        => 'unit price',
            'items.*.discount'     => 'item discount',
            'payments.*.amount'    => 'payment amount',
            'payments.*.method'    => 'payment method',
        ];
    }
}
