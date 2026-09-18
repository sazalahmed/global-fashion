# Department & Designation Management — Design

**Date:** 2026-07-09
**Module:** Employee
**Status:** Approved

## Problem

The employee create/edit forms expose **Department** and **Designation** dropdowns, but their options are hardcoded:

- Department reads `Modules\Employee\Models\Employee::DEPARTMENTS` (a PHP constant).
- Designation is a literal array inside `create.blade.php` / `edit.blade.php`.

There is no UI to create/manage these lists, so admins cannot add their own departments or designations.

## Goal

Let admins create and manage Departments and Designations, and populate the employee form dropdowns dynamically from that managed data.

## Decisions

- **Relationship:** Independent flat lists (a Designation is NOT tied to a Department; no cascading).
- **Management UI location:** Dedicated pages under the **Staff** sidebar section, mirroring the existing **Leave Types** pattern.
- **Storage on employees:** *(Revised after review — the string approach below was superseded.)* Store **foreign keys** `employees.department_id` / `employees.designation_id` referencing the managed tables, and expose the name via the relationship. To keep every existing read site and the mobile API contract intact, the model keeps `department` / `designation` as **name accessors** (backed by hidden, eager-loaded `departmentInfo` / `designationInfo` belongsTo relations and `$appends`), so `$employee->department` still returns the name string. A data migration backfills the FKs from the old name strings (creating any missing managed rows), then drops the string columns.

## Data model

Two new tables in the Employee module (both soft-deletable):

`departments`
- `id`
- `name` — string, unique
- `is_active` — boolean, default true
- `sort_order` — integer, default 0
- `timestamps`, `softDeletes`

`designations` — identical shape.

Models `Department` and `Designation`:
- `$fillable = ['name', 'is_active', 'sort_order']`
- cast `is_active => boolean`, `sort_order => integer`
- `scopeActive($q)` → `where('is_active', true)`

## Seeding

A seeder inserts the current hardcoded values so nothing disappears:
- Departments: Sales, Inventory, Accounts, Delivery, Management
- Designations: Manager, Supervisor, Salesperson, Cashier, Delivery Agent, Accountant

Idempotent via `firstOrCreate(['name' => ...])`.

## Management UI

Mirror the Leave Types pattern (index page with inline add/edit + delete + active toggle).

- Controllers: `DepartmentController`, `DesignationController`, each with `index`, `store`, `update`, `destroy`, `toggleStatus`.
- Permissions: reuse `hr.view` / `hr.create` / `hr.edit` / `hr.delete` (same as employees & leave types).
- Routes (Employee module `web.php`), names `departments.*` and `designations.*`.
- Views: `departments/index.blade.php`, `designations/index.blade.php` — table (Name · Status · Actions), inline create form, edit (modal or inline), delete-confirm, and `<x-core::status-toggle>` for active state.
- Validation: `name` required, string, max 100, unique (ignore self on update); `is_active` boolean; `sort_order` nullable integer.

## Employee form wiring

- `EmployeeController@create`, `@edit`, `@index` pass:
  - `$departments = Department::active()->orderBy('sort_order')->orderBy('name')->get()`
  - `$designations = Designation::active()->orderBy('sort_order')->orderBy('name')->get()`
- `create.blade.php` / `edit.blade.php`: replace the hardcoded Department loop and Designation array with `@foreach` over `$departments` / `$designations`, keeping the current selection matched by name (`old(...)` / `$employee->department|designation`).
- Employee index Department filter: replace `Employee::DEPARTMENTS` with the dynamic `$departments` list.
- `StoreEmployeeRequest`: department/designation stay `nullable|string|max:100`.

## Sidebar

Add "Departments" and "Designations" links under the Staff (HR) submenu, active-state driven by `request()->routeIs('departments.*')` / `designations.*`.

## Out of scope

- Cascading (designation filtered by department).
- Converting `employees.department/designation` to foreign keys.
- A mobile REST API for managing these lists (the API keeps returning the name strings unchanged).

## Backward compatibility

- Existing employee records keep their string department/designation values.
- `Employee::DEPARTMENTS` const usages are replaced by the dynamic list; the const may remain for the seeder or be removed once unused.
