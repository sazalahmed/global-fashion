<?php

namespace Modules\Variant\Database\Seeders;

use Illuminate\Database\Seeder;

class VariantDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            VariantAttributeSeeder::class,
        ]);
    }
}
