<?php

namespace Modules\Customer\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Customer\Models\Area;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = [
            'Dhaka', 'Chattogram', 'Rajshahi', 'Khulna',
            'Barishal', 'Sylhet', 'Rangpur', 'Mymensingh',
        ];

        foreach ($divisions as $name) {
            Area::firstOrCreate(
                ['name' => $name, 'level' => 'division'],
                ['is_active' => true]
            );
        }
    }
}
