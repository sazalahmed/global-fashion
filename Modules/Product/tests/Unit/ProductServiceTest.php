<?php

namespace Modules\Product\Tests\Unit;

use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Services\ProductService;
use Modules\Unit\Models\Unit;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    protected ProductService $service;
    protected Category $category;
    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->admin);
        $this->service = app(ProductService::class);
        $this->category = Category::create(['name' => 'Electronics', 'slug' => 'electronics', 'status' => 'active', 'sort_order' => 0]);
        $this->unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc', 'status' => 'active']);
    }

    private function validProductData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Product',
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
        ], $overrides);
    }

    public function test_create_product(): void
    {
        $product = $this->service->create($this->validProductData());

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('Test Product', $product->name);
        $this->assertNotEmpty($product->sku);
        $this->assertNotEmpty($product->slug);
        $this->assertDatabaseHas('products', ['name' => 'Test Product']);
    }

    public function test_create_product_generates_unique_sku(): void
    {
        $p1 = $this->service->create($this->validProductData(['name' => 'Product One']));
        $p2 = $this->service->create($this->validProductData(['name' => 'Product Two']));

        $this->assertNotEquals($p1->sku, $p2->sku);
    }

    public function test_create_product_with_tags(): void
    {
        $product = $this->service->create($this->validProductData(), [], 'phone,gadget,electronics');

        $this->assertCount(3, $product->tags);
        $this->assertDatabaseHas('tags', ['name' => 'phone']);
    }

    public function test_list_products_with_search(): void
    {
        $this->service->create($this->validProductData(['name' => 'iPhone 15']));
        $this->service->create($this->validProductData(['name' => 'Samsung Galaxy']));

        $results = $this->service->list(['search' => 'iPhone']);
        $this->assertEquals(1, $results->total());
    }

    public function test_list_products_with_category_filter(): void
    {
        $cat2 = Category::create(['name' => 'Clothing', 'slug' => 'clothing', 'status' => 'active', 'sort_order' => 1]);
        $this->service->create($this->validProductData(['name' => 'Phone']));
        $this->service->create($this->validProductData(['name' => 'Shirt', 'category_id' => $cat2->id]));

        $results = $this->service->list(['category_id' => $this->category->id]);
        $this->assertEquals(1, $results->total());
    }

    public function test_find_product(): void
    {
        $product = $this->service->create($this->validProductData());
        $found = $this->service->find($product->id);

        $this->assertEquals($product->id, $found->id);
        $this->assertTrue($found->relationLoaded('category'));
    }

    public function test_update_product(): void
    {
        $product = $this->service->create($this->validProductData());
        $updated = $this->service->update($product, ['name' => 'Updated Product', 'sell_price' => 200]);

        $this->assertEquals('Updated Product', $updated->name);
        $this->assertEquals(200, $updated->sell_price);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Updated Product']);
    }

    public function test_delete_product(): void
    {
        $product = $this->service->create($this->validProductData());
        $result = $this->service->delete($product);

        $this->assertTrue($result);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_generate_sku(): void
    {
        $sku = $this->service->generateSku($this->category->id);
        $this->assertNotEmpty($sku);
        $this->assertStringStartsWith('ELE', $sku);
    }

    public function test_generate_barcode(): void
    {
        $barcode = $this->service->generateBarcode();
        $this->assertEquals(13, strlen($barcode));
        $this->assertStringStartsWith('880', $barcode);
    }

    public function test_duplicate_product(): void
    {
        $original = $this->service->create($this->validProductData());
        $duplicate = $this->service->duplicate($original);

        $this->assertNotEquals($original->id, $duplicate->id);
        $this->assertNotEquals($original->sku, $duplicate->sku);
        $this->assertEquals('draft', $duplicate->status);
        $this->assertDatabaseCount('products', 2);
    }

    public function test_get_stats(): void
    {
        $this->service->create($this->validProductData(['name' => 'P1']));
        $this->service->create($this->validProductData(['name' => 'P2', 'status' => 'inactive']));

        $stats = $this->service->getStats();
        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['active']);
    }
}
