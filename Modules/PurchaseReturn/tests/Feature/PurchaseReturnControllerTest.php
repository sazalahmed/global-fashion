<?php

namespace Modules\PurchaseReturn\Tests\Feature;

use Modules\Branch\Models\Branch;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Purchase\Services\PurchaseService;
use Modules\PurchaseReturn\Services\PurchaseReturnService;
use Modules\Supplier\Models\Supplier;
use Modules\Unit\Models\Unit;
use Tests\TestCase;

class PurchaseReturnControllerTest extends TestCase
{
    protected Branch $branch;
    protected Product $product;
    protected Supplier $supplier;
    protected int $purchaseId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->branch = Branch::create(['name' => 'Main', 'code' => 'BR-001', 'is_main' => true, 'is_active' => true, 'is_pos_enabled' => true, 'is_ecom_enabled' => false]);
        $this->supplier = Supplier::create(['company_name' => 'Supplier Co', 'phone' => '01711111111', 'status' => 'active', 'opening_balance' => 0, 'due_balance' => 0]);
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

        $purchase = app(PurchaseService::class)->create([
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'po_date' => now()->format('Y-m-d'),
            'status' => 'draft',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 10, 'unit_price' => 100, 'tax_rate' => 0],
            ],
        ]);
        $this->purchaseId = $purchase->id;
    }

    public function test_index_renders(): void
    {
        $this->actingAsAdmin()->get(route('purchase-returns.index'))->assertStatus(200);
    }

    public function test_create_renders(): void
    {
        $this->actingAsAdmin()->get(route('purchase-returns.create'))->assertStatus(200);
    }

    public function test_store_creates_purchase_return(): void
    {
        $response = $this->actingAsAdmin()->post(route('purchase-returns.store'), [
            'purchase_id' => $this->purchaseId,
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Defective items',
            'status' => 'draft',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 100, 'tax_amount' => 0],
            ],
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('purchase_returns', ['purchase_id' => $this->purchaseId]);
    }

    public function test_complete_purchase_return(): void
    {
        $return = app(PurchaseReturnService::class)->create([
            'purchase_id' => $this->purchaseId,
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Damaged',
            'status' => 'draft',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 100, 'tax_amount' => 0],
            ],
        ]);
        $this->actingAsAdmin()->post(route('purchase-returns.complete', $return))->assertRedirect();
    }
}
