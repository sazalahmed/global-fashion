<?php

namespace Modules\Asset\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'asset_category_id' => 'required|exists:asset_categories,id',
            'serial_number' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'location' => 'nullable|string|max:255',
            'branch_id' => 'nullable|exists:branches,id',
            'vendor_name' => 'nullable|string|max:255',
            'vendor_invoice_no' => 'nullable|string|max:100',
            'purchase_date' => 'required|date',
            'purchase_price' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0|lte:purchase_price',
            'payment_account_id' => 'nullable|exists:payment_accounts,id',
            'salvage_value' => 'nullable|numeric|min:0',
            'is_depreciable' => 'boolean',
            'depreciation_method' => 'nullable|in:straight_line,declining_balance',
            'useful_life_years' => 'nullable|integer|min:1|max:50',
            'next_maintenance_date' => 'nullable|date',
            'warranty_info' => 'nullable|string|max:1000',
            'warranty_expiry' => 'nullable|date',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }

    /**
     * Money paid must come from somewhere: an explicit paid amount requires a
     * payment account. (Blank paid amount + no account = recorded as unpaid.)
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ((float) $this->input('paid_amount') > 0 && ! $this->input('payment_account_id')) {
                $v->errors()->add('payment_account_id', __('Select the payment account the paid amount came from.'));
            }
        });
    }
}
