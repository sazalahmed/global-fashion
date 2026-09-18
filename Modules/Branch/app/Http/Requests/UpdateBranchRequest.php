<?php

namespace Modules\Branch\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => 'required|string|max:255',
            'code'           => 'required|string|max:20|unique:branches,code,' . ($this->route('branch')?->id ?? $this->route('branch')),
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:255',
            'address'        => 'nullable|string|max:500',
            'city'           => 'nullable|string|max:100',
            'district'       => 'nullable|string|max:100',
            'zip_code'       => 'nullable|string|max:10',
            'manager_name'   => 'nullable|string|max:255',
            'manager_phone'  => 'nullable|string|max:20',
            'is_main'        => 'nullable|boolean',
            'is_active'      => 'nullable|boolean',
            'is_pos_enabled' => 'nullable|boolean',
            'is_ecom_enabled'=> 'nullable|boolean',
            'opening_time'   => 'nullable|date_format:H:i',
            'closing_time'   => 'nullable|date_format:H:i',
            'notes'          => 'nullable|string|max:1000',
            'logo'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_logo'    => 'nullable|boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'code'           => 'branch code',
            'is_main'        => 'main branch',
            'is_active'      => 'active status',
            'is_pos_enabled' => 'POS enabled',
            'is_ecom_enabled'=> 'eCommerce enabled',
            'zip_code'       => 'zip code',
            'manager_name'   => 'manager name',
            'manager_phone'  => 'manager phone',
            'opening_time'   => 'opening time',
            'closing_time'   => 'closing time',
        ];
    }
}
