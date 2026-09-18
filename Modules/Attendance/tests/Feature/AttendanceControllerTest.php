<?php

namespace Modules\Attendance\Tests\Feature;

use Tests\TestCase;

class AttendanceControllerTest extends TestCase
{
    public function test_index_renders(): void
    {
        $this->actingAsAdmin()->get(route('attendance.index'))->assertStatus(200);
    }

    public function test_create_renders(): void
    {
        $this->actingAsAdmin()->get(route('attendance.create'))->assertStatus(200);
    }

    public function test_leave_renders(): void
    {
        $this->actingAsAdmin()->get(route('attendance.leave'))->assertStatus(200);
    }
}
