# Expense Module

## Overview
Manages business expenses including expense tracking, categorization, vendor management, approval workflows, recurring expenses, and vendor due/advance payments.

## Controllers
- **ExpenseController** -- CRUD for expenses, approval/rejection, mark as paid, and ledger views.
- **ExpenseVendorController** -- CRUD for expense vendors, pay due, and add advance operations.

## Models
- **Expense** -- Individual expense records with amount, category, vendor, date, status (pending/approved/rejected/paid), and supporting documents.
- **ExpenseCategory** -- Hierarchical categorization of expenses (e.g., Rent, Utilities, Transport, Office Supplies).
- **ExpenseVendor** -- Vendors/suppliers for recurring expenses with balance tracking (due and advance amounts).
- **RecurringExpense** -- Scheduled recurring expenses with frequency, next due date, and auto-generation settings.

## Services
- **ExpenseService** -- Business logic for expense creation, approval workflow, payment processing, recurring expense generation, and ledger calculations.
- **ExpenseVendorService** -- Vendor management, due payment processing, advance tracking, and vendor ledger.

## Routes
| Method | Route | Description |
|--------|-------|-------------|
| Resource | `/expenses` | Expense CRUD |
| PATCH | `/expenses/{id}/approve` | Approve an expense |
| PATCH | `/expenses/{id}/reject` | Reject an expense |
| PATCH | `/expenses/{id}/mark-paid` | Mark expense as paid |
| GET | `/expenses/ledger` | Expense ledger report |
| Resource | `/expense-vendors` | Expense vendor CRUD |
| POST | `/expense-vendors/{id}/pay-due` | Pay vendor due amount |
| POST | `/expense-vendors/{id}/add-advance` | Add advance to vendor |
| Resource | `/recurring-expenses` | Recurring expense CRUD |

## Settings / Configuration
No module-specific settings. Expense categories and approval rules are managed within the module.

## Dependencies
- **Payment** -- Expense payments are recorded through the Payment module.
- **Accounting** -- Expenses post journal entries for financial reporting.
