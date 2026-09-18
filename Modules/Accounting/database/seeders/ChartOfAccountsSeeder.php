<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\Account;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // ── Assets ──
            ['account_code' => '1001', 'account_name' => 'Cash in Hand', 'account_type' => 'asset', 'sub_type' => 'current_asset', 'is_system' => true, 'opening_balance_type' => 'debit'],
            ['account_code' => '1002', 'account_name' => 'bKash Account', 'account_type' => 'asset', 'sub_type' => 'current_asset', 'is_system' => true, 'opening_balance_type' => 'debit'],
            ['account_code' => '1003', 'account_name' => 'Nagad Account', 'account_type' => 'asset', 'sub_type' => 'current_asset', 'is_system' => true, 'opening_balance_type' => 'debit'],
            ['account_code' => '1004', 'account_name' => 'Bank — DBBL', 'account_type' => 'asset', 'sub_type' => 'current_asset', 'is_system' => true, 'is_bank_account' => true, 'bank_name' => 'Dutch-Bangla Bank Limited', 'opening_balance_type' => 'debit'],
            ['account_code' => '1005', 'account_name' => 'Bank — BRAC', 'account_type' => 'asset', 'sub_type' => 'current_asset', 'is_system' => true, 'is_bank_account' => true, 'bank_name' => 'BRAC Bank Limited', 'opening_balance_type' => 'debit'],
            ['account_code' => '1010', 'account_name' => 'Accounts Receivable', 'account_type' => 'asset', 'sub_type' => 'current_asset', 'is_system' => true, 'opening_balance_type' => 'debit'],
            ['account_code' => '1020', 'account_name' => 'Inventory', 'account_type' => 'asset', 'sub_type' => 'current_asset', 'is_system' => true, 'opening_balance_type' => 'debit'],
            ['account_code' => '1025', 'account_name' => 'Advance Tax / AIT', 'account_type' => 'asset', 'sub_type' => 'current_asset', 'is_system' => true, 'opening_balance_type' => 'debit'],
            ['account_code' => '1500', 'account_name' => 'Shop Equipment', 'account_type' => 'asset', 'sub_type' => 'fixed_asset', 'is_system' => true, 'opening_balance_type' => 'debit'],
            ['account_code' => '1510', 'account_name' => 'Furniture & Fixtures', 'account_type' => 'asset', 'sub_type' => 'fixed_asset', 'is_system' => true, 'opening_balance_type' => 'debit'],
            ['account_code' => '1520', 'account_name' => 'Accumulated Depreciation', 'account_type' => 'asset', 'sub_type' => 'fixed_asset', 'is_system' => true, 'opening_balance_type' => 'credit'],

            // ── Liabilities ──
            ['account_code' => '2001', 'account_name' => 'Accounts Payable', 'account_type' => 'liability', 'sub_type' => 'current_liability', 'is_system' => true, 'opening_balance_type' => 'credit'],
            ['account_code' => '2010', 'account_name' => 'VAT Payable (15%)', 'account_type' => 'liability', 'sub_type' => 'current_liability', 'is_system' => true, 'opening_balance_type' => 'credit'],
            ['account_code' => '2015', 'account_name' => 'Salary Payable', 'account_type' => 'liability', 'sub_type' => 'current_liability', 'is_system' => true, 'opening_balance_type' => 'credit'],
            ['account_code' => '2500', 'account_name' => 'Bank Loan — City Bank', 'account_type' => 'liability', 'sub_type' => 'long_term_liability', 'is_system' => true, 'opening_balance_type' => 'credit'],

            // ── Equity ──
            ['account_code' => '3001', 'account_name' => "Owner's Capital", 'account_type' => 'equity', 'sub_type' => 'owner_equity', 'is_system' => true, 'opening_balance_type' => 'credit'],
            ['account_code' => '3010', 'account_name' => 'Retained Earnings', 'account_type' => 'equity', 'sub_type' => 'retained_earnings', 'is_system' => true, 'opening_balance_type' => 'credit'],
            ['account_code' => '3020', 'account_name' => "Owner's Drawing", 'account_type' => 'equity', 'sub_type' => 'owner_equity', 'is_system' => true, 'opening_balance_type' => 'debit'],

            // ── Revenue ──
            ['account_code' => '4001', 'account_name' => 'Sales Revenue', 'account_type' => 'revenue', 'sub_type' => 'operating_revenue', 'is_system' => true, 'opening_balance_type' => 'credit'],
            ['account_code' => '4010', 'account_name' => 'Sales Returns & Allowances', 'account_type' => 'revenue', 'sub_type' => 'operating_revenue', 'is_system' => true, 'opening_balance_type' => 'debit'],
            ['account_code' => '4020', 'account_name' => 'Service Income', 'account_type' => 'revenue', 'sub_type' => 'other_revenue', 'is_system' => true, 'opening_balance_type' => 'credit'],
            ['account_code' => '4030', 'account_name' => 'Discount Received', 'account_type' => 'revenue', 'sub_type' => 'other_revenue', 'is_system' => true, 'opening_balance_type' => 'credit'],
            ['account_code' => '4040', 'account_name' => 'Gain on Asset Disposal', 'account_type' => 'revenue', 'sub_type' => 'other_revenue', 'is_system' => true, 'opening_balance_type' => 'credit'],
            ['account_code' => '4050', 'account_name' => 'Inventory Gain (Stock Found)', 'account_type' => 'revenue', 'sub_type' => 'other_revenue', 'is_system' => true, 'opening_balance_type' => 'credit'],

            // ── Expenses ──
            ['account_code' => '5001', 'account_name' => 'Cost of Goods Sold', 'account_type' => 'expense', 'sub_type' => 'cost_of_sales', 'is_system' => true, 'opening_balance_type' => 'debit'],
            ['account_code' => '5100', 'account_name' => 'Rent Expense', 'account_type' => 'expense', 'sub_type' => 'operating_expense', 'is_system' => true, 'opening_balance_type' => 'debit'],
            ['account_code' => '5110', 'account_name' => 'Salary Expense', 'account_type' => 'expense', 'sub_type' => 'operating_expense', 'is_system' => true, 'opening_balance_type' => 'debit'],
            ['account_code' => '5120', 'account_name' => 'Utilities Expense', 'account_type' => 'expense', 'sub_type' => 'operating_expense', 'is_system' => true, 'opening_balance_type' => 'debit'],
            ['account_code' => '5130', 'account_name' => 'Marketing Expense', 'account_type' => 'expense', 'sub_type' => 'operating_expense', 'is_system' => true, 'opening_balance_type' => 'debit'],
            ['account_code' => '5140', 'account_name' => 'Courier & Delivery Expense', 'account_type' => 'expense', 'sub_type' => 'operating_expense', 'is_system' => true, 'opening_balance_type' => 'debit'],
            ['account_code' => '5150', 'account_name' => 'Depreciation Expense', 'account_type' => 'expense', 'sub_type' => 'operating_expense', 'is_system' => true, 'opening_balance_type' => 'debit'],
            ['account_code' => '5160', 'account_name' => 'Office Supplies', 'account_type' => 'expense', 'sub_type' => 'operating_expense', 'is_system' => true, 'opening_balance_type' => 'debit'],
            ['account_code' => '5170', 'account_name' => 'Bank Charges & Fees', 'account_type' => 'expense', 'sub_type' => 'other_expense', 'is_system' => true, 'opening_balance_type' => 'debit'],
            ['account_code' => '5180', 'account_name' => 'Discount Allowed', 'account_type' => 'expense', 'sub_type' => 'other_expense', 'is_system' => true, 'opening_balance_type' => 'debit'],
            ['account_code' => '5190', 'account_name' => 'Interest Expense', 'account_type' => 'expense', 'sub_type' => 'other_expense', 'is_system' => true, 'opening_balance_type' => 'debit'],
            ['account_code' => '5200', 'account_name' => 'Loss on Asset Disposal', 'account_type' => 'expense', 'sub_type' => 'other_expense', 'is_system' => true, 'opening_balance_type' => 'debit'],
            ['account_code' => '5210', 'account_name' => 'Inventory Shrinkage / Adjustment', 'account_type' => 'expense', 'sub_type' => 'other_expense', 'is_system' => true, 'opening_balance_type' => 'debit'],
        ];

        foreach ($accounts as $data) {
            Account::firstOrCreate(
                ['account_code' => $data['account_code']],
                array_merge($data, ['status' => 'active'])
            );
        }
    }
}
