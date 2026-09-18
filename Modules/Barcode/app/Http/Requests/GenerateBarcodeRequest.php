<?php

namespace Modules\Barcode\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateBarcodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_ids'    => ['required', 'array'],
            'product_ids.*'  => ['integer', 'exists:products,id'],
            'quantities'     => ['required', 'array'],
            'quantities.*'   => ['integer', 'min:1', 'max:100'],
            'barcode_type'   => ['nullable', 'string', 'in:code128,ean13,upc_a,qr'],
            'show_name'      => ['nullable', 'boolean'],
            'show_price'     => ['nullable', 'boolean'],
            'show_business'  => ['nullable', 'boolean'],
            'label_size'     => ['nullable', 'string', 'in:38x25,50x25,50x30'],
            'labels_per_row' => ['nullable', 'integer', 'in:2,3,4'],
        ];
    }

    public function attributes(): array
    {
        return [
            'product_ids'    => 'products',
            'quantities'     => 'quantities',
            'barcode_type'   => 'barcode type',
            'label_size'     => 'label size',
            'labels_per_row' => 'labels per row',
        ];
    }
}
