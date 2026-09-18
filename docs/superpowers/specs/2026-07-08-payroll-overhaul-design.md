# Payroll Overhaul (Group E4)

**Date:** 2026-07-08
**Modules:** `Modules/Payroll` (+ reads Attendance overtime E3, Employee advances E1)
**Status:** Approved

## Decisions
- Full basic salary (no proration); **Absent** deduction auto-filled (absent days × daily
  rate), editable.
- Earnings side = Basic + **Overtime** + **Bonus** + **Commission** (editable inputs).
  Overtime auto-suggested = overtime hours × (basic ÷ working days ÷ 8), editable.
  Structure-component earnings/deductions are no longer applied on the grid (replaced by
  the explicit columns).
- Deductions = **Advance** (editable, labeled) + **Absent** (editable). Advance pre-filled
  from the employee's advance balance (capped so net ≥ 0).
- **Per-employee approval**: each row has Approve; editing locked once approved (unapprove
  to edit). Mark-Paid pays only approved, unpaid rows (can run repeatedly).
- Per-employee **payslip** with a printed **signature line**.

## Schema (migration on payroll_items)
Add: `overtime` dec(15,2) d0, `bonus` dec(15,2) d0, `commission` dec(15,2) d0,
`absent_deduction` dec(15,2) d0, `overtime_hours` dec(6,2) d0,
`status` string(20) d'pending' (pending|approved), `approved_at` timestamp null,
`approved_by` bigint null. (`advance_deduction`, `net_salary`, etc. already exist.)

## Calculation (per item)
- gross = basic_salary + overtime + bonus + commission
- total_deductions = advance_deduction + absent_deduction
- net_salary = gross − total_deductions (floored at 0)

## PayrollService
- `generatePayroll`: basic = full salary; absent_deduction = round(absent_days × salary/workingDays);
  overtime_hours + overtime (auto-suggest) from AttendanceService::getOvertimeSummary; bonus/commission 0;
  advance_deduction = min(advance_balance, gross − absent_deduction); status pending.
- `updateItem(item, data)`: only when item pending & payroll not paid; set overtime/bonus/
  commission/absent_deduction/advance_deduction (advance capped at advance_balance); recompute
  net + payroll totals.
- `approveItem` / `unapproveItem` (unapprove blocked once paid).
- `markAsPaid`: pay approved & unpaid items only. Per batch:
  - JE: DR Salary Expense (5110) = Σ(gross − absent); CR 1015 Loans&Advances = Σ advance;
    CR Cash/Bank = Σ net.
  - For each item with advance: decrement employee.advance_balance and insert an
    EmployeeAdvance `recovery` row (linked to the payroll JE, no extra JE) so the advance
    ledger stays accurate.
  - Mark those items payment_status=paid. Set payroll.status='paid' when no unpaid items remain.
- Recompute payroll totals helper after edits/approvals.

## Controller + routes (payroll.*)
- PUT  `payroll/items/{item}` → updateItem
- POST `payroll/items/{item}/approve` → approveItem
- POST `payroll/items/{item}/unapprove` → unapproveItem
- GET  `payroll/items/{item}/payslip` → payslip (printable, signature line)

## Views
- `show.blade.php`: editable grid — Employee | Basic | OT (hrs) | Bonus | Commission |
  Advance | Absent | Net | Status | Actions (Save / Approve / Unapprove / Payslip).
  Remove the Earnings column/breakdown. Inputs disabled once row approved. Mark-Paid pays approved.
- `payslip.blade.php`: per-employee payslip (business header, month, earnings/deductions
  breakdown, net, signature line "Employee Signature" + "Authorized Signature").

## Verification
- Generate → each item: full basic, auto absent/overtime/advance, pending.
- Edit overtime/bonus/commission/absent/advance → net recomputes; advance capped at balance.
- Approve locks row; unapprove re-opens.
- Mark-Paid pays only approved; JE balances (DR salary+? = CR cash+advance); advance_balance
  drops + EmployeeAdvance recovery row added; ledger accurate.
- Payslip renders with signature line.
- Regression: index totals, cancel/delete still work.
