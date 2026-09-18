<?php

namespace Tests\Feature;

use App\Services\Search\SearchableRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Category\Models\Category;
use Modules\Customer\Models\Customer;
use Modules\Product\Models\Product;
use Modules\Sale\Models\Sale;
use Modules\Unit\Models\Unit;
use Modules\Variant\Models\ProductVariant;
use Tests\TestCase;

/**
 * Coverage for the expanded global-search scope: variant-aware product search,
 * relationship (party-name) search on transactions, and registration of the
 * newly searchable entity types.
 */
class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(string $sku = 'PRD-1'): Product
    {
        $category = Category::create(['name' => 'Gen', 'slug' => 'gen', 'status' => 'active', 'sort_order' => 0]);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc', 'status' => 'active']);

        return Product::create([
            'name' => 'Test Shirt', 'slug' => 'test-shirt-' . $sku, 'sku' => $sku,
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'cost_price' => 100, 'sell_price' => 200, 'product_type' => 'variable',
            'status' => 'active', 'discount_type' => 'none', 'track_stock' => true,
        ]);
    }

    public function test_newly_added_entity_types_are_registered(): void
    {
        $types = array_keys(app(SearchableRegistry::class)->all());

        $this->assertContains('Payment', $types);
        $this->assertContains('Lender', $types);
        $this->assertContains('Customer Group', $types);
        $this->assertContains('Supplier Group', $types);
    }

    public function test_dead_config_references_are_not_registered(): void
    {
        $types = array_keys(app(SearchableRegistry::class)->all());

        // Modules/models that do not exist must not appear.
        $this->assertNotContains('Stock Transfer', $types);
        $this->assertNotContains('Delivery Challan', $types);
        $this->assertNotContains('Installment Plan', $types);
    }

    public function test_product_is_found_by_its_variant_sku(): void
    {
        $product = $this->makeProduct('SHIRT-1');
        ProductVariant::create([
            'product_id' => $product->id,
            'sku'        => 'SHIRT-1-RED-XL',
            'barcode'    => 'BC-9001',
        ]);

        $ids = Product::query()->globalSearch('RED-XL')->pluck('id')->all();
        $this->assertContains($product->id, $ids);

        // And by variant barcode.
        $byBarcode = Product::query()->globalSearch('BC-9001')->pluck('id')->all();
        $this->assertContains($product->id, $byBarcode);
    }

    public function test_product_search_still_matches_own_columns(): void
    {
        $product = $this->makeProduct('ABC-123');

        $ids = Product::query()->globalSearch('ABC-123')->pluck('id')->all();
        $this->assertContains($product->id, $ids);
    }

    public function test_sale_is_found_by_customer_name(): void
    {
        $customer = Customer::create([
            'name' => 'Zaynab Traders', 'phone' => '01711000111', 'is_active' => true,
        ]);

        $sale = Sale::create([
            'customer_id'    => $customer->id,
            'invoice_number' => 'INV-TESTCUST-1',
            'sale_date'      => now(),
            'source'         => 'store',
            'status'         => 'completed',
            'payment_status' => 'unpaid',
            'subtotal'       => 1000,
            'discount_type'  => 'fixed',
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_rate'       => 15,
            'tax_amount'     => 150,
            'shipping_charge' => 0,
            'grand_total'    => 1150,
            'paid_amount'    => 0,
            'due_amount'     => 1150,
        ]);

        $byName = Sale::query()->globalSearch('Zaynab')->pluck('id')->all();
        $this->assertContains($sale->id, $byName);

        $byPhone = Sale::query()->globalSearch('01711000111')->pluck('id')->all();
        $this->assertContains($sale->id, $byPhone);

        // Original invoice-number search must still work.
        $byInvoice = Sale::query()->globalSearch('INV-TESTCUST-1')->pluck('id')->all();
        $this->assertContains($sale->id, $byInvoice);
    }
}
