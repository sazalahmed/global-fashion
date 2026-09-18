<?php

namespace Modules\Attendance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'attendance_date' => 'required|date',
            'branch_id' => 'nullable|exists:branches,id',
            'records' => 'required|array|min:1',
            'records.*.employee_id' => 'required|exists:employees,id',
            'records.*.status' => 'required|in:present,absent,late,half_day,on_leave,off',
            'records.*.check_in' => 'nullable|date_format:H:i',
            'records.*.check_out' => 'nullable|date_format:H:i',
            'records.*.note' => 'nullable|string|max:500',
        ];
    }
}
