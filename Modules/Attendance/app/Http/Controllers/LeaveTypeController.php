<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Attendance\Models\LeaveType;

class LeaveTypeController extends Controller
{
    public function index()
    {
        bpAuthorize('hr.view');
        $leaveTypes = LeaveType::orderBy('name')->get();

        return view('attendance::leave-types', compact('leaveTypes'));
    }

    public function store(Request $request)
    {
        bpAuthorize('hr.create');
        $data = $this->validateData($request);
        LeaveType::create($data);

        return back()->with('success', __('Leave type created.'));
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        bpAuthorize('hr.edit');
        $data = $this->validateData($request, $leaveType);
        $leaveType->update($data);

        return back()->with('success', __('Leave type updated.'));
    }

    public function destroy(LeaveType $leaveType)
    {
        bpAuthorize('hr.delete');
        $leaveType->delete();

        return back()->with('success', __('Leave type deleted.'));
    }

    public function toggleStatus(LeaveType $leaveType): \Illuminate\Http\JsonResponse
    {
        bpAuthorize('hr.edit');
        $leaveType->update(['is_active' => ! $leaveType->is_active]);

        return response()->json(['success' => true, 'is_active' => $leaveType->is_active, 'message' => __('Status updated.')]);
    }

    private function validateData(Request $request, ?LeaveType $leaveType = null): array
    {
        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:100'],
            'code'              => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('leave_types', 'code')->ignore($leaveType?->id)],
            'max_days'          => ['required', 'integer', 'min:0', 'max:365'],
            'paid'              => ['nullable', 'boolean'],
            'requires_approval' => ['nullable', 'boolean'],
            'is_active'         => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtolower($validated['code']);
        $validated['paid'] = $request->boolean('paid');
        $validated['requires_approval'] = $request->boolean('requires_approval');
        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }
}
