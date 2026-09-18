<?php

namespace Modules\Variant\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Category\Models\Category;
use Modules\Inventory\Models\WarehouseStock;
use Modules\Product\Models\Product;
use Modules\Unit\Models\Unit;
use Modules\Variant\Models\ProductVariant;
use Modules\Variant\Models\VariantAttribute;
use Modules\Variant\Models\VariantAttributeValue;
use Modules\Variant\Services\VariantService;
use Tests\TestCase;

/**
 * Regression coverage for the "Color created separately" bug: adding a second
 * attribute (e.g. Size) to a product that already had single-attribute variants
 * (Color-only) must replace the stale single-value combos with the full matrix,
 * not leave the Color-only rows lingering alongside the Color×Size rows.
 */
class VariantRegenerationTest extends TestCase
{
    use RefreshDatabase;

    private VariantService $service;
    private Product $product;
    private VariantAttribute $color;
    private VariantAttribute $size;
    private VariantAttributeValue $red;
    private VariantAttributeValue $blue;
    private VariantAttributeValue $small;
    private VariantAttributeValue $medium;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VariantService::class);

        $category = Category::create(['name' => 'Gen', 'slug' => 'gen', 'status' => 'active', 'sort_order' => 0]);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc', 'status' => 'active']);
        $this->product = Product::create([
            'name' => 'Kurta', 'slug' => 'kurta', 'sku' => 'KU-1',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'cost_price' => 600, 'sell_price' => 1800, 'product_type' => 'variable',
            'status' => 'active', 'discount_type' => 'none', 'track_stock' => true,
        ]);

        $this->color = VariantAttribute::create(['name' => 'Color', 'display_name' => 'Color', 'display_type' => 'button', 'sort_order' => 0, 'status' => 'active']);
        $this->red  = $this->color->values()->create(['value' => 'Red',  'sort_order' => 0]);
        $this->blue = $this->color->values()->create(['value' => 'Blue', 'sort_order' => 1]);

        $this->size = VariantAttribute::create(['name' => 'Size', 'display_name' => 'Size', 'display_type' => 'button', 'sort_order' => 1, 'status' => 'active']);
        $this->small  = $this->size->values()->create(['value' => 'S', 'sort_order' => 0]);
        $this->medium = $this->size->values()->create(['value' => 'M', 'sort_order' => 1]);
    }

    public function test_adding_a_second_attribute_replaces_stale_single_value_variants(): void
    {
        // Step 1: admin first generates Color-only variants.
        $this->service->generateVariants($this->product, [[$this->red->id, $this->blue->id]]);
        $this->assertSame(2, $this->product->variants()->count(), 'Color-only generation should make 2 variants');

        // Step 2: admin then adds Size — the edit page re-fires with the full selection.
        $this->service->generateVariants($this->product, [
            [$this->red->id, $this->blue->id],
            [$this->small->id, $this->medium->id],
        ]);

        // Only the 2×2 matrix should remain — the Color-only rows are gone.
        $this->assertSame(4, $this->product->variants()->count(), 'Should be exactly the Color×Size matrix');

        // Every surviving variant must carry both a Color and a Size value.
        foreach ($this->product->variants()->with('attributeValues')->get() as $v) {
            $this->assertSame(2, $v->attributeValues->count(), "Variant {$v->sku} should have 2 attribute values");
        }

        // A default still exists after the stale (possibly-default) rows were pruned.
        $this->assertSame(1, $this->product->variants()->where('is_default', true)->count());
    }

    public function test_regeneration_clears_orphaned_warehouse_stock(): void
    {
        $this->service->generateVariants($this->product, [[$this->red->id, $this->blue->id]]);

        // Seed stock on the color-only variants.
        foreach ($this->product->variants()->get() as $v) {
            WarehouseStock::create(['product_id' => $this->product->id, 'variant_id' => $v->id, 'quantity' => 5]);
        }

        $this->service->generateVariants($this->product, [
            [$this->red->id, $this->blue->id],
            [$this->small->id, $this->medium->id],
        ]);

        $liveIds = $this->product->variants()->pluck('id');
        $orphans = WarehouseStock::where('product_id', $this->product->id)
            ->whereNotIn('variant_id', $liveIds)->count();
        $this->assertSame(0, $orphans, 'Stock rows for pruned variants must be removed');
    }

    public function test_generate_endpoint_serializes_reused_variants_without_null_attribute(): void
    {
        // Build the full matrix once.
        $payload = [[$this->red->id, $this->blue->id], [$this->small->id, $this->medium->id]];
        $this->service->generateVariants($this->product, $payload);

        $admin = \App\Models\User::create([
            'name' => 'Test Admin', 'email' => 'admin-' . uniqid() . '@example.com',
            'password' => bcrypt('password'), 'status' => 'active',
        ]);

        // Re-fire generate with the SAME selection: every combo already exists, so
        // all returned variants are reused. The endpoint must serialize their
        // attribute names (regression: "Attempt to read property name on null").
        $response = $this->actingAs($admin)
            ->postJson(route('products.generate-variants', $this->product), [
                'attribute_value_ids' => $payload,
            ]);

        $response->assertOk()->assertJsonCount(4, 'variants');
        $first = $response->json('variants.0.values.0');
        $this->assertArrayHasKey('attribute', $first);
        $this->assertNotNull($first['attribute']);
    }

    public function test_stale_variant_with_sales_history_is_preserved(): void
    {
        $this->service->generateVariants($this->product, [[$this->red->id, $this->blue->id]]);
        $redOnly = $this->product->variants()->whereHas('attributeValues', fn ($q) => $q->where('variant_attribute_values.id', $this->red->id))
            ->whereDoesntHave('attributeValues', fn ($q) => $q->where('variant_attribute_values.id', $this->small->id))
            ->first();

        // Give the Color-only Red variant a sale, making it unsafe to hard-delete.
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('sale_items')->insert([
                'sale_id' => 1, 'product_id' => $this->product->id, 'variant_id' => $redOnly->id,
                'product_name' => 'Kurta', 'product_sku' => $redOnly->sku, 'quantity' => 1,
                'unit_price' => 1800, 'subtotal' => 1800, 'created_at' => now(), 'updated_at' => now(),
            ]);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->service->generateVariants($this->product, [
            [$this->red->id, $this->blue->id],
            [$this->small->id, $this->medium->id],
        ]);

        // The sold Color-only variant survives; the unreferenced Blue-only one is pruned.
        $this->assertDatabaseHas('product_variants', ['id' => $redOnly->id]);
        $this->assertSame(5, $this->product->variants()->count(), '4 matrix + 1 preserved sold variant');
    }
}
