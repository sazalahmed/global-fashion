<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $rows = [
            // Pure investors' contributed capital — money the business owes back,
            // so it lives on the liability side. Shareholders use Owner's Capital
            // (3001) directly, no separate account needed for the pool.
            ['account_code' => '2030', 'account_name' => 'Investor Capital (Liability)', 'account_type' => 'liability', 'sub_type' => 'long_term_liability', 'is_system' => true, 'opening_balance_type' => 'credit', 'status' => 'active'],

            // Profit-share paid out to pure investors. Recognized as expense
            // because it reduces operating profit available to shareholders.
            ['account_code' => '5195', 'account_name' => 'Investor Profit Share', 'account_type' => 'expense', 'sub_type' => 'other_expense', 'is_system' => true, 'opening_balance_type' => 'debit', 'status' => 'active'],
        ];

        foreach ($rows as $row) {
            DB::table('accounts')->updateOrInsert(
                ['account_code' => $row['account_code']],
                $row + ['created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    public function down(): void
    {
        DB::table('accounts')->whereIn('account_code', ['2030', '5195'])->delete();
    }
};
