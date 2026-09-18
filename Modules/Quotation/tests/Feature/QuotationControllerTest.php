<?php

namespace Modules\Quotation\Tests\Feature;

use Modules\Branch\Models\Branch;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Unit\Models\Unit;
use Tests\TestCase;

class QuotationControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Branch::create(['name' => 'Main', 'code' => 'BR-001', 'is_main' => true, 'is_active' => true, 'is_pos_enabled' => true, 'is_ecom_enabled' => false]);
        $cat = Category::create(['name' => 'G', 'slug' => 'g', 'status' => 'active', 'sort_order' => 0]);
        $unit = Unit::create(['name' => 'Pc', 'short_name' => 'pc', 'status' => 'active']);
        Product::create(['name' => 'P', 'slug' => 'p', 'sku' => 'P-001', 'category_id' => $cat->id, 'unit_id' => $unit->id, 'cost_price' => 100, 'sell_price' => 200, 'product_type' => 'simple', 'status' => 'active', 'vat_rate' => 15, 'vat_inclusive' => 'yes', 'discount_type' => 'none', 'min_stock_alert' => 10, 'show_in_pos' => true, 'track_stock' => true, 'created_by' => $this->admin->id]);
    }

    public function test_index_renders(): void
    {
        $this->actingAsAdmin()->get(route('quotations.index'))->assertStatus(200);
    }

    public function test_create_renders(): void
    {
        $this->actingAsAdmin()->get(route('quotations.create'))->assertStatus(200);
    }

    public function test_store_creates_quotation(): void
    {
        $this->actingAsAdmin()->post(route('quotations.store'), [
            'branch_id' => 1, 'quotation_date' => now()->format('Y-m-d'),
            'valid_until' => now()->addDays(30)->format('Y-m-d'),
            'items' => [['product_id' => 1, 'quantity' => 2, 'unit_price' => 200]],
        ])->assertRedirect();
        $this->assertDatabaseHas('quotations', ['branch_id' => 1]);
    }
}
