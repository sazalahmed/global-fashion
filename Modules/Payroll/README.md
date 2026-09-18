# Payroll Module

## Overview
Manages employee salary structures, payroll generation, approval workflows, and salary disbursement. Integrates with attendance data for deductions and overtime calculations.

## Controllers
- **PayrollController** -- Salary structure CRUD, payroll generation, approval, and disbursement.

## Models
- **Payroll** -- Monthly payroll run for a specific period with status (draft/approved/disbursed), total amount, and processing date.
- **PayrollItem** -- Individual employee entry within a payroll run, including gross salary, deductions, allowances, net pay, and attendance-based adjustments.
- **SalaryStructure** -- Defines the salary template assigned to employees, specifying base salary and applicable components.
- **SalaryStructureComponent** -- Individual components within a salary structure (basic, house rent, medical, transport, tax deduction, provident fund, etc.) with type (earning/deduction) and calculation method (fixed/percentage).

## Services
- **PayrollService** -- Business logic for salary structure management, payroll generation from attendance and salary data, approval workflow, disbursement processing, and payslip generation.

## Routes
| Method | Route | Description |
|--------|-------|-------------|
| Resource | `/payroll/salary-structures` | Salary structure CRUD |
| GET | `/payroll` | List payroll runs |
| POST | `/payroll/generate` | Generate payroll for a period |
| GET | `/payroll/{id}` | View payroll details |
| PATCH | `/payroll/{id}/approve` | Approve payroll |
| POST | `/payroll/{id}/disburse` | Disburse salaries |

## Settings / Configuration
No module-specific settings. Salary components and structures are configured within the module.

## Dependencies
- **Employee** -- Payroll is generated for employees based on their assigned salary structures.
- **Attendance** -- Attendance data drives deductions for absences and overtime calculations.
- **Payment** -- Salary disbursements are recorded as payments.
- **Accounting** -- Payroll posts journal entries for salary expenses and liabilities.
