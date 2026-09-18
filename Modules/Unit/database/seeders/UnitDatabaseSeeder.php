<?php

namespace Modules\Unit\Database\Seeders;

use Illuminate\Database\Seeder;

class UnitDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            UnitSeeder::class,
        ]);
    }
}
