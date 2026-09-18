# Employee Advances + Ledger (Group E1)

**Date:** 2026-07-08
**Module:** `Modules/Employee`
**Status:** Approved

## Context
- `employees.advance_balance` already exists; `adjustAdvanceBalance()` mutates it but records nothing.
- Payroll auto-deducts advance (≤50% of basic) — revisited in E4.
- Print is already on the employee list via the shared export dropdown (Group B) — no work needed.

## Schema (one migration)
`employee_advances`: id, employee_id FK cascade, advance_number unique,
type enum('advance','recovery'), amount decimal(15,2), advance_date date,
payment_account_id nullable, reference nullable, note nullable,
journal_entry_id nullable, created_by, timestamps, softDeletes, index(employee_id).

## Model
`EmployeeAdvance`: fillable, casts (amount decimal, advance_date date),
`employee()`, `paymentAccount()`, `creator()`.

## Service — `EmployeeAdvanceService`
- `recordAdvance(Employee, data)`: create txn (advance), JE DR 1015 / CR Cash-Bank,
  `advance_balance += amount`.
- `recordRecovery(Employee, data)`: validate ≤ advance_balance; create txn (recovery),
  JE DR Cash-Bank / CR 1015, `advance_balance -= amount`.
- `getLedger(Employee)`: running balance (advance +, recovery −).
- `generateNumber()` → `EMPADV-YYYY-####`. Cash-account resolution via payment-account type map.
- Reuses account `1015 — Loans & Advances (Receivable)` (seeded in Group D).

## Controller + routes (employee.* names)
- POST `employees/{employee}/advance` → giveAdvance
- POST `employees/{employee}/advance-recovery` → recordRecovery
- GET  `employees/{employee}/advance-ledger` → advanceLedger

## Views
- `show.blade.php`: advance summary (balance) + Give-Advance & Record-Recovery modals + link to ledger.
- `advance-ledger.blade.php`: per-employee running ledger with totals footer.

## Verification
- Give advance 10k → advance_balance +10k; JE DR 1015 = CR cash.
- Recovery 4k → balance −4k; JE DR cash = CR 1015.
- Over-recovery rejected.
- Ledger running balance correct; pages 200.
