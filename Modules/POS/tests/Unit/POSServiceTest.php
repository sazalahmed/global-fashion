<?php

namespace Modules\POS\Tests\Unit;

use Modules\Branch\Models\Branch;
use Modules\Category\Models\Category;
use Modules\Customer\Models\Customer;
use Modules\Inventory\Services\InventoryService;
use Modules\POS\Services\POSService;
use Modules\Product\Models\Product;
use Modules\Unit\Models\Unit;
use Tests\TestCase;

class POSServiceTest extends TestCase
{
    protected POSService $service;
    protected Product $product;
    protected Customer $customer;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(POSService::class);
        $this->actingAs($this->admin);

        $this->branch = Branch::create(['name' => 'Main', 'code' => 'BR-001', 'is_main' => true, 'is_active' => true, 'is_pos_enabled' => true, 'is_ecom_enabled' => false]);
        $category = Category::create(['name' => 'General', 'slug' => 'general', 'status' => 'active', 'sort_order' => 0]);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc', 'status' => 'active']);
        $this->product = Product::create([
            'name' => 'POS Product', 'slug' => 'pos-product', 'sku' => 'POS-001',
            'barcode' => '8801234567890',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'cost_price' => 100, 'sell_price' => 200, 'product_type' => 'simple',
            'status' => 'active', 'vat_rate' => 0, 'vat_inclusive' => 'no',
            'discount_type' => 'none', 'min_stock_alert' => 10, 'show_in_pos' => true,
            'track_stock' => true, 'created_by' => $this->admin->id,
        ]);
        $this->customer = Customer::create([
            'name' => 'Walk-in', 'phone' => '01700000001',
            'customer_group' => 'Retail', 'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        // Add stock
        app(InventoryService::class)->adjustStock(
            $this->product->id, null, 100, 'manual', 1, 100
        );
    }

    public function test_process_sale(): void
    {
        $sale = $this->service->processSale([
            'customer_id' => $this->customer->id,
            'cart' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 200],
            ],
            'payments' => [
                ['amount' => 400, 'method' => 'cash'],
            ],
            'discount_type' => null,
            'discount_value' => 0,
        ]);

        $this->assertNotNull($sale->id);
        $this->assertEquals('pos', $sale->source);
        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
    }

    public function test_process_sale_insufficient_stock_throws(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->service->processSale([
            'customer_id' => $this->customer->id,
            'cart' => [
                ['product_id' => $this->product->id, 'quantity' => 999, 'unit_price' => 200],
            ],
            'payments' => [
                ['amount' => 199800, 'method' => 'cash'],
            ],
        ]);
    }

    public function test_search_products(): void
    {
        $results = $this->service->searchProducts('POS');
        $this->assertGreaterThanOrEqual(1, $results->count());
        $this->assertEquals('POS Product', $results->first()->name);
    }

    public function test_get_by_barcode(): void
    {
        $found = $this->service->getProductByBarcode('8801234567890');
        $this->assertNotNull($found);
        $this->assertEquals($this->product->id, $found->id);
    }

    public function test_get_by_barcode_not_found(): void
    {
        $found = $this->service->getProductByBarcode('0000000000000');
        $this->assertNull($found);
    }

    public function test_check_availability_sufficient(): void
    {
        $result = $this->service->checkAvailability($this->product->id, null, 50);
        $this->assertTrue($result);
    }

    public function test_check_availability_insufficient(): void
    {
        $result = $this->service->checkAvailability($this->product->id, null, 999);
        $this->assertFalse($result);
    }
}
