<?php

namespace Modules\Setting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Setting\Models\TaxRate;

class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            [
                'name'        => 'Standard VAT',
                'rate'        => 15,
                'type'        => 'percentage',
                'apply_to'    => 'All Products',
                'description' => 'NBR Standard Rate',
                'is_default'  => true,
                'is_active'   => true,
            ],
            [
                'name'        => 'Reduced VAT',
                'rate'        => 7.5,
                'type'        => 'percentage',
                'apply_to'    => 'Selected Categories',
                'description' => 'Selected categories',
                'is_default'  => false,
                'is_active'   => true,
            ],
            [
                'name'        => 'Special VAT',
                'rate'        => 5,
                'type'        => 'percentage',
                'apply_to'    => 'Services',
                'description' => 'Services',
                'is_default'  => false,
                'is_active'   => true,
            ],
            [
                'name'        => 'VAT Exempt',
                'rate'        => 0,
                'type'        => 'exempt',
                'apply_to'    => 'Essential Items',
                'description' => 'Essential items',
                'is_default'  => false,
                'is_active'   => true,
            ],
        ];

        foreach ($rates as $rate) {
            TaxRate::firstOrCreate(['name' => $rate['name']], $rate);
        }
    }
}
