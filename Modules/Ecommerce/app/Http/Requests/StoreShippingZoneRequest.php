<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShippingZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                    => 'required|string|max:255',
            'bn_name'                 => 'nullable|string|max:150',
            'districts'               => 'nullable|array',
            'districts.*'             => 'integer|exists:districts,id',
            'flat_rate'               => 'required|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'estimated_days'          => 'nullable|integer|min:1|max:30',
            'is_active'               => 'nullable|boolean',
        ];
    }

    /**
     * Split the zone fields from the district IDs so the service can
     * persist the parent row first, then attach the pivot.
     */
    public function toZoneAttributes(): array
    {
        return [
            'name'                    => $this->validated()['name'],
            'bn_name'                 => $this->validated()['bn_name'] ?? null,
            'flat_rate'               => $this->validated()['flat_rate'],
            'free_shipping_threshold' => $this->validated()['free_shipping_threshold'] ?? null,
            'estimated_days'          => $this->validated()['estimated_days'] ?? 3,
            'is_active'               => (bool) ($this->validated()['is_active'] ?? true),
        ];
    }

    public function districtIds(): array
    {
        return array_map('intval', $this->validated()['districts'] ?? []);
    }
}
