<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Attendance\Http\Requests\StoreAttendanceRequest;
use Modules\Attendance\Http\Requests\StoreLeaveRequest;
use Modules\Attendance\Models\Leave;
use Modules\Attendance\Services\AttendanceService;
use Modules\Branch\Models\Branch;
use Modules\Employee\Models\Employee;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $service,
    ) {}

    public function index(Request $request)
    {
        bpAuthorize('hr.view');

        // The page reports one day. Without a date the stats described today
        // while the table listed every record ever taken, so the cards and the
        // rows below them disagreed.
        $date = $request->date ?: now()->toDateString();

        $stats = $this->service->getStats($date);
        $roster = $this->service->dailyRoster(
            $date,
            $request->only(['employee_id', 'branch_id', 'status', 'search']),
        );
        $employees = Employee::active()->orderBy('name')->get(['id', 'name', 'employee_id']);
        $branches = Branch::where('is_active', true)->get();

        return view('attendance::index', compact('stats', 'roster', 'employees', 'branches', 'date'));
    }

    /**
     * Every attendance record for one employee, with running totals.
     */
    public function employeeLedger(Request $request, Employee $employee)
    {
        bpAuthorize('hr.view');

        $ledger = $this->service->employeeLedger(
            $employee->id,
            $request->only(['date_from', 'date_to', 'status']),
        );

        return view('attendance::employee-ledger', [
            'employee' => $employee,
            'entries'  => $ledger['entries'],
            'summary'  => $ledger['summary'],
        ]);
    }

    public function create(Request $request)
    {
        bpAuthorize('hr.create');
        $date = $request->date ?? now()->toDateString();
        $branchId = $request->branch_id;
        $employees = Employee::active()
            ->when($branchId, fn($q, $b) => $q->where('branch_id', $b))
            ->with('branch')
            ->orderBy('name')
            ->get();
        $branches = Branch::where('is_active', true)->get();

        // Get existing attendance for this date
        $existing = \Modules\Attendance\Models\Attendance::whereDate('attendance_date', $date)
            ->when($branchId, fn($q, $b) => $q->where('branch_id', $b))
            ->get()
            ->keyBy('employee_id');

        $carbonDate = \Illuminate\Support\Carbon::parse($date);
        $isWeekend = in_array($carbonDate->dayOfWeek, $this->service->weekendDayNumbers(), true);
        $isHoliday = $this->service->isHoliday($carbonDate, $this->service->activeHolidays($branchId));
        $isOffDay = $isWeekend || $isHoliday;

        // Default check-in/out to the configured shift window so a new mark
        // matches the Attendance Configuration; saved records still win in the view.
        $shiftStart = \Modules\Setting\Models\Setting::get('attendance', 'shift_start', '09:00');
        $shiftEnd = \Modules\Setting\Models\Setting::get('attendance', 'shift_end', '18:00');

        return view('attendance::create', compact('employees', 'branches', 'date', 'branchId', 'existing', 'isWeekend', 'isHoliday', 'isOffDay', 'shiftStart', 'shiftEnd'));
    }

    public function store(StoreAttendanceRequest $request)
    {
        bpAuthorize('hr.create');
        $isOffDay = $this->service->isOffDay(
            \Illuminate\Support\Carbon::parse($request->input('attendance_date')),
            $request->input('branch_id'),
        );

        $count = $this->service->bulkMarkAttendance(
            $request->input('records'),
            $request->input('attendance_date'),
            $request->input('branch_id'),
            $isOffDay,
        );

        $msg = $isOffDay
            ? "Overtime recorded for {$count} employee(s)."
            : "Attendance marked for {$count} employees.";

        return redirect()->route('attendance.index')->with('success', $msg);
    }

    public function report(Request $request)
    {
        bpAuthorize('hr.view');
        $month = $request->month ?? now()->format('Y-m');
        $branchId = $request->branch_id;
        $report = $this->service->getMonthlyReport($month, $branchId);
        $branches = Branch::where('is_active', true)->get();

        return view('attendance::report', compact('report', 'month', 'branchId', 'branches'));
    }

    // Leave Management
    public function leave(Request $request)
    {
        bpAuthorize('hr.view');
        $leaves = $this->service->leaveList(
            $request->only(['employee_id', 'status', 'leave_type']),
        );
        $employees = Employee::active()->orderBy('name')->get(['id', 'name', 'employee_id']);

        return view('attendance::leave', compact('leaves', 'employees'));
    }

    public function leaveCreate()
    {
        bpAuthorize('hr.create');
        $employees = Employee::active()->orderBy('name')->get(['id', 'name', 'employee_id']);
        $weekendDayNumbers = $this->service->weekendDayNumbers();
        $leaveTypes = \Modules\Attendance\Models\LeaveType::active()->orderBy('name')->get();

        return view('attendance::leave-create', compact('employees', 'weekendDayNumbers', 'leaveTypes'));
    }

    /**
     * Leave balance per active leave type for an employee (this calendar year):
     * allowed (max_days), taken (sum of approved leave days), and remaining.
     */
    public function leaveBalance(Employee $employee)
    {
        bpAuthorize('hr.view');
        $year = now()->year;

        $taken = Leave::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->selectRaw('leave_type, SUM(total_days) as days')
            ->groupBy('leave_type')
            ->pluck('days', 'leave_type');

        $balance = \Modules\Attendance\Models\LeaveType::active()
            ->orderBy('name')
            ->get()
            ->map(function ($type) use ($taken) {
                $used = (int) ($taken[$type->code] ?? 0);

                return [
                    'type'      => $type->code,
                    'name'      => $type->name,
                    'allowed'   => $type->max_days,
                    'taken'     => $used,
                    'remaining' => max(0, $type->max_days - $used),
                    'unlimited' => $type->max_days === 0,
                ];
            })
            ->values();

        return response()->json($balance);
    }

    public function leaveStore(StoreLeaveRequest $request)
    {
        bpAuthorize('hr.create');
        $leave = $this->service->applyLeave($request->validated());

        return redirect()->route('attendance.leave')
            ->with('success', __('Leave application submitted.'));
    }

    public function leaveApprove(Leave $leave)
    {
        bpAuthorize('hr.edit');
        $this->service->approveLeave($leave);

        return back()->with('success', __('Leave approved.'));
    }

    public function leaveReject(Request $request, Leave $leave)
    {
        bpAuthorize('hr.edit');
        $this->service->rejectLeave($leave, $request->input('rejection_reason'));

        return back()->with('success', __('Leave rejected.'));
    }
}
