<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Branch\Models\Branch;
use Modules\Category\Models\Category;
use Modules\Customer\Models\Customer;
use Modules\Ecommerce\Models\CourierProvider;
use Modules\Product\Models\Product;
use Modules\Sale\Services\SaleService;
use Modules\Unit\Models\Unit;
use Tests\TestCase;

/**
 * The Steadfast "delivered" webhook records the courier-collected COD on the
 * sale. COD is only ONE source of money — advances recorded through the
 * Payment module live in payment allocations, and the webhook must combine
 * both instead of overwriting paid_amount with the COD alone.
 */
class SteadfastWebhookCodTest extends TestCase
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

        CourierProvider::updateOrCreate(['slug' => 'steadfast'], [
            'name' => 'Steadfast',
            'api_key' => 'test-key', 'api_secret' => 'test-secret',
            'is_active' => true,
        ]);
    }

    private function createSale(array $payments = []): \Modules\Sale\Models\Sale
    {
        return $this->service->createSale([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'invoice_date' => now()->format('Y-m-d'),
            'source' => 'store',
        ], [
            ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 200, 'discount_amount' => 0],
        ], $payments); // grand_total = 400
    }

    private function postDelivered(\Modules\Sale\Models\Sale $sale, float $codAmount): void
    {
        $this->postJson(route('webhooks.steadfast'), [
            'notification_type' => 'delivery_status',
            'consignment_id' => 999001,
            'invoice' => $sale->invoice_number,
            'status' => 'delivered',
            'cod_amount' => $codAmount,
        ])->assertOk();
    }

    public function test_delivered_cod_adds_to_existing_advance_instead_of_overwriting(): void
    {
        // 100 advance received when the sale was created; 300 sent as COD.
        $sale = $this->createSale([['amount' => 100, 'method' => 'cash']]);
        $this->assertEquals(100, (float) $sale->paid_amount);

        $this->postDelivered($sale, 300);

        $sale->refresh();
        $this->assertEquals(300, (float) $sale->courier_collected_amount);
        $this->assertEquals(400, (float) $sale->paid_amount, 'COD must be added on top of the advance, not replace it');
        $this->assertEquals(0, (float) $sale->due_amount);
        $this->assertEquals('paid', $sale->payment_status);
    }

    public function test_delivered_cod_without_advance_marks_collected_as_paid(): void
    {
        $sale = $this->createSale();

        $this->postDelivered($sale, 400);

        $sale->refresh();
        $this->assertEquals(400, (float) $sale->paid_amount);
        $this->assertEquals(0, (float) $sale->due_amount);
        $this->assertEquals('paid', $sale->payment_status);
    }

    public function test_delivered_webhook_is_idempotent_with_advance(): void
    {
        $sale = $this->createSale([['amount' => 100, 'method' => 'cash']]);

        $this->postDelivered($sale, 300);
        $this->postDelivered($sale, 300); // courier retries the same event

        $sale->refresh();
        $this->assertEquals(400, (float) $sale->paid_amount);
        $this->assertEquals(0, (float) $sale->due_amount);
    }

    public function test_delivered_cod_shortfall_becomes_discount(): void
    {
        // Rider settled a 400 parcel for 340 — the 60 shortfall is a price
        // concession, recorded automatically as a discount, not left as due.
        $sale = $this->createSale();

        $this->postDelivered($sale, 340);

        $sale->refresh();
        $this->assertEquals(60, (float) $sale->discount_amount);
        $this->assertEquals('fixed', $sale->discount_type);
        $this->assertEquals(340, (float) $sale->grand_total);
        $this->assertEquals(340, (float) $sale->paid_amount);
        $this->assertEquals(0, (float) $sale->due_amount);
        $this->assertEquals('paid', $sale->payment_status);
    }

    public function test_amount_change_after_delivery_records_shortfall_as_discount(): void
    {
        // The real production sequence (S20260715008): delivered at full COD,
        // then Steadfast revises the collected amount 20 seconds later.
        $sale = $this->createSale();
        $this->postDelivered($sale, 400);

        $postAmountChange = fn () => $this->postJson(route('webhooks.steadfast'), [
            'notification_type' => 'tracking_update',
            'consignment_id' => 999001,
            'invoice' => $sale->invoice_number,
            'tracking_message' => 'Amount has been changed  from "400" to "330"',
        ])->assertOk();

        $postAmountChange();

        $sale->refresh();
        $this->assertEquals(330, (float) $sale->courier_collected_amount);
        $this->assertEquals(70, (float) $sale->discount_amount);
        $this->assertEquals(330, (float) $sale->grand_total);
        $this->assertEquals(0, (float) $sale->due_amount);
        $this->assertEquals('paid', $sale->payment_status);

        // Courier retries must not compound the discount.
        $postAmountChange();
        $sale->refresh();
        $this->assertEquals(70, (float) $sale->discount_amount);
        $this->assertEquals(330, (float) $sale->grand_total);
    }

    public function test_cod_shortfall_with_advance_becomes_discount(): void
    {
        // 100 advance + 250 collected on a 400 sale → 50 shortfall becomes
        // discount; the advance still counts toward the paid total.
        $sale = $this->createSale([['amount' => 100, 'method' => 'cash']]);

        $this->postDelivered($sale, 250);

        $sale->refresh();
        $this->assertEquals(50, (float) $sale->discount_amount);
        $this->assertEquals(350, (float) $sale->grand_total);
        $this->assertEquals(350, (float) $sale->paid_amount);
        $this->assertEquals(0, (float) $sale->due_amount);
        $this->assertEquals('paid', $sale->payment_status);
    }

    public function test_partial_delivery_shortfall_is_not_discounted(): void
    {
        // partial_delivered means goods came back — the gap is settled by a
        // SaleReturn, never by an automatic discount.
        $sale = $this->createSale();

        $this->postJson(route('webhooks.steadfast'), [
            'notification_type' => 'delivery_status',
            'consignment_id' => 999001,
            'invoice' => $sale->invoice_number,
            'status' => 'partial_delivered',
            'cod_amount' => 250,
        ])->assertOk();

        $sale->refresh();
        $this->assertEquals(0, (float) $sale->discount_amount);
        $this->assertEquals(400, (float) $sale->grand_total);
        $this->assertEquals(250, (float) $sale->paid_amount);
        $this->assertEquals(150, (float) $sale->due_amount);
        $this->assertTrue((bool) $sale->needs_return);
    }
}
