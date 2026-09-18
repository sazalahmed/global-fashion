<?php

namespace Modules\Manufacturing\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\Account;

class ManufacturingAccountsSeeder extends Seeder
{
    /**
     * Seed manufacturing-specific chart of accounts.
     *
     * Uses updateOrCreate by account_code so this seeder is idempotent
     * and can be safely re-run without duplicating records.
     */
    public function run(): void
    {
        $accounts = [
            // ── Assets (Current) ──
            [
                'account_code'       => '1021',
                'account_name'       => 'RM Supplier Advance',
                'account_type'       => 'asset',
                'sub_type'           => 'current_asset',
                'is_system'          => true,
                'opening_balance_type' => 'debit',
            ],
            [
                'account_code'       => '1030',
                'account_name'       => 'Raw Material Inventory',
                'account_type'       => 'asset',
                'sub_type'           => 'current_asset',
                'is_system'          => true,
                'opening_balance_type' => 'debit',
            ],
            [
                'account_code'       => '1035',
                'account_name'       => 'Work-in-Progress',
                'account_type'       => 'asset',
                'sub_type'           => 'current_asset',
                'is_system'          => true,
                'opening_balance_type' => 'debit',
            ],
            [
                'account_code'       => '1040',
                'account_name'       => 'Factory Receivable',
                'account_type'       => 'asset',
                'sub_type'           => 'current_asset',
                'is_system'          => true,
                'opening_balance_type' => 'debit',
            ],
            [
                'account_code'       => '1045',
                'account_name'       => 'Factory Advance',
                'account_type'       => 'asset',
                'sub_type'           => 'current_asset',
                'is_system'          => true,
                'opening_balance_type' => 'debit',
            ],

            // ── Liabilities (Current) ──
            [
                'account_code'       => '2020',
                'account_name'       => 'Factory Payable',
                'account_type'       => 'liability',
                'sub_type'           => 'current_liability',
                'is_system'          => true,
                'opening_balance_type' => 'credit',
            ],

            // ── Expenses ──
            [
                'account_code'       => '5010',
                'account_name'       => 'Manufacturing Cost',
                'account_type'       => 'expense',
                'sub_type'           => 'cost_of_sales',
                'is_system'          => true,
                'opening_balance_type' => 'debit',
            ],
            [
                'account_code'       => '5015',
                'account_name'       => 'Damage Loss',
                'account_type'       => 'expense',
                'sub_type'           => 'operating_expense',
                'is_system'          => true,
                'opening_balance_type' => 'debit',
            ],
            [
                'account_code'       => '5020',
                'account_name'       => 'Raw Material Waste Loss',
                'account_type'       => 'expense',
                'sub_type'           => 'operating_expense',
                'is_system'          => true,
                'opening_balance_type' => 'debit',
            ],
            [
                'account_code'       => '5025',
                'account_name'       => 'Product Waste Loss',
                'account_type'       => 'expense',
                'sub_type'           => 'operating_expense',
                'is_system'          => true,
                'opening_balance_type' => 'debit',
            ],
        ];

        foreach ($accounts as $data) {
            Account::updateOrCreate(
                ['account_code' => $data['account_code']],
                array_merge($data, ['status' => 'active'])
            );
        }
    }
}
