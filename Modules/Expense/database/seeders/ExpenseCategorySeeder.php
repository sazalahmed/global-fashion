<?php

namespace Modules\Expense\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\Account;
use Modules\Expense\Models\ExpenseCategory;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Rent', 'account_code' => '5100', 'sort_order' => 1],
            ['name' => 'Utilities', 'account_code' => '5120', 'sort_order' => 2],
            ['name' => 'Salary & Wages', 'account_code' => '5110', 'sort_order' => 3],
            ['name' => 'Transport', 'account_code' => '5140', 'sort_order' => 4],
            ['name' => 'Office Supplies', 'account_code' => '5160', 'sort_order' => 5],
            ['name' => 'Marketing', 'account_code' => '5130', 'sort_order' => 6],
            ['name' => 'Maintenance', 'account_code' => '5160', 'sort_order' => 7],
            ['name' => 'Food & Refreshment', 'account_code' => '5160', 'sort_order' => 8],
            ['name' => 'Courier & Delivery', 'account_code' => '5140', 'sort_order' => 9],
        ];

        foreach ($categories as $data) {
            $account = Account::where('account_code', $data['account_code'])->first();

            ExpenseCategory::firstOrCreate(
                ['name' => $data['name']],
                [
                    'account_id' => $account?->id,
                    'is_active' => true,
                    'sort_order' => $data['sort_order'],
                ]
            );
        }
    }
}
