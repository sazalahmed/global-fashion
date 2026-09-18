<?php

namespace Modules\Unit\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Unit\Models\Unit;

class UnitSeeder extends Seeder
{
    /**
     * Seed the default units.
     */
    public function run(): void
    {
        $baseUnits = [
            ['name' => 'Piece', 'short_name' => 'Pcs', 'allow_decimal' => false],
            // ['name' => 'Kilogram', 'short_name' => 'Kg', 'allow_decimal' => true],
            ['name' => 'Dozen', 'short_name' => 'Dz', 'allow_decimal' => false],
            ['name' => 'Box', 'short_name' => 'Box', 'allow_decimal' => false],
            ['name' => 'Packet', 'short_name' => 'Pkt', 'allow_decimal' => false],
            ['name' => 'Meter', 'short_name' => 'm', 'allow_decimal' => true],
            ['name' => 'Carton', 'short_name' => 'Ctn', 'allow_decimal' => false],
        ];

        foreach ($baseUnits as $unit) {
            Unit::firstOrCreate(
                ['name' => $unit['name']],
                $unit + ['status' => 'active']
            );
        }

        $kg = Unit::where('name', 'Kilogram')->first();
        $litre = Unit::where('name', 'Litre')->first();
        $meter = Unit::where('name', 'Meter')->first();

        $subUnits = [
            [
                'name' => 'Gram',
                'short_name' => 'g',
                'base_unit_id' => $kg?->id,
                'conversion_factor' => 0.001,
                'allow_decimal' => true,
            ],
            [
                'name' => 'Millilitre',
                'short_name' => 'ml',
                'base_unit_id' => $litre?->id,
                'conversion_factor' => 0.001,
                'allow_decimal' => true,
            ],
            [
                'name' => 'Centimeter',
                'short_name' => 'cm',
                'base_unit_id' => $meter?->id,
                'conversion_factor' => 0.01,
                'allow_decimal' => true,
            ],
        ];

        foreach ($subUnits as $unit) {
            Unit::firstOrCreate(
                ['name' => $unit['name']],
                $unit + ['status' => 'active']
            );
        }
    }
}
