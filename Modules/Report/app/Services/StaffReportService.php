<?php

namespace Modules\Report\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Models\Attendance;
use Modules\Employee\Models\Employee;

class StaffReportService
{
    /**
     * Get staff report data with attendance summary.
     */
    public function getReport(array $filters = []): Collection
    {
        $query = Employee::query()
            ->select('employees.*')
            ->with('branch')
            ->when($filters['branch_id'] ?? null, fn($q, $b) => $q->where('branch_id', $b))
            ->when($filters['department'] ?? null, fn($q, $d) => $q->whereHas('departmentInfo', fn($dq) => $dq->where('name', $d)))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s), fn($q) => $q->where('status', 'active'));

        $employees = $query->orderBy('name')->get();

        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $filters['date_to'] ?? now()->toDateString();

        // Get attendance summary per employee for the date range
        $attendanceSummary = Attendance::select(
                'employee_id',
                DB::raw('COUNT(*) as total_days'),
                DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days"),
                DB::raw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days"),
                DB::raw("SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days"),
                DB::raw("SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_days"),
                DB::raw('COALESCE(SUM(hours_worked), 0) as total_hours'),
                DB::raw('COALESCE(SUM(late_minutes), 0) as total_late_minutes')
            )
            ->whereBetween('attendance_date', [$dateFrom, $dateTo])
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        // Merge attendance data into employee records
        return $employees->map(function ($employee) use ($attendanceSummary) {
            $att = $attendanceSummary->get($employee->id);

            $employee->present_days = $att->present_days ?? 0;
            $employee->absent_days = $att->absent_days ?? 0;
            $employee->late_days = $att->late_days ?? 0;
            $employee->leave_days = $att->leave_days ?? 0;
            $employee->total_hours = $att->total_hours ?? 0;
            $employee->total_late_minutes = $att->total_late_minutes ?? 0;
            $employee->total_attendance_days = $att->total_days ?? 0;

            return $employee;
        });
    }

    /**
     * Get summary stats for the staff report.
     */
    public function getStats(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $filters['date_to'] ?? now()->toDateString();

        $totalEmployees = Employee::where('status', 'active')
            ->when($filters['branch_id'] ?? null, fn($q, $b) => $q->where('branch_id', $b))
            ->when($filters['department'] ?? null, fn($q, $d) => $q->whereHas('departmentInfo', fn($dq) => $dq->where('name', $d)))
            ->count();

        $todayAttendance = Attendance::where('attendance_date', now()->toDateString())
            ->when($filters['branch_id'] ?? null, fn($q, $b) => $q->where('branch_id', $b));

        $presentToday = (clone $todayAttendance)->whereIn('status', ['present', 'late'])->count();
        $onLeaveToday = (clone $todayAttendance)->where('status', 'leave')->count();

        // Average attendance rate over the period
        $avgAttendance = 0;
        if ($totalEmployees > 0) {
            $totalPresent = Attendance::whereBetween('attendance_date', [$dateFrom, $dateTo])
                ->whereIn('status', ['present', 'late'])
                ->when($filters['branch_id'] ?? null, fn($q, $b) => $q->where('branch_id', $b))
                ->count();

            $workingDays = max(1, now()->parse($dateFrom)->diffInWeekdays(now()->parse($dateTo)) + 1);
            $avgAttendance = round(($totalPresent / ($totalEmployees * $workingDays)) * 100, 1);
        }

        return [
            'total_employees' => $totalEmployees,
            'present_today'   => $presentToday,
            'on_leave_today'  => $onLeaveToday,
            'avg_attendance'  => min(100, $avgAttendance) . '%',
        ];
    }

    /**
     * Get unique departments for filter dropdown.
     */
    public function getDepartments(): Collection
    {
        return \Modules\Employee\Models\Department::orderBy('sort_order')->orderBy('name')->pluck('name');
    }
}
