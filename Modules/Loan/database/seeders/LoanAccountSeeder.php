<?php

namespace Modules\Loan\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\Account;

class LoanAccountSeeder extends Seeder
{
    public function run(): void
    {
        Account::firstOrCreate(
            ['account_code' => '2100'],
            [
                'account_name' => 'Loan Payable',
                'account_type' => 'liability',
                'sub_type'     => 'current_liability',
                'description'  => 'Tracks all outstanding loan liabilities from external lenders.',
                'is_system'    => true,
                'status'       => 'active',
            ]
        );
    }
}
