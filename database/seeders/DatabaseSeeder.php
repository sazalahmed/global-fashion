<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // 1. Core: roles, permissions, admin user
            \Modules\Security\Database\Seeders\SecurityDatabaseSeeder::class,

            // 2. Accounting: chart of accounts
            \Modules\Accounting\Database\Seeders\AccountingDatabaseSeeder::class,

            // 3. Foundation: units, variant attributes
            \Modules\Unit\Database\Seeders\UnitDatabaseSeeder::class,
            \Modules\Variant\Database\Seeders\VariantDatabaseSeeder::class,

            // 4. Settings & expense categories
            // \Modules\Expense\Database\Seeders\ExpenseDatabaseSeeder::class,
            \Modules\Setting\Database\Seeders\SettingDatabaseSeeder::class,

            // 5. Location: districts & thanas (addresses, shipping zones depend on these)
            \Modules\Location\Database\Seeders\LocationDatabaseSeeder::class,

            // 6. Categories: Global Fashion category tree (products depend on it)
            // \Modules\Category\Database\Seeders\CategoryDatabaseSeeder::class,

            // 7. Products: depends on units and categories
            // \Modules\Product\Database\Seeders\ProductDatabaseSeeder::class,

            // 8. Inventory: opening stock (depends on products)
            // \Modules\Inventory\Database\Seeders\InventoryDatabaseSeeder::class,

            // 9. Ecommerce: homepage sections, storefront content, shipping zones
            \Modules\Ecommerce\Database\Seeders\EcommerceDatabaseSeeder::class,
        ]);
    }
}
