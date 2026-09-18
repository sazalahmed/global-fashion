<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')?->id;

        return [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'nid' => 'nullable|string|max:30',
            'department_id' => 'nullable|exists:departments,id',
            'designation_id' => 'nullable|exists:designations,id',
            'branch_id' => 'nullable|exists:branches,id',
            'salary' => 'required|numeric|min:0',
            'salary_structure_id' => 'nullable|exists:salary_structures,id',
            'joining_date' => 'nullable|date',
            'leaving_date' => 'nullable|date|after_or_equal:joining_date',
            'address' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status' => 'required|in:active,inactive,on_leave,terminated',
            'user_id' => 'nullable|exists:users,id',
        ];
    }
}
