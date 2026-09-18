<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Modules\Accounting\Models\Account;
use Modules\Expense\Models\ExpenseCategory;
use Tests\DuskTestCase;

class ExpenseWorkflowTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => \Modules\Security\Database\Seeders\RolePermissionSeeder::class]);
        $this->artisan('db:seed', ['--class' => \Modules\Accounting\Database\Seeders\ChartOfAccountsSeeder::class]);

        $this->admin = User::factory()->create([
            'email' => 'admin@bizpos.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->admin->assignRole('Super Admin');

        ExpenseCategory::create(['name' => 'Office Supplies', 'is_active' => true, 'sort_order' => 0]);
    }

    public function test_expense_list_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/admin/expenses')
                ->assertSee('Expense');
        });
    }

    public function test_create_expense_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/admin/expenses/create')
                ->assertPresent('select[name="expense_category_id"]')
                ->assertPresent('input[name="amount"]');
        });
    }

    public function test_expense_ledger_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/admin/expenses/ledger')
                ->assertSee('Ledger');
        });
    }
}
