<?php

namespace Modules\Accounting\Tests\Unit;

use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Services\JournalEntryService;
use Tests\TestCase;

class JournalEntryServiceTest extends TestCase
{
    protected JournalEntryService $service;
    protected Account $cashAccount;
    protected Account $revenueAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->admin);
        $this->service = app(JournalEntryService::class);
        $this->cashAccount = Account::where('account_code', '1001')->first();
        $this->revenueAccount = Account::where('account_code', '4001')->first();
    }

    public function test_create_journal_entry(): void
    {
        $entry = $this->service->create([
            'entry_date' => now()->format('Y-m-d'),
            'description' => 'Test entry',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit_amount' => 1000, 'credit_amount' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit_amount' => 0, 'credit_amount' => 1000],
            ],
        ]);

        $this->assertInstanceOf(JournalEntry::class, $entry);
        $this->assertEquals('draft', $entry->status);
        $this->assertDatabaseCount('journal_entry_lines', 2);
    }

    public function test_post_balanced_entry(): void
    {
        $entry = $this->service->create([
            'entry_date' => now()->format('Y-m-d'),
            'description' => 'Balanced entry',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit_amount' => 5000, 'credit_amount' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit_amount' => 0, 'credit_amount' => 5000],
            ],
        ]);

        $posted = $this->service->post($entry);
        $this->assertEquals('posted', $posted->status);
        $this->assertNotNull($posted->posted_at);
    }

    public function test_post_unbalanced_entry_throws(): void
    {
        $entry = $this->service->create([
            'entry_date' => now()->format('Y-m-d'),
            'description' => 'Unbalanced',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit_amount' => 5000, 'credit_amount' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit_amount' => 0, 'credit_amount' => 3000],
            ],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->service->post($entry);
    }

    public function test_void_creates_reversing_entry(): void
    {
        $entry = $this->service->create([
            'entry_date' => now()->format('Y-m-d'),
            'description' => 'To void',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit_amount' => 1000, 'credit_amount' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit_amount' => 0, 'credit_amount' => 1000],
            ],
        ]);
        $this->service->post($entry);

        $voided = $this->service->void($entry, 'Test void reason');
        $this->assertEquals('voided', $voided->status);
        $this->assertDatabaseCount('journal_entries', 2);
    }

    public function test_delete_draft_only(): void
    {
        $entry = $this->service->create([
            'entry_date' => now()->format('Y-m-d'),
            'description' => 'Draft to delete',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit_amount' => 100, 'credit_amount' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit_amount' => 0, 'credit_amount' => 100],
            ],
        ]);

        $result = $this->service->delete($entry);
        $this->assertTrue($result);
    }

    public function test_create_from_source(): void
    {
        $entry = $this->service->createFromSource('sale', 1, [
            ['account_id' => $this->cashAccount->id, 'debit_amount' => 2000, 'credit_amount' => 0],
            ['account_id' => $this->revenueAccount->id, 'debit_amount' => 0, 'credit_amount' => 2000],
        ], 'Sale #1');

        $this->assertEquals('posted', $entry->status);
        $this->assertEquals('sale', $entry->source_type);
    }

    public function test_entry_number_is_unique(): void
    {
        $e1 = $this->service->create(['entry_date' => now()->format('Y-m-d'), 'description' => 'E1', 'lines' => [
            ['account_id' => $this->cashAccount->id, 'debit_amount' => 100, 'credit_amount' => 0],
            ['account_id' => $this->revenueAccount->id, 'debit_amount' => 0, 'credit_amount' => 100],
        ]]);
        $e2 = $this->service->create(['entry_date' => now()->format('Y-m-d'), 'description' => 'E2', 'lines' => [
            ['account_id' => $this->cashAccount->id, 'debit_amount' => 200, 'credit_amount' => 0],
            ['account_id' => $this->revenueAccount->id, 'debit_amount' => 0, 'credit_amount' => 200],
        ]]);

        $this->assertNotEquals($e1->entry_number, $e2->entry_number);
    }
}
