<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Accounting\Models\Account;

/**
 * Every expense category (Modules\Expense\Models\ExpenseCategory) has
 * account_id = NULL — none were ever mapped to a GL account — so
 * ExpenseService::create() has been falling back to "the first expense-type
 * account by id", which happens to be 5200 Loss on Asset Disposal. That
 * misfiled 65 real expense payments (rent, utilities, transportation, ads)
 * as one-off asset-disposal losses.
 *
 * This adds a proper catch-all "General Operating Expenses" account for
 * that fallback to use instead, and reclassifies Bank Charges & Fees from
 * other_expense to operating_expense — both per explicit instruction on
 * what should count as Operating Expenses (Expenses + Salary + Bank
 * Charges + Courier Charges) in the P&L.
 *
 * Deliberately does NOT touch the 65 existing journal_entry_lines under
 * 5200 — by instruction, this is a code-path fix for new expenses only,
 * not a historical data correction.
 */
return new class extends Migration
{
    public function up(): void
    {
        Account::firstOrCreate(
            ['account_code' => '5165'],
            [
                'account_name' => 'General Operating Expenses',
                'account_type' => 'expense',
                'sub_type' => 'operating_expense',
                'is_system' => true,
                'opening_balance_type' => 'debit',
                'status' => 'active',
            ]
        );

        Account::where('account_code', '5170')->update(['sub_type' => 'operating_expense']);
    }

    public function down(): void
    {
        Account::where('account_code', '5170')->update(['sub_type' => 'other_expense']);
        Account::where('account_code', '5165')->delete();
    }
};
