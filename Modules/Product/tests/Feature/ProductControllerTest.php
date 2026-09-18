<?php

namespace Modules\Product\Tests\Feature;

use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Unit\Models\Unit;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    protected Category $category;
    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::create(['name' => 'Electronics', 'slug' => 'electronics', 'status' => 'active', 'sort_order' => 0]);
        $this->unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc', 'status' => 'active']);
    }

    public function test_unauthenticated_cannot_access_products(): void
    {
        $this->get(route('products.index'))->assertRedirect(route('login'));
    }

    public function test_index_displays_products(): void
    {
        $response = $this->actingAsAdmin()->get(route('products.index'));
        $response->assertStatus(200);
        $response->assertViewIs('product::index');
    }

    public function test_create_page_renders(): void
    {
        $response = $this->actingAsAdmin()->get(route('products.create'));
        $response->assertStatus(200);
        $response->assertViewIs('product::create');
    }

    public function test_store_creates_product(): void
    {
        $response = $this->actingAsAdmin()->post(route('products.store'), [
            'name' => 'Test Product',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'cost_price' => 100,
            'sell_price' => 150,
            'product_type' => 'simple',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', ['name' => 'Test Product']);
    }

    public function test_store_fails_without_name(): void
    {
        $response = $this->actingAsAdmin()->post(route('products.store'), [
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'cost_price' => 100,
            'sell_price' => 150,
            'product_type' => 'simple',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_store_fails_without_category(): void
    {
        $response = $this->actingAsAdmin()->post(route('products.store'), [
            'name' => 'Test Product',
            'unit_id' => $this->unit->id,
            'cost_price' => 100,
            'sell_price' => 150,
            'product_type' => 'simple',
        ]);

        $response->assertSessionHasErrors('category_id');
    }

    public function test_show_displays_product(): void
    {
        $product = $this->createProduct();
        $response = $this->actingAsAdmin()->get(route('products.show', $product));
        $response->assertStatus(200);
        $response->assertViewIs('product::show');
    }

    public function test_edit_page_renders(): void
    {
        $product = $this->createProduct();
        $response = $this->actingAsAdmin()->get(route('products.edit', $product));
        $response->assertStatus(200);
        $response->assertViewIs('product::edit');
    }

    public function test_update_modifies_product(): void
    {
        $product = $this->createProduct();
        $response = $this->actingAsAdmin()->put(route('products.update', $product), [
            'name' => 'Updated Product',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'cost_price' => 100,
            'sell_price' => 200,
            'product_type' => 'simple',
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Updated Product']);
    }

    public function test_destroy_soft_deletes_product(): void
    {
        $product = $this->createProduct();
        $response = $this->actingAsAdmin()->delete(route('products.destroy', $product));

        $response->assertRedirect(route('products.index'));
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_index_with_search_filter(): void
    {
        $this->createProduct('iPhone 15');
        $this->createProduct('Samsung Galaxy');

        $response = $this->actingAsAdmin()->get(route('products.index', ['search' => 'iPhone']));
        $response->assertStatus(200);
        $response->assertSee('iPhone 15');
    }

    public function test_generate_sku_ajax(): void
    {
        $response = $this->actingAsAdmin()->getJson(route('products.ajax.generate-sku', ['category_id' => $this->category->id]));
        $response->assertOk();
        $response->assertJsonStructure(['sku']);
    }

    public function test_generate_barcode_ajax(): void
    {
        $response = $this->actingAsAdmin()->getJson(route('products.ajax.generate-barcode'));
        $response->assertOk();
        $response->assertJsonStructure(['barcode']);
    }

    private function createProduct(string $name = 'Test Product'): Product
    {
        return app(\Modules\Product\Services\ProductService::class)->create([
            'name' => $name,
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'cost_price' => 100,
            'sell_price' => 150,
            'product_type' => 'simple',
            'status' => 'active',
            'vat_rate' => 15,
            'vat_inclusive' => 'yes',
            'discount_type' => 'none',
            'min_stock_alert' => 10,
            'show_in_pos' => true,
            'track_stock' => true,
        ]);
    }
}
