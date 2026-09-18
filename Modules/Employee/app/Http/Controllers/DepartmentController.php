<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Employee\Models\Department;

class DepartmentController extends Controller
{
    public function index()
    {
        bpAuthorize('hr.view');
        $departments = Department::orderBy('sort_order')->orderBy('name')->get();

        return view('employee::departments.index', compact('departments'));
    }

    public function store(Request $request)
    {
        bpAuthorize('hr.create');
        Department::create($this->validateData($request));

        return back()->with('success', __('Department created.'));
    }

    public function update(Request $request, Department $department)
    {
        bpAuthorize('hr.edit');
        $department->update($this->validateData($request, $department));

        return back()->with('success', __('Department updated.'));
    }

    public function destroy(Department $department)
    {
        bpAuthorize('hr.delete');
        $department->delete();

        return back()->with('success', __('Department deleted.'));
    }

    public function toggleStatus(Department $department): JsonResponse
    {
        bpAuthorize('hr.edit');
        $department->update(['is_active' => ! $department->is_active]);

        return response()->json(['success' => true, 'is_active' => $department->is_active, 'message' => __('Status updated.')]);
    }

    private function validateData(Request $request, ?Department $department = null): array
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:100', Rule::unique('departments', 'name')->ignore($department?->id)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }
}
