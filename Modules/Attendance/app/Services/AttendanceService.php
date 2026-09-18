<?php

namespace Modules\Attendance\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\Holiday;
use Modules\Attendance\Models\Leave;
use Modules\Attendance\Models\WeekendDay;
use Modules\Employee\Models\Employee;

class AttendanceService
{
    /** Bangladesh default weekend: Friday (5) + Saturday (6). day_of_week 0=Sun..6=Sat. */
    public const DEFAULT_WEEKEND_DAYS = [5, 6];

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Attendance::with('employee', 'branch')
            ->when($filters['employee_id'] ?? null, fn($q, $e) => $q->where('employee_id', $e))
            ->when($filters['branch_id'] ?? null, fn($q, $b) => $q->where('branch_id', $b))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['date'] ?? null, fn($q, $d) => $q->whereDate('attendance_date', $d))
            ->when($filters['month'] ?? null, fn($q, $m) => $q->byMonth($m))
            ->orderByDesc('attendance_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Everyone on the payroll for one day, marked or not.
     *
     * Listing attendance rows alone hides the people who matter most — those
     * with nothing recorded — and, unfiltered, repeats an employee once per
     * historical date. Starting from the employee list and attaching that
     * day's record gives one row per person and makes "not marked" visible.
     */
    public function dailyRoster(string $date, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        // Qualified rather than the active() scope: both tables have a status
        // column, and the join makes an unqualified one ambiguous.
        $query = Employee::where('employees.status', 'active')
            ->leftJoin('attendances', function ($join) use ($date) {
                $join->on('attendances.employee_id', '=', 'employees.id')
                    ->whereDate('attendances.attendance_date', $date);
            })
            ->when($filters['branch_id'] ?? null, fn ($q, $b) => $q->where('employees.branch_id', $b))
            ->when($filters['employee_id'] ?? null, fn ($q, $e) => $q->where('employees.id', $e))
            ->when($filters['search'] ?? null, function ($q, $s) {
                $q->where(function ($w) use ($s) {
                    $w->where('employees.name', 'like', "%{$s}%")
                        ->orWhere('employees.employee_id', 'like', "%{$s}%");
                });
            })
            // 'not_marked' is a real thing to filter on here, and it is the
            // absence of a row rather than a status any row carries.
            ->when($filters['status'] ?? null, fn ($q, $s) => $s === 'not_marked'
                ? $q->whereNull('attendances.id')
                : $q->where('attendances.status', $s))
            ->orderBy('employees.name')
            ->select([
                'employees.*',
                'attendances.id as attendance_id',
                'attendances.check_in',
                'attendances.check_out',
                'attendances.hours_worked',
                'attendances.status as attendance_status',
                'attendances.late_minutes',
                'attendances.note as attendance_note',
            ]);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * One employee's attendance history, with the totals that make it
     * readable at a glance.
     */
    public function employeeLedger(int $employeeId, array $filters = [], int $perPage = 31): array
    {
        $base = Attendance::where('employee_id', $employeeId)
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->whereDate('attendance_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->whereDate('attendance_date', '<=', $d))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s));

        $summary = (clone $base)
            ->selectRaw("
                COUNT(*) AS records,
                COALESCE(SUM(status = 'present'), 0)  AS present,
                COALESCE(SUM(status = 'absent'), 0)   AS absent,
                COALESCE(SUM(status = 'late'), 0)     AS late,
                COALESCE(SUM(status = 'half_day'), 0) AS half_day,
                COALESCE(SUM(status = 'on_leave'), 0) AS on_leave,
                COALESCE(SUM(hours_worked), 0)        AS hours,
                COALESCE(SUM(late_minutes), 0)        AS late_minutes
            ")
            ->first();

        return [
            'entries' => (clone $base)->with('branch')
                ->orderByDesc('attendance_date')
                ->paginate($perPage)
                ->withQueryString(),
            'summary' => $summary,
        ];
    }

    public function markAttendance(array $data, bool $isOffDay = false): Attendance
    {
        $hoursWorked = 0;
        if (!empty($data['check_in']) && !empty($data['check_out'])) {
            $checkIn = Carbon::parse($data['check_in']);
            $checkOut = Carbon::parse($data['check_out']);
            // Carbon 3 diffs are signed; clamp so a missing/early checkout
            // can never produce negative worked hours.
            $hoursWorked = max(0, round($checkIn->diffInMinutes($checkOut) / 60, 2));
        }

        $data['hours_worked'] = $hoursWorked;

        // Minutes late = check-in time of day minus the configured shift start.
        $lateMinutes = 0;
        if (!empty($data['check_in'])) {
            $shift = Carbon::parse($this->shiftStart());
            $checkIn = Carbon::parse($data['check_in']);
            $lateMinutes = max(0, ($checkIn->hour * 60 + $checkIn->minute) - ($shift->hour * 60 + $shift->minute));
        }

        $data['late_minutes'] = $lateMinutes;

        // Overtime: whole day on a weekend/holiday; otherwise the hours worked
        // after the configured shift end on a normal working day.
        $overtimeHours = 0.0;
        if ($isOffDay) {
            $overtimeHours = $hoursWorked;
        } elseif (!empty($data['check_out'])) {
            $shiftEnd = Carbon::parse($this->shiftEnd());
            $checkOut = Carbon::parse($data['check_out']);
            $overtimeMinutes = ($checkOut->hour * 60 + $checkOut->minute) - ($shiftEnd->hour * 60 + $shiftEnd->minute);
            $overtimeHours = max(0, round($overtimeMinutes / 60, 2));
        }
        $data['overtime_hours'] = $overtimeHours;
        $data['is_overtime'] = $overtimeHours > 0;

        return Attendance::updateOrCreate(
            ['employee_id' => $data['employee_id'], 'attendance_date' => $data['attendance_date']],
            $data,
        );
    }

    /**
     * Mark many employees at once. On an off day (weekend/holiday) records left
     * as "off" (not worked) are skipped so non-working employees get no row —
     * only the employees explicitly marked are recorded, as overtime.
     */
    public function bulkMarkAttendance(array $records, string $date, ?int $branchId = null, bool $isOffDay = false): int
    {
        $count = 0;
        DB::transaction(function () use ($records, $date, $branchId, $isOffDay, &$count) {
            foreach ($records as $record) {
                if (($record['status'] ?? null) === 'off') {
                    continue; // employee did not work this off day
                }

                $this->markAttendance(array_merge($record, [
                    'attendance_date' => $date,
                    'branch_id' => $branchId ?? ($record['branch_id'] ?? null),
                ]), $isOffDay);
                $count++;
            }
        });

        return $count;
    }

    /**
     * Per-employee overtime totals for a month (hours + day count), from
     * attendance rows flagged as overtime. Used by payroll to suggest an
     * overtime amount.
     */
    public function getOvertimeSummary(string $month, ?int $branchId = null): Collection
    {
        return Attendance::byMonth($month)
            ->where('is_overtime', true)
            ->when($branchId, fn ($q, $b) => $q->where('branch_id', $b))
            ->selectRaw('employee_id, SUM(overtime_hours) as overtime_hours, COUNT(*) as overtime_days')
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');
    }

    public function getMonthlyReport(string $month, ?int $branchId = null): Collection
    {
        $employees = Employee::active()
            ->when($branchId, fn($q, $b) => $q->where('branch_id', $b))
            ->with(['branch'])
            ->get();

        $attendances = Attendance::byMonth($month)
            ->when($branchId, fn($q, $b) => $q->where('branch_id', $b))
            ->get()
            ->groupBy('employee_id');

        [$year, $monthNum] = array_map('intval', explode('-', $month));
        $workingDays = $this->getWorkingDaysInMonth($year, $monthNum, $branchId);

        return $employees->map(function ($employee) use ($attendances, $workingDays) {
            $records = $attendances->get($employee->id, collect());
            return [
                'employee' => $employee,
                'present' => $records->where('status', 'present')->count(),
                'absent' => $records->where('status', 'absent')->count(),
                'late' => $records->where('status', 'late')->count(),
                'half_day' => $records->where('status', 'half_day')->count(),
                'on_leave' => $records->where('status', 'on_leave')->count(),
                // hours_worked is stored clamped >= 0; sum is therefore never negative.
                'total_hours' => max(0, (float) $records->sum('hours_worked')),
                'working_days' => $workingDays,
            ];
        });
    }

    public function getStats(?string $date = null): array
    {
        $date = $date ?? Carbon::today()->toDateString();

        $total = Employee::active()->count();
        $byStatus = Attendance::whereDate('attendance_date', $date)
            ->selectRaw('status, COUNT(*) AS n')
            ->groupBy('status')
            ->pluck('n', 'status');

        return [
            'total_employees' => $total,
            'present' => (int) ($byStatus['present'] ?? 0),
            'absent' => (int) ($byStatus['absent'] ?? 0),
            'late' => (int) ($byStatus['late'] ?? 0),
            'half_day' => (int) ($byStatus['half_day'] ?? 0),
            'on_leave' => (int) ($byStatus['on_leave'] ?? 0),
            // Staff with nothing recorded for the day — the gap the roster
            // exists to surface. Never negative if a record outlives its
            // employee being deactivated.
            'not_marked' => max(0, $total - (int) $byStatus->sum()),
            'pending_leaves' => Leave::pending()->count(),
        ];
    }

    // Leave Management
    public function leaveList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Leave::with('employee', 'approver')
            ->when($filters['employee_id'] ?? null, fn($q, $e) => $q->where('employee_id', $e))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['leave_type'] ?? null, fn($q, $t) => $q->where('leave_type', $t))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function applyLeave(array $data): Leave
    {
        // Count only working days (exclude weekends + holidays) so payroll
        // and leave balances stay accurate.
        $data['total_days'] = $this->countWorkingDaysBetween($data['start_date'], $data['end_date']);

        return Leave::create($data);
    }

    public function approveLeave(Leave $leave): Leave
    {
        $leave->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);

        // Mark attendance as on_leave for the leave period
        $start = $leave->start_date;
        $end = $leave->end_date;
        while ($start->lte($end)) {
            Attendance::updateOrCreate(
                ['employee_id' => $leave->employee_id, 'attendance_date' => $start->toDateString()],
                ['status' => 'on_leave', 'note' => "Leave: {$leave->leave_type}"],
            );
            $start->addDay();
        }

        return $leave->fresh();
    }

    public function rejectLeave(Leave $leave, ?string $reason = null): Leave
    {
        $leave->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'rejection_reason' => $reason,
        ]);

        return $leave->fresh();
    }

    // ── Working-day / weekend / holiday calculation ──

    /**
     * Configured weekend day-of-week numbers (0=Sun..6=Sat), falling back to
     * the Bangladesh default (Fri+Sat) when none are configured.
     */
    public function weekendDayNumbers(): array
    {
        $days = WeekendDay::whereNull('branch_id')
            ->where('is_weekend', true)
            ->pluck('day_of_week')
            ->map(fn($d) => (int) $d)
            ->all();

        return $days ?: self::DEFAULT_WEEKEND_DAYS;
    }

    /**
     * Active holidays applicable to the given branch (branch-specific + global).
     */
    public function activeHolidays(?int $branchId = null): Collection
    {
        return Holiday::active()
            ->where(fn($q) => $q->whereNull('branch_id')->when($branchId, fn($q2) => $q2->orWhere('branch_id', $branchId)))
            ->get();
    }

    public function isHoliday(Carbon $date, Collection $holidays): bool
    {
        foreach ($holidays as $holiday) {
            $start = $holiday->start_date;
            $end = $holiday->end_date ?? $holiday->start_date;

            if ($holiday->is_recurring) {
                $md = $date->format('m-d');
                if ($md >= $start->format('m-d') && $md <= $end->format('m-d')) {
                    return true;
                }
            } elseif ($date->betweenIncluded($start, $end)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Count working days between two dates (inclusive), excluding configured
     * weekend days and active holidays.
     */
    public function countWorkingDaysBetween(string $startDate, string $endDate, ?int $branchId = null): int
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($end->lt($start)) {
            return 0;
        }

        $weekendDays = $this->weekendDayNumbers();
        $holidays = $this->activeHolidays($branchId);

        $count = 0;
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (in_array($date->dayOfWeek, $weekendDays, true)) {
                continue;
            }
            if ($this->isHoliday($date, $holidays)) {
                continue;
            }
            $count++;
        }

        return $count;
    }

    /**
     * Working days in a calendar month, excluding weekends + holidays.
     */
    public function getWorkingDaysInMonth(int $year, int $month, ?int $branchId = null): int
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return $this->countWorkingDaysBetween($start->toDateString(), $end->toDateString(), $branchId);
    }

    /**
     * Configured shift start time (HH:MM), used for late calculation.
     */
    public function shiftStart(): string
    {
        return (string) \Modules\Setting\Models\Setting::get('attendance', 'shift_start', '09:00');
    }

    /**
     * Configured shift end time (HH:MM), used for overtime calculation.
     */
    public function shiftEnd(): string
    {
        return (string) \Modules\Setting\Models\Setting::get('attendance', 'shift_end', '18:00');
    }

    /**
     * Whether a given date is a non-working day (weekend or holiday) for a branch.
     */
    public function isOffDay(Carbon $date, ?int $branchId = null): bool
    {
        if (in_array($date->dayOfWeek, $this->weekendDayNumbers(), true)) {
            return true;
        }

        return $this->isHoliday($date, $this->activeHolidays($branchId));
    }
}
