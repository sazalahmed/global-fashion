<?php

namespace Modules\SaleReturn\Tests\Feature;

use Modules\Branch\Models\Branch;
use Modules\Category\Models\Category;
use Modules\Customer\Models\Customer;
use Modules\Product\Models\Product;
use Modules\Sale\Services\SaleService;
use Modules\SaleReturn\Models\SaleReturn;
use Modules\SaleReturn\Services\SaleReturnService;
use Modules\Unit\Models\Unit;
use Tests\TestCase;

class SaleReturnControllerTest extends TestCase
{
    protected Branch $branch;
    protected Product $product;
    protected Customer $customer;
    protected int $saleId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->branch = Branch::create(['name' => 'Main', 'code' => 'BR-001', 'is_main' => true, 'is_active' => true, 'is_pos_enabled' => true, 'is_ecom_enabled' => false]);
        $category = Category::create(['name' => 'General', 'slug' => 'general', 'status' => 'active', 'sort_order' => 0]);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc', 'status' => 'active']);
        $this->product = Product::create([
            'name' => 'Test Item', 'slug' => 'test-item', 'sku' => 'TST-001',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'cost_price' => 100, 'sell_price' => 200, 'product_type' => 'simple',
            'status' => 'active', 'vat_rate' => 0, 'vat_inclusive' => 'no',
            'discount_type' => 'none', 'min_stock_alert' => 10, 'show_in_pos' => true,
            'track_stock' => true, 'created_by' => $this->admin->id,
        ]);
        $this->customer = Customer::create([
            'name' => 'Test Customer', 'phone' => '01700000001',
            'customer_group' => 'Retail', 'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $sale = app(SaleService::class)->createSale([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'invoice_date' => now()->format('Y-m-d'),
            'source' => 'store',
            'discount_type' => null, 'discount_value' => 0, 'tax_rate' => 0, 'shipping_charge' => 0,
        ], [
            ['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 200, 'discount_amount' => 0],
        ]);
        $this->saleId = $sale->id;
    }

    private function createReturn(): SaleReturn
    {
        return app(SaleReturnService::class)->create([
            'sale_id' => $this->saleId,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Defective',
            'refund_method' => 'cash',
        ], [
            ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 200, 'tax_amount' => 0],
        ]);
    }

    public function test_index_renders(): void
    {
        $this->actingAsAdmin()->get(route('sale-returns.index'))->assertStatus(200);
    }

    public function test_create_renders(): void
    {
        $this->actingAsAdmin()->get(route('sale-returns.create'))->assertStatus(200);
    }

    public function test_store_creates_sale_return(): void
    {
        $response = $this->actingAsAdmin()->post(route('sale-returns.store'), [
            'sale_id' => $this->saleId,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Defective product',
            'refund_method' => 'cash',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 200, 'tax_amount' => 0],
            ],
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('sale_returns', ['sale_id' => $this->saleId]);
    }

    public function test_approve_sale_return(): void
    {
        $return = $this->createReturn();
        $this->actingAsAdmin()->post(route('sale-returns.approve', $return))->assertRedirect();
        $this->assertDatabaseHas('sale_returns', ['id' => $return->id, 'status' => 'approved']);
    }

    public function test_cancel_sale_return(): void
    {
        $return = $this->createReturn();
        $this->actingAsAdmin()->post(route('sale-returns.cancel', $return))->assertRedirect();
        $this->assertDatabaseHas('sale_returns', ['id' => $return->id, 'status' => 'cancelled']);
    }
}
