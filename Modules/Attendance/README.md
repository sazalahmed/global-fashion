# Attendance Module

## Overview
Employee attendance tracking, leave management, and work schedule configuration. Handles clock-in/out records, leave applications and approvals, weekend definitions, and holiday calendars.

## Controllers
- **AttendanceController** - Manage attendance records, leave applications, and leave approvals
- **AttendanceConfigController** - Configure weekend days, holidays, and work hour settings

## Models
- **Attendance** - Daily attendance records (employee, date, check-in/out times, status, hours worked)
- **Leave** - Leave applications (employee, leave type, date range, status, approval)
- **WeekendDay** - Configured weekend days for the organization
- **Holiday** - Company holidays and public holidays calendar

## Services
- **AttendanceService** - Business logic for attendance calculation, leave balance tracking, and schedule validation

## Routes
- **Attendance Records** - View, create, and edit daily attendance entries
- **Leave Management** - Apply for leave, approve/reject leave requests, view leave balances
- **Holiday/Weekend Config** - Set weekend days and manage holiday calendar

## Settings / Configuration
- **Weekend days** - Configurable days off (default: Friday, Saturday for Bangladesh)
- **Holidays** - Public and company holidays
- **Work hours** - Standard daily work hours and overtime thresholds

## Dependencies
- **Employee** - Attendance records are linked to employees
- **Payroll** - Attendance data feeds into salary calculations (absences, overtime)
