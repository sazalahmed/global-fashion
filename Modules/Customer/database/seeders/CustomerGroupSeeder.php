<?php

namespace Modules\Customer\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Customer\Models\CustomerGroup;

class CustomerGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['name' => 'Retail', 'description' => 'Regular retail customers', 'discount_percentage' => 0],
            ['name' => 'Wholesale', 'description' => 'Bulk buyers with volume discounts', 'discount_percentage' => 5],
            ['name' => 'VIP', 'description' => 'Premium loyal customers', 'discount_percentage' => 10],
            ['name' => 'Corporate', 'description' => 'Business and corporate accounts', 'discount_percentage' => 8],
        ];

        foreach ($groups as $group) {
            CustomerGroup::firstOrCreate(
                ['name' => $group['name']],
                array_merge($group, ['is_active' => true])
            );
        }
    }
}
