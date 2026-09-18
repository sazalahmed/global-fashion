<?php

namespace Modules\Inventory\Tests\Unit;

use Modules\Category\Models\Category;
use Modules\Inventory\Models\StockLedger;
use Modules\Inventory\Models\WarehouseStock;
use Modules\Inventory\Services\InventoryService;
use Modules\Product\Models\Product;
use Modules\Unit\Models\Unit;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    protected InventoryService $service;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InventoryService::class);
        $this->actingAs($this->admin);

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
    }

    public function test_adjust_stock_increases_quantity(): void
    {
        $ledger = $this->service->adjustStock(
            $this->product->id, null,
            50, 'manual', 1, 100, 'Initial stock'
        );

        $this->assertInstanceOf(StockLedger::class, $ledger);
        $this->assertEquals(0, $ledger->quantity_before);
        $this->assertEquals(50, $ledger->quantity_after);
        $this->assertEquals(50, $this->service->getStockLevel($this->product->id, null));
    }

    public function test_adjust_stock_decreases_quantity(): void
    {
        $this->service->adjustStock($this->product->id, null, 100, 'manual', 1);
        $this->service->adjustStock($this->product->id, null, -30, 'sale', 1);

        $this->assertEquals(70, $this->service->getStockLevel($this->product->id, null));
    }

    public function test_adjust_stock_throws_on_insufficient(): void
    {
        $this->expectException(\Modules\Inventory\Exceptions\InsufficientStockException::class);

        $this->service->adjustStock($this->product->id, null, -10, 'sale', 1);
    }

    public function test_get_stock_level(): void
    {
        $this->service->adjustStock($this->product->id, null, 25, 'manual', 1);
        $level = $this->service->getStockLevel($this->product->id);

        $this->assertEquals(25, $level);
    }

    public function test_create_adjustment(): void
    {
        $adjustment = $this->service->createAdjustment(
            ['type' => 'addition', 'reason' => 'Found extra stock'],
            [['product_id' => $this->product->id, 'quantity' => 10, 'unit_cost' => 100]]
        );

        $this->assertEquals('draft', $adjustment->status);
        $this->assertNotEmpty($adjustment->adjustment_number);
        $this->assertDatabaseHas('stock_adjustments', ['id' => $adjustment->id]);
    }

    public function test_approve_adjustment_updates_stock(): void
    {
        $adjustment = $this->service->createAdjustment(
            ['type' => 'addition', 'reason' => 'Found extra'],
            [['product_id' => $this->product->id, 'quantity' => 20, 'unit_cost' => 100]]
        );
        $this->service->approveAdjustment($adjustment);

        $this->assertEquals('approved', $adjustment->fresh()->status);
        $this->assertEquals(20, $this->service->getStockLevel($this->product->id, null));
    }

    public function test_get_stats(): void
    {
        $stats = $this->service->getStats();

        $this->assertArrayHasKey('total_products', $stats);
        $this->assertArrayHasKey('low_stock_count', $stats);
        $this->assertArrayHasKey('total_stock_value', $stats);
    }
}
