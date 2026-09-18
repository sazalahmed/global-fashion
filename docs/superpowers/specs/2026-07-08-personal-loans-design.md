# Personal Loans — Business Lends to a Borrower (Group D)

**Date:** 2026-07-08
**Module:** `Modules/Loan` (new Borrower + PersonalLoan flow), `Modules/Accounting` (account seed)
**Status:** Approved

## Decisions
- Mirror the Lender pattern with a **Borrower** entity (receivable side).
- **Running account**: flexible disbursements + repayments, any amount/date, running balance. No installments, interest, or schedule.
- Separate "Personal Loans" section (own pages + sidebar item).

## Schema (one migration)
- `borrowers`: id, name, phone, email, address, opening_balance decimal(15,2) default 0,
  total_lent decimal(15,2) default 0, total_recovered decimal(15,2) default 0,
  outstanding_balance decimal(15,2) default 0, status string default 'active',
  notes, created_by, timestamps, softDeletes.
- `personal_loan_transactions`: id, borrower_id FK cascade, txn_number unique,
  type enum('disbursement','repayment'), amount decimal(15,2), txn_date date,
  payment_account_id nullable, reference nullable, note nullable,
  journal_entry_id nullable, created_by, timestamps, softDeletes, index(borrower_id).
- Seed asset account `1015 — Loans & Advances (Receivable)` if absent.

Outstanding = opening_balance + total_lent − total_recovered.

## Models
- `Borrower`: fillable, casts (money decimals), `transactions()` hasMany, `creator()`,
  scopes active()/search(); helper to recompute outstanding.
- `PersonalLoanTransaction`: fillable, casts (amount decimal, txn_date date),
  `borrower()`, `paymentAccount()`, `creator()`.

## Service — `PersonalLoanService`
- Borrower CRUD (delete blocked if outstanding > 0).
- `recordDisbursement(Borrower, data)`: create txn (type disbursement), JE
  DR 1015 / CR Cash-Bank, `total_lent += amount`, recompute outstanding.
- `recordRepayment(Borrower, data)`: validate amount ≤ outstanding; create txn
  (type repayment), JE DR Cash-Bank / CR 1015, `total_recovered += amount`,
  recompute outstanding.
- `getLedger(Borrower, filters)`: opening balance row + txns asc by date with
  running balance (disbursement +, repayment −).
- `getStats()`: total borrowers, active, total_lent, total_recovered, total_outstanding.
- `generateTxnNumber()` → `PL-YYYY-####`.
- Cash-account resolution reuses the payment-account-type → code map (1001/1002/1004).

## Accounting
- Add a `Loans & Advances (Receivable)` asset account (code 1015) via migration seed.
- Disbursement JE source_type `personal_loan_disbursement`; repayment `personal_loan_repayment`.
- Both balanced (DR = CR).

## Controller + routes (`personal-loans` prefix, names `personal-loans.`)
- index, create, store, show, edit, update, destroy
- POST `{borrower}/disburse` → disburse
- POST `{borrower}/repay` → repay
- Permission: finance.view / finance.create / finance.edit / finance.delete.

## Views
- `personal-loans/index`: stat cards + borrower table (outstanding, totals row) + filter bar + export dropdown (module `personal-loans`? optional — skip export for now).
- `personal-loans/create`, `edit`: borrower form.
- `personal-loans/show`: borrower info + outstanding + running ledger table + two modals (Give Loan, Receive Payment).

## Sidebar
- Add "Personal Loans" under the existing Loans submenu (route `personal-loans.index`, active on `personal-loans.*`).

## Verification
- Create borrower → outstanding = opening_balance.
- Disburse 50k → outstanding +50k; JE DR 1015 = CR cash (balanced).
- Repay 20k → outstanding −20k; JE DR cash = CR 1015 (balanced).
- Over-repay rejected.
- Ledger running balance correct; delete blocked while outstanding > 0.
- Pages render 200; sidebar link active.
