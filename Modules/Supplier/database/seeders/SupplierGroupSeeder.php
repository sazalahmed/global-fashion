<?php

namespace Modules\Supplier\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Supplier\Models\SupplierGroup;

class SupplierGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['name' => 'Local Manufacturer', 'description' => 'Locally manufactured goods suppliers'],
            ['name' => 'Importer', 'description' => 'Import-based suppliers'],
            ['name' => 'Distributor', 'description' => 'Regional distributors'],
            ['name' => 'Wholesaler', 'description' => 'Wholesale bulk suppliers'],
        ];

        foreach ($groups as $group) {
            SupplierGroup::firstOrCreate(
                ['name' => $group['name']],
                array_merge($group, ['is_active' => true])
            );
        }
    }
}
