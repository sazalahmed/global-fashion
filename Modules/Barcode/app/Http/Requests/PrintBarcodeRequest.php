<?php

namespace Modules\Barcode\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrintBarcodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_ids'   => ['required', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'quantities'    => ['required', 'array'],
            'quantities.*'  => ['integer', 'min:1', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'product_ids' => 'products',
            'quantities'  => 'quantities',
        ];
    }
}
