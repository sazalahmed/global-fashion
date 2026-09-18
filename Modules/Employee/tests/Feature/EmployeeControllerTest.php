<?php

namespace Modules\Employee\Tests\Feature;

use Modules\Branch\Models\Branch;
use Modules\Employee\Models\Department;
use Modules\Employee\Models\Designation;
use Modules\Employee\Models\Employee;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
{
    protected Branch $branch;
    protected Department $department;
    protected Designation $designation;

    protected function setUp(): void
    {
        parent::setUp();
        // hr.* routes are permission-gated; Super Admin bypasses all checks.
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']));
        $this->branch = Branch::create(['name' => 'Main', 'code' => 'BR-001', 'is_main' => true, 'is_active' => true, 'is_pos_enabled' => true, 'is_ecom_enabled' => false]);
        $this->department = Department::create(['name' => 'Sales', 'is_active' => true]);
        $this->designation = Designation::create(['name' => 'Manager', 'is_active' => true]);
    }

    public function test_index_renders(): void
    {
        $this->actingAsAdmin()->get(route('employee.index'))->assertStatus(200);
    }

    public function test_create_renders(): void
    {
        $this->actingAsAdmin()->get(route('employee.create'))->assertStatus(200);
    }

    public function test_store_creates_employee(): void
    {
        $this->actingAsAdmin()->post(route('employee.store'), [
            'name' => 'John Doe',
            'phone' => '01700000001',
            'salary' => 25000,
            'status' => 'active',
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'branch_id' => $this->branch->id,
            'joining_date' => now()->format('Y-m-d'),
        ])->assertRedirect();
        $this->assertDatabaseHas('employees', ['name' => 'John Doe']);
    }

    public function test_show_displays_employee(): void
    {
        $employee = Employee::create([
            'employee_id' => 'EMP-9001',
            'name' => 'Test Employee', 'phone' => '01700000001',
            'salary' => 20000, 'status' => 'active',
            'department_id' => $this->department->id, 'designation_id' => $this->designation->id,
            'created_by' => $this->admin->id,
        ]);
        $this->actingAsAdmin()->get(route('employee.show', $employee))->assertStatus(200);
    }

    public function test_destroy_deletes_employee(): void
    {
        $employee = Employee::create([
            'employee_id' => 'EMP-9002',
            'name' => 'Delete Me', 'phone' => '01700000002',
            'salary' => 15000, 'status' => 'active',
            'department_id' => $this->department->id, 'designation_id' => $this->designation->id,
            'created_by' => $this->admin->id,
        ]);
        $this->actingAsAdmin()->delete(route('employee.destroy', $employee))->assertRedirect();
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }
}
