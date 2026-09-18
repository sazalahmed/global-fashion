<?php

namespace Modules\Employee\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Employee\Models\Department;
use Modules\Employee\Models\Designation;
use Modules\Employee\Models\Employee;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'employee_id' => 'EMP-' . str_pad(fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'name' => fake()->name(),
            'phone' => fake()->numerify('01#########'),
            'email' => fake()->unique()->safeEmail(),
            'department_id' => Department::firstOrCreate(['name' => fake()->randomElement(['Sales', 'Accounting', 'Warehouse', 'Admin'])])->id,
            'designation_id' => Designation::firstOrCreate(['name' => fake()->jobTitle()])->id,
            'salary' => fake()->numberBetween(15000, 80000),
            'joining_date' => fake()->dateTimeBetween('-2 years', '-1 month'),
            'status' => 'active',
        ];
    }
}
