<?php

namespace Modules\Purchase\Tests\Unit;

use Modules\Branch\Models\Branch;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Purchase\Models\Purchase;
use Modules\Purchase\Services\PurchaseService;
use Modules\Supplier\Models\Supplier;
use Modules\Unit\Models\Unit;
use Tests\TestCase;

class PurchaseServiceTest extends TestCase
{
    protected PurchaseService $service;
    protected Supplier $supplier;
    protected Branch $branch;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->admin);
        $this->service = app(PurchaseService::class);
        $this->branch = Branch::create(['name' => 'Main', 'code' => 'BR-001', 'is_main' => true, 'is_active' => true, 'is_pos_enabled' => true, 'is_ecom_enabled' => false]);
        $this->supplier = Supplier::create(['company_name' => 'Supplier Co', 'contact_person' => 'John', 'phone' => '01811111111', 'status' => 'active', 'payment_terms' => 'Net 30', 'opening_balance' => 0, 'credit_limit' => 100000]);
        $cat = Category::create(['name' => 'General', 'slug' => 'general', 'status' => 'active', 'sort_order' => 0]);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc', 'status' => 'active']);
        $this->product = Product::create([
            'name' => 'Test', 'slug' => 'test', 'sku' => 'TST-001',
            'category_id' => $cat->id, 'unit_id' => $unit->id,
            'cost_price' => 100, 'sell_price' => 200, 'product_type' => 'simple',
            'status' => 'active', 'vat_rate' => 15, 'vat_inclusive' => 'yes',
            'discount_type' => 'none', 'min_stock_alert' => 10, 'show_in_pos' => true,
            'track_stock' => true, 'created_by' => $this->admin->id,
        ]);
    }

    private function purchaseData(): array
    {
        return [
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'po_date' => now()->format('Y-m-d'),
            'payment_terms' => 'Net 30',
            'status' => 'draft',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 10, 'unit_price' => 100, 'discount_amount' => 0, 'tax_rate' => 15],
            ],
        ];
    }

    public function test_create_purchase(): void
    {
        $purchase = $this->service->create($this->purchaseData());
        $this->assertInstanceOf(Purchase::class, $purchase);
        $this->assertEquals('draft', $purchase->status);
        $this->assertDatabaseHas('purchases', ['id' => $purchase->id]);
        $this->assertDatabaseHas('purchase_items', ['purchase_id' => $purchase->id]);
    }

    public function test_create_calculates_totals(): void
    {
        $purchase = $this->service->create($this->purchaseData());
        $this->assertEquals(1000, $purchase->subtotal);
        $this->assertGreaterThan(0, $purchase->tax_amount);
        $this->assertGreaterThan(1000, $purchase->grand_total);
    }

    public function test_approve_purchase(): void
    {
        $purchase = $this->service->create(array_merge($this->purchaseData(), ['status' => 'pending']));
        $approved = $this->service->approve($purchase, $this->admin->id);
        $this->assertEquals('approved', $approved->status);
    }

    public function test_cancel_purchase(): void
    {
        $purchase = $this->service->create($this->purchaseData());
        $cancelled = $this->service->cancel($purchase);
        $this->assertEquals('cancelled', $cancelled->status);
    }

    public function test_delete_draft_only(): void
    {
        $purchase = $this->service->create($this->purchaseData());
        $result = $this->service->delete($purchase);
        $this->assertTrue($result);
    }

    public function test_delete_received_purchase_reverses_received_stock(): void
    {
        $inventory = app(\Modules\Inventory\Services\InventoryService::class);
        $grnService = app(\Modules\Purchase\Services\GrnService::class);

        $purchase = $this->service->create(array_merge($this->purchaseData(), ['status' => 'approved']));
        $this->service->approve($purchase, $this->admin->id);

        $grnService->create($purchase, ['items' => [[
            'purchase_item_id'  => $purchase->items->first()->id,
            'product_id'        => $this->product->id,
            'quantity_received' => 10,
            'quantity_rejected' => 0,
        ]]]);

        $this->assertEquals('received', $purchase->fresh()->status);
        $this->assertEquals(10, $inventory->getStockLevel($this->product->id));

        $this->assertTrue($this->service->delete($purchase));
        // All received stock unwound back to zero.
        $this->assertEquals(0, $inventory->getStockLevel($this->product->id));
        $this->assertSoftDeleted('purchases', ['id' => $purchase->id]);
    }

    public function test_delete_received_purchase_with_sold_stock_never_goes_negative(): void
    {
        $inventory = app(\Modules\Inventory\Services\InventoryService::class);
        $grnService = app(\Modules\Purchase\Services\GrnService::class);

        $purchase = $this->service->create(array_merge($this->purchaseData(), ['status' => 'approved']));
        $this->service->approve($purchase, $this->admin->id);

        $grnService->create($purchase, ['items' => [[
            'purchase_item_id'  => $purchase->items->first()->id,
            'product_id'        => $this->product->id,
            'quantity_received' => 10,
            'quantity_rejected' => 0,
        ]]]);

        // Sell 8 of the 10 received units — only 2 remain on hand.
        $inventory->adjustStock($this->product->id, null, -8, 'sale', 999, 0, 'Sold');
        $this->assertEquals(2, $inventory->getStockLevel($this->product->id));

        // Delete must still succeed, reversing only the 2 reversible units.
        $this->assertTrue($this->service->delete($purchase));
        $this->assertEquals(0, $inventory->getStockLevel($this->product->id));
        $this->assertSoftDeleted('purchases', ['id' => $purchase->id]);
    }

    public function test_list_purchases(): void
    {
        $this->service->create($this->purchaseData());
        $result = $this->service->list();
        $this->assertEquals(1, $result->total());
    }

    public function test_get_stats(): void
    {
        $this->service->create($this->purchaseData());
        $stats = $this->service->getStats();
        $this->assertArrayHasKey('thisMonthPurchases', $stats);
        $this->assertArrayHasKey('pendingOrders', $stats);
    }

    public function test_po_number_is_unique(): void
    {
        $p1 = $this->service->create($this->purchaseData());
        $p2 = $this->service->create($this->purchaseData());
        $this->assertNotEquals($p1->po_number, $p2->po_number);
    }
}
