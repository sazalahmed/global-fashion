<?php

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Ecommerce\Models\ShippingZone;

class ShippingZoneSeeder extends Seeder
{
    /**
     * Seed the two default delivery zones used at checkout:
     *   - Inside Dhaka  → flat rate BDT 60
     *   - Outside Dhaka → flat rate BDT 120
     *
     * Idempotent: keyed on the English name so re-running updates rather
     * than duplicates.
     */
    public function run(): void
    {
        $zones = [
            ['name' => 'Inside Dhaka',  'bn_name' => 'ঢাকার মধ্যে',  'flat_rate' => 60,  'estimated_days' => 1],
            ['name' => 'Outside Dhaka', 'bn_name' => 'ঢাকার বাইরে', 'flat_rate' => 120, 'estimated_days' => 3],
        ];

        foreach ($zones as $zone) {
            ShippingZone::updateOrCreate(
                ['name' => $zone['name']],
                [
                    'bn_name'        => $zone['bn_name'],
                    'flat_rate'      => $zone['flat_rate'],
                    'estimated_days' => $zone['estimated_days'],
                    'is_active'      => true,
                ]
            );
        }

        $this->command->info('Seeded ' . count($zones) . ' shipping zones.');
    }
}
