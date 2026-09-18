<?php

namespace Modules\Expense\Database\Seeders;

use Illuminate\Database\Seeder;

class ExpenseDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ExpenseCategorySeeder::class,
        ]);
    }
}
