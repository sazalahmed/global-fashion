<?php

namespace Modules\Accounting\Tests\Feature;

use Modules\Accounting\Models\Account;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    public function test_chart_of_accounts_renders(): void
    {
        $this->actingAsAdmin()->get(route('accounting.chart-of-accounts'))->assertStatus(200);
    }

    public function test_create_account_page_renders(): void
    {
        $this->actingAsAdmin()->get(route('accounting.chart-of-accounts.create'))->assertStatus(200);
    }

    public function test_store_creates_account(): void
    {
        $this->actingAsAdmin()->post(route('accounting.chart-of-accounts.store'), [
            'account_code' => '9999',
            'account_name' => 'Test Account',
            'account_type' => 'asset',
            'sub_type' => 'current_asset',
        ])->assertRedirect();
        $this->assertDatabaseHas('accounts', ['account_code' => '9999']);
    }

    public function test_journal_entries_renders(): void
    {
        $this->actingAsAdmin()->get(route('accounting.journal-entries'))->assertStatus(200);
    }

    public function test_create_journal_entry_renders(): void
    {
        $this->actingAsAdmin()->get(route('accounting.journal-entries.create'))->assertStatus(200);
    }

    public function test_general_ledger_renders(): void
    {
        $this->actingAsAdmin()->get(route('accounting.general-ledger'))->assertStatus(200);
    }

    public function test_trial_balance_renders(): void
    {
        $this->actingAsAdmin()->get(route('accounting.trial-balance'))->assertStatus(200);
    }

    public function test_profit_loss_renders(): void
    {
        $this->actingAsAdmin()->get(route('accounting.profit-loss'))->assertStatus(200);
    }

    public function test_balance_sheet_renders(): void
    {
        $this->actingAsAdmin()->get(route('accounting.balance-sheet'))->assertStatus(200);
    }

    public function test_bank_reconciliation_renders(): void
    {
        $this->actingAsAdmin()->get(route('accounting.bank-reconciliation'))->assertStatus(200);
    }

    public function test_credit_notes_renders(): void
    {
        $this->actingAsAdmin()->get(route('accounting.credit-notes.index'))->assertStatus(200);
    }

    public function test_debit_notes_renders(): void
    {
        $this->actingAsAdmin()->get(route('accounting.debit-notes.index'))->assertStatus(200);
    }
}
