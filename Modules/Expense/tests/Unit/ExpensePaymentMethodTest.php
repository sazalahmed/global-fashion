<?php

namespace Modules\Expense\Tests\Unit;

use Modules\Accounting\Database\Seeders\ChartOfAccountsSeeder;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Expense\Services\ExpenseService;
use Modules\Payment\Models\PaymentAccount;
use Tests\TestCase;

/**
 * The expense form posts a payment account, never a payment_method string, so
 * defaulting the method to 'Cash' labelled every bank and wallet expense as
 * cash in the list. The method has to come from the account that was chosen.
 */
class ExpensePaymentMethodTest extends TestCase
{
    protected ExpenseService $service;
    protected ExpenseCategory $category;
    protected PaymentAccount $cash;
    protected PaymentAccount $bank;
    protected PaymentAccount $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ChartOfAccountsSeeder::class);
        $this->actingAs($this->admin);
        $this->service = app(ExpenseService::class);

        $this->category = ExpenseCategory::create([
            'name' => 'Office', 'is_active' => true, 'sort_order' => 0,
            'account_id' => \Modules\Accounting\Models\Account::where('account_code', '5160')->value('id'),
        ]);

        // expenses.payment_account_id carries a foreign key to `accounts` (the
        // chart of accounts) even though every reader treats it as a
        // payment_accounts id — see the note on the class. Live data only
        // satisfies that constraint because the two tables happen to share id
        // numbers, so the payment accounts here are given ids that exist in
        // `accounts` too. Remove this once the column points at the right table.
        $this->cash   = $this->paymentAccount('Cash', 'cash', '1001', true);
        $this->bank   = $this->paymentAccount('City Bank', 'bank', '1004');
        $this->wallet = $this->paymentAccount('bKash Merchant', 'mobile_banking', '1002');
    }

    private function paymentAccount(string $name, string $type, string $borrowIdFromCode, bool $default = false): PaymentAccount
    {
        $account = new PaymentAccount([
            'name' => $name, 'account_type' => $type,
            'is_default' => $default, 'is_active' => true, 'created_by' => $this->admin->id,
        ]);
        $account->id = \Modules\Accounting\Models\Account::where('account_code', $borrowIdFromCode)->value('id');
        $account->save();

        return $account;
    }

    /** Mirrors the create form: an account is chosen, no method is posted. */
    private function spend(PaymentAccount $account, float $amount = 2550)
    {
        return $this->service->create([
            'expense_category_id' => $this->category->id,
            'payment_account_id'  => $account->id,
            'amount'              => $amount,
            'tax_amount'          => 0,
            'expense_date'        => now()->format('Y-m-d'),
            'description'         => 'Server-site data tracking charge',
            'status'              => 'pending',
        ]);
    }

    public function test_a_bank_expense_is_not_labelled_cash(): void
    {
        $expense = $this->spend($this->bank);

        $this->assertEquals('bank', $expense->fresh()->payment_method);
    }

    public function test_a_wallet_expense_is_not_labelled_cash(): void
    {
        $expense = $this->spend($this->wallet);

        $this->assertEquals('mobile_banking', $expense->fresh()->payment_method);
    }

    public function test_a_cash_expense_is_still_cash(): void
    {
        $expense = $this->spend($this->cash);

        $this->assertEquals('cash', $expense->fresh()->payment_method);
    }

    public function test_an_explicit_method_still_wins(): void
    {
        $expense = $this->service->create([
            'expense_category_id' => $this->category->id,
            'payment_account_id'  => $this->bank->id,
            'payment_method'      => 'card',
            'amount'              => 100,
            'tax_amount'          => 0,
            'expense_date'        => now()->format('Y-m-d'),
            'description'         => 'Card spend',
            'status'              => 'pending',
        ]);

        $this->assertEquals('card', $expense->fresh()->payment_method);
    }

    /**
     * The payment-account balance is what the Account List shows, and it sums
     * expenses by payment_account_id — so a bank expense must reduce the bank,
     * and leave cash alone.
     */
    public function test_a_bank_expense_reduces_the_bank_account_not_cash(): void
    {
        $expense = $this->spend($this->bank, 2550);
        $this->service->approve($expense);
        $this->service->markPaid($expense->fresh());

        $this->assertEquals(-2550.0, round((float) $this->bank->fresh()->currentBalance(), 2));
        $this->assertEquals(0.0, round((float) $this->cash->fresh()->currentBalance(), 2));
    }
}
