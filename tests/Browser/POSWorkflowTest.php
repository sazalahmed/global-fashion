<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Modules\Branch\Models\Branch;
use Modules\Category\Models\Category;
use Modules\Customer\Models\Customer;
use Modules\Inventory\Models\WarehouseStock;
use Modules\Product\Models\Product;
use Modules\Unit\Models\Unit;
use Modules\Warehouse\Models\Warehouse;
use Tests\DuskTestCase;

class POSWorkflowTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => \Modules\Security\Database\Seeders\RolePermissionSeeder::class]);
        $this->artisan('db:seed', ['--class' => \Modules\Accounting\Database\Seeders\ChartOfAccountsSeeder::class]);

        $this->admin = User::factory()->create([
            'email' => 'cashier@bizpos.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->admin->assignRole('Super Admin');

        Branch::create(['name' => 'Main Branch', 'code' => 'BR-001', 'is_main' => true, 'is_active' => true, 'is_pos_enabled' => true, 'is_ecom_enabled' => false]);
        $cat = Category::create(['name' => 'General', 'slug' => 'general', 'status' => 'active', 'sort_order' => 0]);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc', 'status' => 'active']);
        $warehouse = Warehouse::create(['name' => 'Main', 'code' => 'WH-001', 'is_default' => true, 'is_active' => true]);

        $product = Product::create([
            'name' => 'POS Item', 'slug' => 'pos-item', 'sku' => 'POS-001', 'barcode' => '8801234567890',
            'category_id' => $cat->id, 'unit_id' => $unit->id,
            'cost_price' => 50, 'sell_price' => 100, 'product_type' => 'simple',
            'status' => 'active', 'vat_rate' => 0, 'vat_inclusive' => 'yes',
            'discount_type' => 'none', 'min_stock_alert' => 5, 'show_in_pos' => true,
            'track_stock' => true, 'created_by' => 1,
        ]);

        WarehouseStock::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 100,
            'reserved_quantity' => 0,
            'reorder_level' => 10,
        ]);

        Customer::create([
            'name' => 'Walk-in', 'phone' => '01700000000',
            'customer_group' => 'Retail', 'is_active' => true, 'created_by' => 1,
        ]);
    }

    public function test_pos_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/admin/pos')
                ->assertPresent('#pos-container, .pos-terminal, .bp-main');
        });
    }

    public function test_pos_shows_product_categories(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/admin/pos')
                ->assertSee('General');
        });
    }
}
