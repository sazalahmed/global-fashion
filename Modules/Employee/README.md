# Employee Module

## Overview
Manages employee records including personal details, job information, branch assignment, salary configuration, and employment status.

## Controllers
- **EmployeeController** -- CRUD operations for employee records.

## Models
- **Employee** -- Represents an employee with personal info, contact details, designation, department, branch assignment, joining date, and status.

## Services
- **EmployeeService** -- Business logic for creating, updating, and managing employee records, including branch assignment and status changes.

## Routes
| Method | Route | Description |
|--------|-------|-------------|
| GET | `/employees` | List all employees |
| GET | `/employees/create` | Create employee form |
| POST | `/employees` | Store new employee |
| GET | `/employees/{id}` | View employee details |
| GET | `/employees/{id}/edit` | Edit employee form |
| PUT | `/employees/{id}` | Update employee |
| DELETE | `/employees/{id}` | Delete employee |

## Settings / Configuration
No module-specific settings. Employee-related configurations (departments, designations) are managed within the module or via the Setting module.

## Dependencies
- **Branch** -- Employees are assigned to branches.
- **Attendance** -- Attendance tracking is linked to employee records.
- **Payroll** -- Salary structures and payroll processing reference employees.
