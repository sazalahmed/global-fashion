<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Employee\Models\Department;
use Modules\Employee\Models\Designation;
use Modules\Employee\Models\Employee;

class EmployeeApiController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Employee::with('branch')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"))
            ->when($request->department, fn ($q, $d) => $q->byDepartment($d))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->branch_id, fn ($q, $b) => $q->byBranch($b))
            ->latest();

        return $this->paginatedSuccess($query->paginate($request->input('per_page', 15)));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(Employee::with('branch')->findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $v = $request->validate([
            'name' => 'required|string|max:255', 'phone' => 'required|string|max:20',
            'email' => 'nullable|email', 'nid' => 'nullable|string|max:50',
            'department' => 'required|string|max:100', 'designation' => 'required|string|max:100',
            'branch_id' => 'nullable|integer|exists:branches,id', 'salary' => 'required|numeric|min:0',
            'joining_date' => 'required|date', 'address' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string', 'emergency_contact_phone' => 'nullable|string',
        ]);
        $this->mapDepartmentDesignation($v);
        $v['employee_id'] = 'EMP-' . str_pad(Employee::count() + 1, 4, '0', STR_PAD_LEFT);
        $v['created_by'] = auth()->id();
        return $this->success(Employee::create($v), 'Employee created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $emp = Employee::findOrFail($id);
        $v = $request->validate([
            'name' => 'sometimes|string|max:255', 'phone' => 'sometimes|string|max:20',
            'email' => 'nullable|email', 'department' => 'sometimes|string|max:100',
            'designation' => 'sometimes|string|max:100', 'branch_id' => 'nullable|integer',
            'salary' => 'sometimes|numeric|min:0', 'status' => 'nullable|in:active,inactive',
            'address' => 'nullable|string',
        ]);
        $this->mapDepartmentDesignation($v);
        $emp->update($v);
        return $this->success($emp->fresh());
    }

    /**
     * The mobile client sends department/designation as name strings. Resolve
     * them to the managed FK ids (creating the record if it's new) so the
     * employees table stores ids while the API contract stays name-based.
     */
    private function mapDepartmentDesignation(array &$v): void
    {
        if (array_key_exists('department', $v)) {
            $v['department_id'] = filled($v['department'])
                ? Department::firstOrCreate(['name' => $v['department']], ['is_active' => true])->id
                : null;
            unset($v['department']);
        }
        if (array_key_exists('designation', $v)) {
            $v['designation_id'] = filled($v['designation'])
                ? Designation::firstOrCreate(['name' => $v['designation']], ['is_active' => true])->id
                : null;
            unset($v['designation']);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        Employee::findOrFail($id)->update(['status' => 'inactive']);
        return $this->success(null, 'Employee deactivated');
    }
}
