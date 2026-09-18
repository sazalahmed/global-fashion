<?php

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Direction is a plain enum on the adjustment; the reason is the
            // managed list. The two are chosen independently.
            'type'               => 'required|in:addition,subtraction',
            'reason_id'          => 'required|exists:adjustment_reasons,id',
            'notes'              => 'nullable|string|max:2000',
            'reference'          => 'nullable|string|max:100',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_cost'  => 'nullable|numeric|min:0',
            'items.*.note'       => 'nullable|string|max:500',
        ];
    }
}
