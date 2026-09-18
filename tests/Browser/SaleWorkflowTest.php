<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Modules\Branch\Models\Branch;
use Modules\Category\Models\Category;
use Modules\Customer\Models\Customer;
use Modules\Product\Models\Product;
use Modules\Unit\Models\Unit;
use Tests\DuskTestCase;

class SaleWorkflowTest extends DuskTestCase
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

        Branch::create(['name' => 'Main Branch', 'code' => 'BR-001', 'is_main' => true, 'is_active' => true, 'is_pos_enabled' => true, 'is_ecom_enabled' => false]);
        $cat = Category::create(['name' => 'General', 'slug' => 'general', 'status' => 'active', 'sort_order' => 0]);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc', 'status' => 'active']);
        Product::create([
            'name' => 'Test Item', 'slug' => 'test-item', 'sku' => 'TST-001',
            'category_id' => $cat->id, 'unit_id' => $unit->id,
            'cost_price' => 100, 'sell_price' => 500, 'product_type' => 'simple',
            'status' => 'active', 'vat_rate' => 15, 'vat_inclusive' => 'yes',
            'discount_type' => 'none', 'min_stock_alert' => 10, 'show_in_pos' => true,
            'track_stock' => true, 'created_by' => 1,
        ]);
        Customer::create([
            'name' => 'Walk-in Customer', 'phone' => '01700000001',
            'customer_group' => 'Retail', 'is_active' => true, 'created_by' => 1,
        ]);
    }

    public function test_sales_list_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/admin/sales')
                ->assertSee('Sales')
                ->assertPresent('.bp-card');
        });
    }

    public function test_create_sale_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/admin/sales/create')
                ->assertSee('Create Sale')
                ->assertPresent('select[name="branch_id"]');
        });
    }

    public function test_sale_show_page_after_creation(): void
    {
        $sale = app(\Modules\Sale\Services\SaleService::class)->createSale([
            'customer_id' => 1,
            'branch_id' => 1,
            'invoice_date' => now()->format('Y-m-d'),
            'source' => 'store',
            'discount_type' => 'none',
            'discount_value' => 0,
            'tax_rate' => 0,
            'shipping_charge' => 0,
        ], [
            ['product_id' => 1, 'quantity' => 2, 'price' => 500, 'discount' => 0],
        ]);

        $this->browse(function (Browser $browser) use ($sale) {
            $browser->loginAs($this->admin)
                ->visit('/admin/sales/' . $sale->id)
                ->assertSee($sale->invoice_number)
                ->assertSee('1,000');
        });
    }
}
