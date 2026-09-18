<?php

namespace Modules\Expense\Tests\Unit;

use Modules\Accounting\Models\Account;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Expense\Services\ExpenseService;
use Tests\TestCase;

class ExpenseServiceTest extends TestCase
{
    protected ExpenseService $service;
    protected ExpenseCategory $category;
    protected Account $expenseAccount;
    protected Account $paymentAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->admin);
        $this->service = app(ExpenseService::class);
        $this->expenseAccount = Account::where('account_code', '5160')->first();
        $this->paymentAccount = Account::where('account_code', '1001')->first();
        $this->category = ExpenseCategory::create(['name' => 'Office', 'account_id' => $this->expenseAccount->id, 'is_active' => true, 'sort_order' => 0]);
    }

    private function expenseData(array $overrides = []): array
    {
        return array_merge([
            'expense_category_id' => $this->category->id,
            'account_id' => $this->expenseAccount->id,
            'payment_account_id' => $this->paymentAccount->id,
            'amount' => 5000,
            'tax_amount' => 0,
            'expense_date' => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'description' => 'Office supplies purchase',
            'status' => 'pending',
        ], $overrides);
    }

    public function test_create_expense(): void
    {
        $expense = $this->service->create($this->expenseData());
        $this->assertInstanceOf(Expense::class, $expense);
        $this->assertEquals('pending', $expense->status);
        $this->assertEquals(5000, $expense->total_amount);
    }

    public function test_approve_expense(): void
    {
        $expense = $this->service->create($this->expenseData());
        $approved = $this->service->approve($expense);
        $this->assertEquals('approved', $approved->status);
    }

    public function test_reject_expense(): void
    {
        $expense = $this->service->create($this->expenseData());
        $rejected = $this->service->reject($expense, 'Budget exceeded');
        $this->assertEquals('rejected', $rejected->status);
    }

    public function test_mark_paid_creates_journal_entry(): void
    {
        $expense = $this->service->create($this->expenseData());
        $this->service->approve($expense);
        $expense->refresh();
        $paid = $this->service->markPaid($expense);

        $this->assertEquals('paid', $paid->status);
        $this->assertNotNull($paid->journal_entry_id);
    }

    public function test_delete_pending_only(): void
    {
        $expense = $this->service->create($this->expenseData());
        $result = $this->service->delete($expense);
        $this->assertTrue($result);
    }

    public function test_list_expenses(): void
    {
        $this->service->create($this->expenseData());
        $result = $this->service->list();
        $this->assertEquals(1, $result->total());
    }

    public function test_get_stats(): void
    {
        $this->service->create($this->expenseData());
        $stats = $this->service->getStats();
        $this->assertArrayHasKey('today', $stats);
        $this->assertArrayHasKey('pending_count', $stats);
    }

    public function test_get_ledger(): void
    {
        $this->service->create($this->expenseData());
        $result = $this->service->getLedger();
        $this->assertGreaterThanOrEqual(1, $result->total());
    }
}
