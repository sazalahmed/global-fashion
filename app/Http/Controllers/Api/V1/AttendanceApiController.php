<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Attendance\Services\AttendanceService;

class AttendanceApiController extends BaseApiController
{
    public function __construct(private readonly AttendanceService $service) {}

    public function index(Request $request): JsonResponse
    {
        return $this->paginatedSuccess($this->service->list($request->all(), $request->input('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $v = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'attendance_date' => 'required|date', 'status' => 'required|in:present,late,absent,half_day,on_leave',
            'check_in' => 'nullable|date_format:H:i', 'check_out' => 'nullable|date_format:H:i',
            'note' => 'nullable|string',
        ]);
        $v['branch_id'] = auth()->user()->branch_id;
        return $this->success($this->service->markAttendance($v), 'Attendance marked', 201);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $v = $request->validate([
            'date' => 'required|date', 'branch_id' => 'nullable|integer',
            'records' => 'required|array|min:1',
            'records.*.employee_id' => 'required|integer', 'records.*.status' => 'required|in:present,late,absent,half_day,on_leave',
        ]);
        $count = $this->service->bulkMarkAttendance($v['records'], $v['date'], $v['branch_id'] ?? null);
        return $this->success(['count' => $count], "Marked {$count} attendance records", 201);
    }

    public function summary(Request $request): JsonResponse
    {
        $v = $request->validate(['month' => 'required|string']);
        return $this->success($this->service->getMonthlyReport($v['month'], $request->input('branch_id')));
    }

    public function leaveList(Request $request): JsonResponse
    {
        return $this->paginatedSuccess($this->service->leaveList($request->all(), $request->input('per_page', 15)));
    }

    public function leaveStore(Request $request): JsonResponse
    {
        $v = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id', 'leave_type' => 'required|string|max:50',
            'start_date' => 'required|date', 'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);
        return $this->success($this->service->applyLeave($v), 'Leave applied', 201);
    }

    public function leaveApprove(int $id): JsonResponse
    {
        $leave = \Modules\Attendance\Models\Leave::findOrFail($id);
        return $this->success($this->service->approveLeave($leave), 'Leave approved');
    }

    public function leaveReject(Request $request, int $id): JsonResponse
    {
        $leave = \Modules\Attendance\Models\Leave::findOrFail($id);
        $reason = $request->input('reason');
        return $this->success($this->service->rejectLeave($leave, $reason), 'Leave rejected');
    }
}
