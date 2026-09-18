<?php

namespace Modules\Sale\Tests\Feature;

use Modules\Branch\Models\Branch;
use Modules\Category\Models\Category;
use Modules\Customer\Models\Customer;
use Modules\Product\Models\Product;
use Modules\Sale\Services\SaleService;
use Modules\Unit\Models\Unit;
use Tests\TestCase;

class SaleControllerTest extends TestCase
{
    protected Branch $branch;
    protected Product $product;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        // Gated controller routes flash "Permission denied" and bounce home
        // without this. Role created directly — RolePermissionSeeder TRUNCATEs,
        // whose implicit commit breaks the RefreshDatabase test transaction.
        \Spatie\Permission\Models\Role::findOrCreate('Super Admin');
        $this->admin->assignRole('Super Admin');
        $this->branch = Branch::create(['name' => 'Main', 'code' => 'BR-001', 'is_main' => true, 'is_active' => true, 'is_pos_enabled' => true, 'is_ecom_enabled' => false]);
        $category = Category::create(['name' => 'General', 'slug' => 'general', 'status' => 'active', 'sort_order' => 0]);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc', 'status' => 'active']);
        $this->product = Product::create([
            'name' => 'Test Item', 'slug' => 'test-item', 'sku' => 'TST-001',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'cost_price' => 100, 'sell_price' => 200, 'product_type' => 'simple',
            'status' => 'active', 'vat_rate' => 15, 'vat_inclusive' => 'yes',
            'discount_type' => 'none', 'min_stock_alert' => 10, 'show_in_pos' => true,
            'track_stock' => true, 'created_by' => $this->admin->id,
        ]);
        $this->customer = Customer::create([
            'name' => 'Walk-in', 'phone' => '01700000001',
            'customer_group' => 'Retail', 'is_active' => true, 'created_by' => $this->admin->id,
        ]);
    }

    public function test_index_displays_sales(): void
    {
        $response = $this->actingAsAdmin()->get(route('sales.index'));
        $response->assertStatus(200);
        $response->assertViewIs('sale::index');
    }

    public function test_create_page_renders(): void
    {
        $response = $this->actingAsAdmin()->get(route('sales.create'));
        $response->assertStatus(200);
        $response->assertViewIs('sale::create');
    }

    public function test_store_creates_sale_with_items(): void
    {
        $response = $this->actingAsAdmin()->post(route('sales.store'), [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'invoice_date' => now()->format('Y-m-d'),
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'price' => 200, 'discount' => 0],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sales', ['customer_id' => $this->customer->id]);
        $this->assertDatabaseHas('sale_items', ['product_id' => $this->product->id, 'quantity' => 2]);
    }

    public function test_store_accepts_advance_payment_without_account(): void
    {
        $cash = \Modules\Payment\Models\PaymentAccount::create([
            'name' => 'Cash', 'account_type' => 'cash',
            'is_default' => true, 'is_active' => true,
        ]);

        $response = $this->actingAsAdmin()->post(route('sales.store'), [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'invoice_date' => now()->format('Y-m-d'),
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'price' => 200, 'discount' => 0],
            ],
            // Advance amount entered, but the account dropdown left on "Select Account".
            'payments' => [
                ['amount' => 100, 'payment_account_id' => '', 'reference' => ''],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('sales', ['customer_id' => $this->customer->id, 'paid_amount' => 100]);
        $this->assertDatabaseHas('payments', ['amount' => 100, 'payment_account_id' => $cash->id]);
    }

    public function test_edit_shows_deleted_account_of_existing_advance(): void
    {
        $this->actingAsAdmin();
        $bkash = \Modules\Payment\Models\PaymentAccount::create([
            'name' => 'bKash Personal', 'account_type' => 'mobile_banking',
            'is_default' => false, 'is_active' => true,
        ]);
        $sale = app(SaleService::class)->createSale([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'invoice_date' => now()->format('Y-m-d'),
        ], [
            ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 200, 'discount_amount' => 0],
        ], [
            ['amount' => 100, 'payment_account_id' => $bkash->id],
        ]);
        $bkash->delete();

        $response = $this->get(route('sales.edit', $sale));

        $response->assertStatus(200);
        // The deleted account must still be rendered — and preselected — in
        // the existing advance row's dropdown.
        $this->assertMatchesRegularExpression(
            '/<option value="' . $bkash->id . '"[^>]*selected>/s',
            $response->getContent()
        );
        $response->assertSee('bKash Personal (deleted)');
    }

    public function test_search_overrides_status_tab_and_date_filters(): void
    {
        $this->actingAsAdmin();
        $sale = app(SaleService::class)->createSale([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'invoice_date' => now()->subDays(9)->format('Y-m-d'),
        ], [
            ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 200, 'discount_amount' => 0],
        ]);

        // Leftover tab + date range from a previous filter session must not
        // hide an explicit phone search (sale is 'confirmed', dated 9 days ago).
        $response = $this->get(route('sales.index', [
            'search'    => $this->customer->phone,
            'status'    => 'partial_cancelled',
            'date_from' => now()->format('Y-m-d'),
            'date_to'   => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $response->assertSee($sale->invoice_number);
    }

    public function test_store_fails_without_items(): void
    {
        $response = $this->actingAsAdmin()->post(route('sales.store'), [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'invoice_date' => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('items');
    }

    public function test_show_displays_sale(): void
    {
        $sale = $this->createSale();
        $response = $this->actingAsAdmin()->get(route('sales.show', $sale));
        $response->assertStatus(200);
        $response->assertViewIs('sale::show');
    }

    public function test_destroy_cancels_sale(): void
    {
        $sale = $this->createSale();
        $response = $this->actingAsAdmin()->delete(route('sales.destroy', $sale));
        $response->assertRedirect(route('sales.index'));
    }

    public function test_print_renders_invoice(): void
    {
        $sale = $this->createSale();
        $response = $this->actingAsAdmin()->get(route('sales.print', $sale));
        $response->assertStatus(200);
    }

    public function test_index_with_date_filter(): void
    {
        $this->createSale();
        $response = $this->actingAsAdmin()->get(route('sales.index', [
            'from_date' => now()->subDays(7)->format('Y-m-d'),
            'to_date' => now()->format('Y-m-d'),
        ]));
        $response->assertStatus(200);
    }

    private function createSale()
    {
        return app(SaleService::class)->createSale([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'invoice_date' => now()->format('Y-m-d'),
            'source' => 'store',
            'discount_type' => null,
            'discount_value' => 0,
            'tax_rate' => 0,
            'shipping_charge' => 0,
        ], [
            ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 200, 'discount_amount' => 0],
        ]);
    }
}
