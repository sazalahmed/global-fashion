<?php

namespace Modules\Quotation\Tests\Unit;

use Modules\Branch\Models\Branch;
use Modules\Category\Models\Category;
use Modules\Customer\Models\Customer;
use Modules\Product\Models\Product;
use Modules\Quotation\Models\Quotation;
use Modules\Quotation\Services\QuotationService;
use Modules\Unit\Models\Unit;
use Tests\TestCase;

class QuotationServiceTest extends TestCase
{
    protected QuotationService $service;
    protected Customer $customer;
    protected Product $product;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(QuotationService::class);
        $this->actingAs($this->admin);
        $this->branch = Branch::create(['name' => 'Main', 'code' => 'BR-001', 'is_main' => true, 'is_active' => true, 'is_pos_enabled' => true, 'is_ecom_enabled' => false]);
        $category = Category::create(['name' => 'General', 'slug' => 'general', 'status' => 'active', 'sort_order' => 0]);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc', 'status' => 'active']);
        $this->product = Product::create([
            'name' => 'Test Item', 'slug' => 'test-item', 'sku' => 'TST-001',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'cost_price' => 100, 'sell_price' => 200, 'product_type' => 'simple',
            'status' => 'active', 'vat_rate' => 15, 'vat_inclusive' => 'yes',
            'discount_type' => 'none', 'min_stock_alert' => 10, 'show_in_pos' => true,
            'track_stock' => true, 'created_by' => $this->admin->id,
        ]);
        $this->customer = Customer::create([
            'name' => 'Test Customer', 'phone' => '01700000001',
            'customer_group' => 'Retail', 'is_active' => true,
            'created_by' => $this->admin->id,
        ]);
    }

    private function quotationData(array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'quotation_date' => now()->format('Y-m-d'),
            'valid_until' => now()->addDays(30)->format('Y-m-d'),
            'discount_type' => null,
            'discount_value' => 0,
            'tax_rate' => 0,
            'shipping_charge' => 0,
        ], $overrides);
    }

    private function items(): array
    {
        return [
            ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 200, 'discount_amount' => 0],
        ];
    }

    public function test_list_quotations(): void
    {
        $this->service->create($this->quotationData(), $this->items());
        $results = $this->service->list();
        $this->assertEquals(1, $results->total());
    }

    public function test_create_quotation(): void
    {
        $quotation = $this->service->create($this->quotationData(), $this->items());

        $this->assertInstanceOf(Quotation::class, $quotation);
        $this->assertNotEmpty($quotation->quotation_number);
        $this->assertEquals(400, (float) $quotation->grand_total);
    }

    public function test_update_quotation(): void
    {
        $quotation = $this->service->create($this->quotationData(), $this->items());
        $updated = $this->service->update($quotation, $this->quotationData(['notes' => 'Updated']), $this->items());
        $this->assertEquals('Updated', $updated->notes);
    }

    public function test_duplicate_quotation(): void
    {
        $original = $this->service->create($this->quotationData(), $this->items());
        $duplicate = $this->service->duplicate($original);

        $this->assertNotEquals($original->id, $duplicate->id);
        $this->assertNotEquals($original->quotation_number, $duplicate->quotation_number);
        $this->assertEquals('draft', $duplicate->status);
    }

    public function test_mark_expired(): void
    {
        $this->service->create($this->quotationData([
            'valid_until' => now()->subDays(5)->format('Y-m-d'),
        ]), $this->items());

        $count = $this->service->markExpired();
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function test_get_stats(): void
    {
        $this->service->create($this->quotationData(), $this->items());
        $stats = $this->service->getStats();

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('accepted', $stats);
        $this->assertArrayHasKey('expired', $stats);
    }

    public function test_calculate_totals(): void
    {
        $items = [
            ['product_id' => $this->product->id, 'quantity' => 3, 'unit_price' => 100, 'discount_amount' => 0],
        ];
        $data = ['discount_type' => 'fixed', 'discount_value' => 50, 'tax_rate' => 15, 'shipping_charge' => 100];
        $totals = $this->service->calculateTotals($items, $data);

        $this->assertEquals(300, $totals['subtotal']);
        $this->assertEquals(50, $totals['discount_amount']);
        $this->assertGreaterThan(0, $totals['tax_amount']);
    }
}
