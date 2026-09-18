<?php

namespace Modules\Asset\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Asset\Models\AssetCategory;

class AssetCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Furniture & Fixtures', 'useful_life_years' => 10, 'depreciation_rate' => 10.00, 'depreciation_method' => 'straight_line'],
            ['name' => 'Computer & IT Equipment', 'useful_life_years' => 5, 'depreciation_rate' => 20.00, 'depreciation_method' => 'straight_line'],
            ['name' => 'Office Equipment', 'useful_life_years' => 7, 'depreciation_rate' => 14.29, 'depreciation_method' => 'straight_line'],
            ['name' => 'Vehicles', 'useful_life_years' => 8, 'depreciation_rate' => 12.50, 'depreciation_method' => 'declining_balance'],
            ['name' => 'Machinery & Tools', 'useful_life_years' => 10, 'depreciation_rate' => 10.00, 'depreciation_method' => 'straight_line'],
            ['name' => 'POS & Shop Equipment', 'useful_life_years' => 5, 'depreciation_rate' => 20.00, 'depreciation_method' => 'straight_line'],
            ['name' => 'Security & CCTV', 'useful_life_years' => 5, 'depreciation_rate' => 20.00, 'depreciation_method' => 'straight_line'],
            ['name' => 'Electrical & AC', 'useful_life_years' => 8, 'depreciation_rate' => 12.50, 'depreciation_method' => 'straight_line'],
            ['name' => 'Signage & Displays', 'useful_life_years' => 5, 'depreciation_rate' => 20.00, 'depreciation_method' => 'straight_line'],
            ['name' => 'Leasehold Improvements', 'useful_life_years' => 10, 'depreciation_rate' => 10.00, 'depreciation_method' => 'straight_line'],
        ];

        foreach ($categories as $cat) {
            AssetCategory::firstOrCreate(['name' => $cat['name']], $cat);
        }
    }
}
