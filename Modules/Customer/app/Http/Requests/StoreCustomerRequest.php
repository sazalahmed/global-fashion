<?php

namespace Modules\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
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
            'name'              => 'required|string|max:255',
            'phone'             => ['required', 'string', 'max:30', 'unique:customers,phone', new \App\Rules\PhoneNumber],
            'email'             => 'nullable|email|max:255|unique:customers,email',
            'company_name'      => 'nullable|string|max:255',
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'area_id'           => 'nullable|exists:areas,id',
            'district'          => 'nullable|string|max:50',
            'upazila'           => 'nullable|string|max:100',
            'address'           => 'nullable|string|max:500',
            'shipping_address'  => 'nullable|string|max:500',
            'photo'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'notes'             => 'nullable|string|max:2000',
            'is_active'         => 'nullable',
            'branch_id'         => 'nullable|exists:branches,id',
        ];
    }
}
