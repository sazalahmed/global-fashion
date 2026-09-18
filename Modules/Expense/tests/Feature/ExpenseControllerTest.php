<?php

namespace Modules\Expense\Tests\Feature;

use Modules\Accounting\Models\Account;
use Modules\Expense\Models\ExpenseCategory;
use Tests\TestCase;

class ExpenseControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ExpenseCategory::create(['name' => 'Office', 'is_active' => true, 'sort_order' => 0]);
    }

    public function test_index_renders(): void
    {
        $this->actingAsAdmin()->get(route('expenses.index'))->assertStatus(200);
    }

    public function test_create_renders(): void
    {
        $this->actingAsAdmin()->get(route('expenses.create'))->assertStatus(200);
    }

    public function test_store_creates_expense(): void
    {
        $expAcc = Account::where('account_type', 'expense')->first();
        $payAcc = Account::where('account_code', '1001')->first();
        $response = $this->actingAsAdmin()->post(route('expenses.store'), [
            'expense_category_id' => 1,
            'account_id' => $expAcc->id,
            'payment_account_id' => $payAcc->id,
            'amount' => 5000,
            'expense_date' => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'description' => 'Office supplies',
        ]);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('expenses', ['amount' => 5000]);
    }

    public function test_ledger_renders(): void
    {
        $this->actingAsAdmin()->get(route('expenses.ledger'))->assertStatus(200);
    }
}
