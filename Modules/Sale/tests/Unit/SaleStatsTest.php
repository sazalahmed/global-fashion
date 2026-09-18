<?php

namespace Modules\Sale\Tests\Unit;

use Modules\Branch\Models\Branch;
use Modules\Category\Models\Category;
use Modules\Customer\Models\Customer;
use Modules\Product\Models\Product;
use Modules\Sale\Services\SaleService;
use Modules\Unit\Models\Unit;
use Tests\TestCase;

/**
 * The four KPI cards on the sales list have to agree with each other: a sale
 * that counts toward the due card must also count as a sale. Previously the
 * money/count cards recognised only 'delivered', so an order out with the
 * courier showed in "Total Due" while being invisible in "Today's Sales".
 */
class SaleStatsTest extends TestCase
{
    protected SaleService $service;
    protected Branch $branch;
    protected Product $product;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->admin);
        $this->service = app(SaleService::class);
        $this->branch = Branch::create(['name' => 'Main', 'code' => 'BR-001', 'is_main' => true, 'is_active' => true, 'is_pos_enabled' => true, 'is_ecom_enabled' => false]);
        $category = Category::create(['name' => 'General', 'slug' => 'general', 'status' => 'active', 'sort_order' => 0]);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc', 'status' => 'active']);
        $this->product = Product::create([
            'name' => 'Test Item', 'slug' => 'test-item', 'sku' => 'TST-001',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'cost_price' => 100, 'sell_price' => 100, 'product_type' => 'simple',
            'status' => 'active', 'vat_rate' => 0, 'vat_inclusive' => 'no',
            'discount_type' => 'none', 'min_stock_alert' => 10, 'show_in_pos' => true,
            'track_stock' => true, 'created_by' => $this->admin->id,
        ]);
        $this->customer = Customer::create([
            'name' => 'Test Customer', 'phone' => '01700000001',
            'customer_group' => 'Retail', 'is_active' => true,
            'created_by' => $this->admin->id,
        ]);
    }

    /** One sale today at the given status, worth quantity x 100. */
    private function saleAt(string $status, int $quantity): void
    {
        $this->service->createSale([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'invoice_date' => now()->format('Y-m-d'),
            'sale_date' => now()->format('Y-m-d'),
            'source' => 'ecommerce',
            'status' => $status,
            'discount_type' => null,
            'discount_value' => 0,
            'tax_rate' => 0,
            'shipping_charge' => 0,
        ], [[
            'product_id' => $this->product->id,
            'quantity' => $quantity,
            'unit_price' => 100,
            'discount_amount' => 0,
        ]]);
    }

    public function test_stats_count_the_whole_active_pipeline_not_only_delivered(): void
    {
        $this->saleAt('delivered', 10);          // 1000
        $this->saleAt('courier', 5);             // 500
        $this->saleAt('pending', 3);             // 300
        $this->saleAt('packing', 2);             // 200

        $stats = $this->service->getStats();

        $this->assertEquals(2000.0, (float) $stats['today_sales']);
        $this->assertEquals(2000.0, (float) $stats['month_sales']);
        $this->assertEquals(4, (int) $stats['total_sales_count']);
    }

    public function test_stats_exclude_non_orders_and_cancellations(): void
    {
        $this->saleAt('delivered', 10);          // 1000 — counts
        $this->saleAt('incompleted', 7);         // abandoned checkout
        $this->saleAt('cancelled', 6);
        $this->saleAt('draft', 5);

        $stats = $this->service->getStats();

        $this->assertEquals(1000.0, (float) $stats['today_sales']);
        $this->assertEquals(1, (int) $stats['total_sales_count']);
    }

    /**
     * The defect from the screenshot: a delivered sale plus one with the
     * courier read as a single sale while the due card counted both.
     */
    public function test_sales_count_agrees_with_the_due_card(): void
    {
        $this->saleAt('delivered', 10);          // 1000, unpaid
        $this->saleAt('courier', 5);             // 500, unpaid

        $stats = $this->service->getStats();

        $this->assertEquals(2, (int) $stats['total_sales_count']);
        $this->assertEquals(1500.0, (float) $stats['month_sales']);
        $this->assertEquals(1500.0, (float) $stats['total_due']);
    }
}
