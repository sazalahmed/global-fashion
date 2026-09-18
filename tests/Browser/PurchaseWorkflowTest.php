<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Modules\Branch\Models\Branch;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Supplier\Models\Supplier;
use Modules\Unit\Models\Unit;
use Tests\DuskTestCase;

class PurchaseWorkflowTest extends DuskTestCase
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

        Branch::create(['name' => 'Main', 'code' => 'BR-001', 'is_main' => true, 'is_active' => true, 'is_pos_enabled' => true, 'is_ecom_enabled' => false]);
        $cat = Category::create(['name' => 'General', 'slug' => 'general', 'status' => 'active', 'sort_order' => 0]);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc', 'status' => 'active']);
        Supplier::create(['company_name' => 'Supplier Co', 'contact_person' => 'John', 'phone' => '01811111111', 'status' => 'active', 'payment_terms' => 'Net 30', 'opening_balance' => 0, 'credit_limit' => 100000]);
        Product::create([
            'name' => 'Test Item', 'slug' => 'test-item', 'sku' => 'TST-001',
            'category_id' => $cat->id, 'unit_id' => $unit->id,
            'cost_price' => 100, 'sell_price' => 200, 'product_type' => 'simple',
            'status' => 'active', 'vat_rate' => 15, 'vat_inclusive' => 'yes',
            'discount_type' => 'none', 'min_stock_alert' => 10, 'show_in_pos' => true,
            'track_stock' => true, 'created_by' => 1,
        ]);
    }

    public function test_purchase_list_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/admin/purchases')
                ->assertSee('Purchase');
        });
    }

    public function test_create_purchase_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/admin/purchases/create')
                ->assertPresent('select[name="supplier_id"]')
                ->assertPresent('select[name="branch_id"]');
        });
    }

    public function test_purchase_show_page_after_creation(): void
    {
        $purchase = app(\Modules\Purchase\Services\PurchaseService::class)->create([
            'supplier_id' => 1,
            'branch_id' => 1,
            'po_date' => now()->format('Y-m-d'),
            'payment_terms' => 'Net 30',
            'status' => 'draft',
            'items' => [
                ['product_id' => 1, 'quantity' => 10, 'unit_price' => 100, 'discount_amount' => 0, 'tax_rate' => 15],
            ],
        ]);

        $this->browse(function (Browser $browser) use ($purchase) {
            $browser->loginAs($this->admin)
                ->visit('/admin/purchases/' . $purchase->id)
                ->assertSee($purchase->po_number)
                ->assertSee('Draft');
        });
    }
}
