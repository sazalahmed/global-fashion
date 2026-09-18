<?php

namespace Modules\Customer\Tests\Unit;

use Modules\Accounting\Database\Seeders\ChartOfAccountsSeeder;
use Modules\Customer\Models\Customer;
use Modules\Customer\Services\CustomerService;
use Modules\Payment\Models\PaymentAccount;
use Modules\Payment\Services\PaymentService;
use Tests\TestCase;

/**
 * The customer ledger has to describe a receipt by the account it actually
 * landed in. payment_method is a denormalised label that defaulted to 'Cash'
 * whenever a form posted only payment_account_id, so bank receipts read as
 * cash — and the same label routed the journal entry, putting the money in
 * Cash in Hand.
 */
class CustomerLedgerTest extends TestCase
{
    protected CustomerService $service;
    protected PaymentService $payments;
    protected Customer $customer;
    protected PaymentAccount $bank;
    protected PaymentAccount $cash;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ChartOfAccountsSeeder::class);
        $this->actingAs($this->admin);
        $this->service = app(CustomerService::class);
        $this->payments = app(PaymentService::class);

        $this->customer = Customer::create([
            'name' => 'Ledger Customer', 'phone' => '01700000055',
            'customer_group' => 'Retail', 'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $this->cash = PaymentAccount::create([
            'name' => 'Cash', 'account_type' => 'cash',
            'is_default' => true, 'is_active' => true, 'created_by' => $this->admin->id,
        ]);
        $this->bank = PaymentAccount::create([
            'name' => 'Sazal Savings', 'account_type' => 'bank',
            'is_default' => false, 'is_active' => true, 'created_by' => $this->admin->id,
        ]);
    }

    /** A receipt with no payment_method sent — exactly what the form posts. */
    private function receive(PaymentAccount $account, float $amount, string $date): \Modules\Payment\Models\Payment
    {
        return $this->payments->create([
            'direction'          => 'receive',
            'party_type'         => 'customer',
            'party_id'           => $this->customer->id,
            'payment_type'       => 'against_invoice',
            'amount'             => $amount,
            'payment_account_id' => $account->id,
            'payment_date'       => $date,
        ]);
    }

    public function test_method_is_taken_from_the_chosen_account_not_defaulted_to_cash(): void
    {
        $payment = $this->receive($this->bank, 5000, now()->format('Y-m-d'));

        $this->assertEquals('bank', $payment->fresh()->payment_method);
    }

    public function test_a_bank_receipt_posts_to_the_bank_ledger_account(): void
    {
        $payment = $this->receive($this->bank, 5000, now()->format('Y-m-d'));

        $debited = \Modules\Accounting\Models\JournalEntryLine::query()
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entry_lines.journal_entry_id', $payment->fresh()->journal_entry_id)
            ->where('journal_entry_lines.debit_amount', '>', 0)
            ->value('accounts.account_code');

        // 1004 Bank — never 1001 Cash in Hand.
        $this->assertEquals('1004', $debited);
    }

    public function test_ledger_describes_the_receipt_by_its_account(): void
    {
        $this->receive($this->bank, 5000, now()->format('Y-m-d'));

        $entries = collect($this->service->getLedger($this->customer->id)['entries']);
        $payment = $entries->firstWhere('source_type', 'payment');

        $this->assertStringContainsString('Sazal Savings', $payment->description);
        $this->assertStringNotContainsStringIgnoringCase('cash', $payment->description);
    }

    public function test_ledger_is_newest_first_but_keeps_a_chronological_balance(): void
    {
        $this->receive($this->cash, 1000, '2026-07-01');
        $this->receive($this->bank, 2000, '2026-07-15');
        $this->receive($this->cash, 3000, '2026-07-20');

        $ledger = $this->service->getLedger($this->customer->id);
        $entries = collect($ledger['entries']);

        // Newest first for display.
        $this->assertEquals('2026-07-20', \Carbon\Carbon::parse($entries->first()->date)->format('Y-m-d'));
        $this->assertEquals('2026-07-01', \Carbon\Carbon::parse($entries->last()->date)->format('Y-m-d'));

        // The balance column still accumulates oldest-first, so the oldest row
        // shows the first movement and the newest row the closing balance.
        $this->assertEquals(-1000.0, (float) $entries->last()->balance);
        $this->assertEquals(-6000.0, (float) $entries->first()->balance);
        $this->assertEquals(-6000.0, (float) $ledger['currentBalance']);
    }

    public function test_ledger_can_be_paginated_without_losing_totals(): void
    {
        foreach (range(1, 7) as $i) {
            $this->receive($this->cash, 100, '2026-07-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT));
        }

        $ledger = $this->service->getLedgerPaginated($this->customer->id, perPage: 5);

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $ledger['entries']);
        $this->assertEquals(7, $ledger['entries']->total());
        $this->assertCount(5, $ledger['entries']->items());
        // Totals must cover every entry, not just the visible page.
        $this->assertEquals(700.0, (float) $ledger['totalCredit']);
    }
}
