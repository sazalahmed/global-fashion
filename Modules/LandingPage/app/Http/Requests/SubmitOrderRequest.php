<?php

namespace Modules\LandingPage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'landing_page_id'  => ['required', 'exists:landing_pages,id'],
            'customer_name'    => ['required', 'string', 'max:255'],
            'customer_phone'   => ['required', 'string', 'max:20'],
            'customer_address' => ['required', 'string', 'max:500'],
            'district'         => ['required', 'string'],
            'division'         => ['nullable', 'string'],
            'product_id'       => ['required', 'exists:products,id'],
            'quantity'         => ['nullable', 'integer', 'min:1'],
            'shipping_zone'    => ['nullable', 'in:inside,outside'],
            'payment_method'   => ['nullable', 'string', 'in:cod,bkash,nagad,rocket'],
            'bkash_number'     => ['nullable', 'string', 'max:20'],
            'bkash_txn_id'     => ['nullable', 'string', 'max:100'],
            'nagad_number'     => ['nullable', 'string', 'max:20'],
            'nagad_txn_id'     => ['nullable', 'string', 'max:100'],
            'rocket_number'    => ['nullable', 'string', 'max:20'],
            'rocket_txn_id'    => ['nullable', 'string', 'max:100'],
        ];
    }
}
