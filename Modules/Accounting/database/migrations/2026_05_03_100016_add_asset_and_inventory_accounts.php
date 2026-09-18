<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Accounting\Models\Account;

return new class extends Migration {
    public function up(): void
    {
        $accounts = [
            ['account_code' => '1520', 'account_name' => 'Accumulated Depreciation', 'account_type' => 'asset', 'sub_type' => 'fixed_asset', 'is_system' => true, 'opening_balance_type' => 'credit'],
            ['account_code' => '4040', 'account_name' => 'Gain on Asset Disposal', 'account_type' => 'revenue', 'sub_type' => 'other_revenue', 'is_system' => true, 'opening_balance_type' => 'credit'],
            ['account_code' => '4050', 'account_name' => 'Inventory Gain (Stock Found)', 'account_type' => 'revenue', 'sub_type' => 'other_revenue', 'is_system' => true, 'opening_balance_type' => 'credit'],
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

    public function down(): void
    {
        Account::whereIn('account_code', ['1520', '4040', '4050', '5200', '5210'])->delete();
    }
};
