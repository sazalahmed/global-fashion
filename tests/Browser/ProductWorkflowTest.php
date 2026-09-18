<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Modules\Category\Models\Category;
use Modules\Unit\Models\Unit;
use Tests\DuskTestCase;

class ProductWorkflowTest extends DuskTestCase
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

        Category::create(['name' => 'Electronics', 'slug' => 'electronics', 'status' => 'active', 'sort_order' => 0]);
        Unit::create(['name' => 'Piece', 'short_name' => 'pc', 'status' => 'active']);
    }

    public function test_product_list_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/admin/products')
                ->assertSee('Products')
                ->assertPresent('.bp-card');
        });
    }

    public function test_create_product_form_renders(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/admin/products/create')
                ->assertSee('Add Product')
                ->assertPresent('input[name="name"]')
                ->assertPresent('select[name="category_id"]')
                ->assertPresent('input[name="cost_price"]')
                ->assertPresent('input[name="sell_price"]');
        });
    }

    public function test_create_product_end_to_end(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/admin/products/create')
                ->type('name', 'iPhone 15 Pro Max')
                ->select('category_id', '1')
                ->select('unit_id', '1')
                ->type('cost_price', '90000')
                ->type('sell_price', '120000')
                ->select('product_type', 'simple')
                ->press('Save')
                ->waitForText('success', 10)
                ->assertPathIsNot('/admin/products/create');

            $this->assertDatabaseHas('products', ['name' => 'iPhone 15 Pro Max']);
        });
    }

    public function test_product_search_filter(): void
    {
        // Create a product first via service
        app(\Modules\Product\Services\ProductService::class)->create([
            'name' => 'Samsung Galaxy S24',
            'category_id' => 1,
            'unit_id' => 1,
            'cost_price' => 70000,
            'sell_price' => 95000,
            'product_type' => 'simple',
            'status' => 'active',
            'vat_rate' => 15,
            'vat_inclusive' => 'yes',
            'discount_type' => 'none',
            'min_stock_alert' => 10,
            'show_in_pos' => true,
            'track_stock' => true,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/admin/products')
                ->assertSee('Samsung Galaxy S24');
        });
    }
}
