<?php

namespace Modules\AdSpend\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\Account;

class AdSpendAccountSeeder extends Seeder
{
    public function run(): void
    {
        Account::firstOrCreate(
            ['account_code' => '5510'],
            [
                'account_name' => 'Advertising & Marketing Expense',
                'account_type' => 'expense',
                'sub_type'     => 'operating_expense',
                'description'  => 'Tracks advertising costs across all digital platforms.',
                'is_system'    => true,
                'status'       => 'active',
            ]
        );
    }
}
