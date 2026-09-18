<?php

namespace Modules\Payroll\Tests\Feature;

use Tests\TestCase;

class PayrollControllerTest extends TestCase
{
    public function test_index_renders(): void
    {
        $this->actingAsAdmin()->get(route('payroll.index'))->assertStatus(200);
    }

    public function test_generate_renders(): void
    {
        $this->actingAsAdmin()->get(route('payroll.generate'))->assertStatus(200);
    }

    public function test_salary_structures_renders(): void
    {
        $this->actingAsAdmin()->get(route('payroll.salary-structures'))->assertStatus(200);
    }
}
