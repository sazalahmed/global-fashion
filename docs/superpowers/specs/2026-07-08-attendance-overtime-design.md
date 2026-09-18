# Attendance — Weekend/Holiday Overtime (Group E3)

**Date:** 2026-07-08
**Module:** `Modules/Attendance`
**Status:** Approved

## Problem
On weekends/holidays the mark-attendance form defaults everyone to present and
records all employees. It should record ONLY employees explicitly marked (as
overtime) and leave others untouched. Extra time after shift end on a normal day
should also count as overtime.

## Schema (migration)
Add to `attendances`: `is_overtime` boolean default false, `overtime_hours` decimal(6,2) default 0.

## Off-day detection (controller)
`create()` already computes `$isWeekend`; add `$isHoliday` (via activeHolidays/isHoliday)
and `$isOffDay = $isWeekend || $isHoliday`; pass to the view.

## Marking logic (AttendanceService)
- `markAttendance($data)`: accept `is_overtime`. Compute `overtime_hours`:
  - off-day + present → overtime_hours = hours_worked, is_overtime = true.
  - normal day → overtime_hours = max(0, check_out − shift_end) in hours; is_overtime = overtime_hours > 0.
- `bulkMarkAttendance`: skip records whose status is `off` (not worked). Pass an
  `is_off_day` flag so overtime is set for the whole day on off-days.

## Create view
- Off-day: warning banner (only marked employees recorded as overtime); status
  select defaults to `off` with options Off / Present (Overtime); hide Mark-All buttons.
- Normal day: unchanged (existing statuses).

## Payroll hook (for E4)
- Add `AttendanceService::getOvertimeSummary(string $month, ?int $branchId)` returning
  per-employee overtime hours + overtime day count for the month (from is_overtime rows).
  E4 uses it to pre-fill a suggested overtime amount (editable).

## Verification
- On a weekend date: marking one employee Present(Overtime) creates 1 row is_overtime=1;
  others get no row.
- On a normal day with check_out after shift_end: overtime_hours computed.
- Monthly overtime summary returns correct per-employee totals.
- Regression: normal-day bulk marking still records present/absent as before.
