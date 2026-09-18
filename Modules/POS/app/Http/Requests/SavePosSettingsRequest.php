<?php

namespace Modules\POS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SavePosSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'default_customer_id'    => ['nullable', 'integer', 'exists:customers,id'],
            'default_payment_method' => ['nullable', 'exists:payment_accounts,id'],
            'sound_enabled'          => ['nullable', 'boolean'],
            'show_stock_qty'         => ['nullable', 'boolean'],
            'allow_negative_stock'   => ['nullable', 'boolean'],
            'print_full_invoice'     => ['nullable', 'boolean'],
            'print_thermal_receipt'  => ['nullable', 'boolean'],
            'receipt_header'         => ['nullable', 'string', 'max:1000'],
            'receipt_footer'         => ['nullable', 'string', 'max:1000'],
            'receipt_show_logo'      => ['nullable', 'boolean'],
            'receipt_show_customer'  => ['nullable', 'boolean'],
            'receipt_show_barcode'   => ['nullable', 'boolean'],
        ];
    }
}
