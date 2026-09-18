<?php

namespace Modules\Variant\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Category\Models\Category;
use Modules\Inventory\Models\WarehouseStock;
use Modules\Product\Models\Product;
use Modules\Unit\Models\Unit;
use Modules\Variant\Exceptions\VariantHasSalesException;
use Modules\Variant\Models\ProductVariant;
use Modules\Variant\Models\VariantAttribute;
use Modules\Variant\Models\VariantAttributeValue;
use Modules\Variant\Services\VariantService;
use Tests\TestCase;

class VariantRemovalTest extends TestCase
{
    use RefreshDatabase;

    private VariantService $service;
    private Product $product;
    private VariantAttributeValue $red;
    private VariantAttributeValue $gold;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VariantService::class);

        $category = Category::create(['name' => 'Gen', 'slug' => 'gen', 'status' => 'active', 'sort_order' => 0]);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc', 'status' => 'active']);
        $this->product = Product::create([
            'name' => 'Shirt', 'slug' => 'shirt', 'sku' => 'SH-1',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'cost_price' => 100, 'sell_price' => 200, 'product_type' => 'variable',
            'status' => 'active', 'discount_type' => 'none', 'track_stock' => true,
        ]);

        $color = VariantAttribute::create(['name' => 'Color', 'display_name' => 'Color', 'display_type' => 'button', 'sort_order' => 0, 'status' => 'active']);
        $this->red  = $color->values()->create(['value' => 'Red',  'sort_order' => 0]);
        $this->gold = $color->values()->create(['value' => 'Gold', 'sort_order' => 1]);

        foreach ([$this->red, $this->gold] as $i => $val) {
            $v = ProductVariant::create([
                'product_id' => $this->product->id, 'sku' => 'SH-1-' . $val->value,
                'cost_price' => 100, 'sell_price' => 200, 'is_active' => true, 'is_default' => $i === 0,
            ]);
            $v->attributeValues()->attach($val->id);
            WarehouseStock::create(['product_id' => $this->product->id, 'variant_id' => $v->id, 'quantity' => 5]);
        }
    }

    // ── Private helpers ──

    /**
     * Insert a minimal sales row + a sale_items row referencing the given
     * variant. A real (non-trashed) parent is required now that the history
     * check joins to it to exclude soft-deleted sales.
     *
     * @return int the created sale's id
     */
    private function addSaleItem(int $variantId): int
    {
        $saleId = DB::table('sales')->insertGetId([
            'invoice_number' => 'INV-' . uniqid(),
            'sale_date'      => now()->toDateString(),
            'created_by'     => $this->makeAdmin()->id,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        DB::table('sale_items')->insert([
            'sale_id'      => $saleId,
            'product_id'   => $this->product->id,
            'variant_id'   => $variantId,
            'product_name' => 'Shirt',
            'product_sku'  => 'SH-1-Gold',
            'quantity'     => 1,
            'unit_price'   => 200,
            'subtotal'     => 200,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return $saleId;
    }

    // ── Tests ──

    public function test_removing_an_unused_value_deletes_its_variants_and_stock(): void
    {
        $this->service->removeProductValues($this->product, [$this->gold->id]);

        $this->assertSame(1, $this->product->variants()->count());
        $this->assertDatabaseMissing('product_variants', ['sku' => 'SH-1-Gold']);
        $this->assertSame(0, WarehouseStock::where('product_id', $this->product->id)
            ->whereNotIn('variant_id', $this->product->variants()->pluck('id'))->count());
        $this->assertDatabaseHas('product_variants', ['sku' => 'SH-1-Red']);
    }

    public function test_removing_a_sold_value_throws_and_deletes_nothing(): void
    {
        $goldVariant = $this->product->variants()->where('sku', 'SH-1-Gold')->first();

        $this->addSaleItem($goldVariant->id);

        $this->expectException(VariantHasSalesException::class);

        try {
            $this->service->removeProductValues($this->product, [$this->gold->id]);
        } finally {
            $this->assertSame(2, $this->product->variants()->count());
        }
    }

    public function test_removing_a_value_is_allowed_once_its_only_sale_is_soft_deleted(): void
    {
        $goldVariant = $this->product->variants()->where('sku', 'SH-1-Gold')->first();

        $saleId = $this->addSaleItem($goldVariant->id);
        DB::table('sales')->where('id', $saleId)->update(['deleted_at' => now()]);

        $this->service->removeProductValues($this->product, [$this->gold->id]);

        $this->assertSame(1, $this->product->variants()->count());
        $this->assertDatabaseMissing('product_variants', ['sku' => 'SH-1-Gold']);
    }

    public function test_removing_default_variant_reassigns_default(): void
    {
        $this->service->removeProductValues($this->product, [$this->red->id]);

        $gold = $this->product->variants()->where('sku', 'SH-1-Gold')->first();
        $this->assertTrue((bool) $gold->is_default);
    }

    public function test_removing_a_value_referenced_by_purchases_is_blocked(): void
    {
        $goldVariant = $this->product->variants()->where('sku', 'SH-1-Gold')->first();

        $supplierId = DB::table('suppliers')->insertGetId([
            'company_name'   => 'Test Supplier',
            'contact_person' => 'Someone',
            'phone'          => '0100000000',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
        $purchaseId = DB::table('purchases')->insertGetId([
            'supplier_id' => $supplierId,
            'po_number'   => 'PO-' . uniqid(),
            'po_date'     => now()->toDateString(),
            'created_by'  => $this->makeAdmin()->id,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
        DB::table('purchase_items')->insert([
            'purchase_id' => $purchaseId,
            'product_id'  => $this->product->id,
            'variant_id'  => $goldVariant->id,
            'quantity'    => 2,
            'unit_price'  => 100,
            'line_total'  => 200,
        ]);

        $this->expectException(VariantHasSalesException::class);

        try {
            $this->service->removeProductValues($this->product, [$this->gold->id]);
        } finally {
            $this->assertSame(2, $this->product->variants()->count());
        }
    }

    public function test_remove_with_empty_array_is_a_noop(): void
    {
        $result = $this->service->removeProductValues($this->product, []);

        $this->assertSame(['removed' => 0], $result);
        $this->assertSame(2, $this->product->variants()->count());
    }

    public function test_delete_variant_directly_blocks_when_referenced(): void
    {
        $goldVariant = $this->product->variants()->where('sku', 'SH-1-Gold')->first();
        $this->addSaleItem($goldVariant->id);

        $this->expectException(VariantHasSalesException::class);

        try {
            $this->service->deleteVariant($goldVariant);
        } finally {
            $this->assertDatabaseHas('product_variants', ['sku' => 'SH-1-Gold']); // nothing deleted
        }
    }

    public function test_delete_variant_directly_removes_unused_and_clears_stock(): void
    {
        $goldVariant = $this->product->variants()->where('sku', 'SH-1-Gold')->first();

        $this->service->deleteVariant($goldVariant);

        // Gold variant is gone
        $this->assertDatabaseMissing('product_variants', ['sku' => 'SH-1-Gold']);

        // Gold's warehouse_stock row is also gone
        $this->assertSame(0, WarehouseStock::where('variant_id', $goldVariant->id)->count());

        // Red still exists
        $this->assertDatabaseHas('product_variants', ['sku' => 'SH-1-Red']);
    }

    public function test_remove_values_endpoint_deletes_unused(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->deleteJson(route('products.remove-variant-values', $this->product), [
                'value_ids' => [$this->gold->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'removed' => 1]);

        $this->assertDatabaseMissing('product_variants', ['sku' => 'SH-1-Gold']);
    }

    public function test_remove_values_endpoint_blocks_referenced_with_422(): void
    {
        $admin = $this->makeAdmin();
        $gold = $this->product->variants()->where('sku', 'SH-1-Gold')->first();
        $this->addSaleItem($gold->id);

        $this->actingAs($admin)
            ->deleteJson(route('products.remove-variant-values', $this->product), [
                'value_ids' => [$this->gold->id],
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['message']);

        $this->assertSame(2, $this->product->variants()->count());
    }

    // ── Private helpers (continued) ──

    private function makeAdmin(): \App\Models\User
    {
        return \App\Models\User::create([
            'name'     => 'Test Admin',
            'email'    => 'admin-test-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'status'   => 'active',
        ]);
    }
}
