<?php

namespace Modules\Purchase\Tests\Feature;

use Modules\Branch\Models\Branch;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Supplier\Models\Supplier;
use Modules\Unit\Models\Unit;
use Tests\TestCase;

class PurchaseControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Branch::create(['name' => 'Main', 'code' => 'BR-001', 'is_main' => true, 'is_active' => true, 'is_pos_enabled' => true, 'is_ecom_enabled' => false]);
        Supplier::create(['company_name' => 'Supplier', 'contact_person' => 'J', 'phone' => '01811111111', 'status' => 'active', 'payment_terms' => 'Net 30', 'opening_balance' => 0, 'credit_limit' => 100000]);
        $cat = Category::create(['name' => 'G', 'slug' => 'g', 'status' => 'active', 'sort_order' => 0]);
        $unit = Unit::create(['name' => 'Pc', 'short_name' => 'pc', 'status' => 'active']);
        Product::create(['name' => 'Item', 'slug' => 'item', 'sku' => 'I-001', 'category_id' => $cat->id, 'unit_id' => $unit->id, 'cost_price' => 100, 'sell_price' => 200, 'product_type' => 'simple', 'status' => 'active', 'vat_rate' => 15, 'vat_inclusive' => 'yes', 'discount_type' => 'none', 'min_stock_alert' => 10, 'show_in_pos' => true, 'track_stock' => true, 'created_by' => $this->admin->id]);
    }

    public function test_index_renders(): void
    {
        $this->actingAsAdmin()->get(route('purchases.index'))->assertStatus(200);
    }

    public function test_create_renders(): void
    {
        $this->actingAsAdmin()->get(route('purchases.create'))->assertStatus(200);
    }

    public function test_store_creates_purchase(): void
    {
        $response = $this->actingAsAdmin()->post(route('purchases.store'), [
            'supplier_id' => 1, 'branch_id' => 1, 'po_date' => now()->format('Y-m-d'),
            'payment_terms' => 'Net 30', 'status' => 'draft',
            'items' => [['product_id' => 1, 'quantity' => 10, 'unit_price' => 100, 'discount_amount' => 0, 'tax_rate' => 15]],
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('purchases', ['supplier_id' => 1]);
    }

    public function test_show_displays(): void
    {
        $po = app(\Modules\Purchase\Services\PurchaseService::class)->create([
            'supplier_id' => 1, 'branch_id' => 1, 'po_date' => now()->format('Y-m-d'),
            'payment_terms' => 'Net 30', 'status' => 'draft',
            'items' => [['product_id' => 1, 'quantity' => 5, 'unit_price' => 100, 'discount_amount' => 0, 'tax_rate' => 15]],
        ]);
        $this->actingAsAdmin()->get(route('purchases.show', $po))->assertStatus(200);
    }

    public function test_approve_changes_status(): void
    {
        $po = app(\Modules\Purchase\Services\PurchaseService::class)->create([
            'supplier_id' => 1, 'branch_id' => 1, 'po_date' => now()->format('Y-m-d'),
            'payment_terms' => 'Net 30', 'status' => 'pending',
            'items' => [['product_id' => 1, 'quantity' => 5, 'unit_price' => 100, 'discount_amount' => 0, 'tax_rate' => 15]],
        ]);
        $this->actingAsAdmin()->post(route('purchases.approve', $po))->assertRedirect();
        $this->assertDatabaseHas('purchases', ['id' => $po->id, 'status' => 'approved']);
    }
}
