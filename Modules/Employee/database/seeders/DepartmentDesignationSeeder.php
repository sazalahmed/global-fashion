<?php

namespace Modules\Employee\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Employee\Models\Department;
use Modules\Employee\Models\Designation;

class DepartmentDesignationSeeder extends Seeder
{
    public function run(): void
    {
        $departments = ['Sales', 'Inventory', 'Accounts', 'Delivery', 'Management'];
        foreach ($departments as $i => $name) {
            Department::firstOrCreate(['name' => $name], ['sort_order' => $i, 'is_active' => true]);
        }

        $designations = ['Manager', 'Supervisor', 'Salesperson', 'Cashier', 'Delivery Agent', 'Accountant'];
        foreach ($designations as $i => $name) {
            Designation::firstOrCreate(['name' => $name], ['sort_order' => $i, 'is_active' => true]);
        }
    }
}
