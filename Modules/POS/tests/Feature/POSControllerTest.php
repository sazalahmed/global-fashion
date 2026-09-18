<?php

namespace Modules\POS\Tests\Feature;

use Modules\Branch\Models\Branch;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Unit\Models\Unit;
use Tests\TestCase;

class POSControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Branch::create(['name' => 'Main', 'code' => 'BR-001', 'is_main' => true, 'is_active' => true, 'is_pos_enabled' => true, 'is_ecom_enabled' => false]);
        $cat = Category::create(['name' => 'General', 'slug' => 'general', 'status' => 'active', 'sort_order' => 0]);
        $unit = Unit::create(['name' => 'Pc', 'short_name' => 'pc', 'status' => 'active']);
        Product::create(['name' => 'POS Item', 'slug' => 'pos-item', 'sku' => 'POS-001', 'barcode' => '8801234567890', 'category_id' => $cat->id, 'unit_id' => $unit->id, 'cost_price' => 50, 'sell_price' => 100, 'product_type' => 'simple', 'status' => 'active', 'vat_rate' => 0, 'vat_inclusive' => 'yes', 'discount_type' => 'none', 'min_stock_alert' => 5, 'show_in_pos' => true, 'track_stock' => true, 'created_by' => $this->admin->id]);
    }

    public function test_pos_page_renders(): void
    {
        $this->actingAsAdmin()->get(route('pos.index'))->assertStatus(200);
    }

    public function test_search_products_ajax(): void
    {
        $response = $this->actingAsAdmin()->getJson(route('pos.search-products', ['term' => 'POS']));
        $response->assertOk();
    }

    public function test_get_by_barcode_ajax(): void
    {
        $response = $this->actingAsAdmin()->getJson(route('pos.get-by-barcode', ['barcode' => '8801234567890']));
        $response->assertOk();
    }

    public function test_search_customers_ajax(): void
    {
        $response = $this->actingAsAdmin()->getJson(route('pos.search-customers', ['term' => 'walk']));
        $response->assertOk();
    }
}
