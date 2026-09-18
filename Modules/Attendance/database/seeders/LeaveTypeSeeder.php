<?php

namespace Modules\Attendance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Attendance\Models\LeaveType;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Casual Leave',    'code' => 'casual',    'max_days' => 10,  'paid' => true],
            ['name' => 'Sick Leave',      'code' => 'sick',      'max_days' => 14,  'paid' => true],
            ['name' => 'Annual Leave',    'code' => 'annual',    'max_days' => 15,  'paid' => true],
            ['name' => 'Maternity Leave', 'code' => 'maternity', 'max_days' => 120, 'paid' => true],
            ['name' => 'Paternity Leave', 'code' => 'paternity', 'max_days' => 7,   'paid' => true],
            ['name' => 'Unpaid Leave',    'code' => 'unpaid',    'max_days' => 0,   'paid' => false],
        ];

        foreach ($types as $type) {
            LeaveType::firstOrCreate(
                ['code' => $type['code']],
                $type + ['requires_approval' => true, 'is_active' => true],
            );
        }
    }
}
