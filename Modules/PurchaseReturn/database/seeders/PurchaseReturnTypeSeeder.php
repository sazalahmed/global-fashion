<?php

namespace Modules\PurchaseReturn\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\PurchaseReturn\Models\PurchaseReturnType;

class PurchaseReturnTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Defective', 'description' => 'Product has manufacturing defects'],
            ['name' => 'Wrong Item', 'description' => 'Received incorrect product'],
            ['name' => 'Expired', 'description' => 'Product expired or near expiry'],
            ['name' => 'Damaged in Transit', 'description' => 'Damaged during shipping/delivery'],
            ['name' => 'Quality Issue', 'description' => 'Does not meet quality standards'],
            ['name' => 'Overstock', 'description' => 'Excess inventory being returned'],
        ];

        foreach ($types as $type) {
            PurchaseReturnType::firstOrCreate(
                ['name' => $type['name']],
                array_merge($type, ['is_active' => true])
            );
        }
    }
}
