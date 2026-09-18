<?php

namespace Modules\Branch\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Branch\Models\Branch;

class BranchDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Branch::firstOrCreate(['code' => 'BR-001'], [
            'name'           => 'Main Branch',
            'code'           => 'BR-001',
            'phone'          => '01700-000000',
            'address'        => 'Dhaka, Bangladesh',
            'is_main'        => true,
            'is_active'      => true,
            'is_pos_enabled' => true,
            'is_ecom_enabled' => true,
        ]);
    }
}
