<?php

namespace Modules\Sale\Tests\Unit;

use Modules\Branch\Models\Branch;
use Modules\Category\Models\Category;
use Modules\Customer\Models\Customer;
use Modules\Product\Models\Product;
use Modules\Sale\Models\Sale;
use Modules\Sale\Services\SaleService;
use Modules\Unit\Models\Unit;
use Tests\TestCase;

class SaleServiceTest extends TestCase
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
    }

    private function saleData(array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'invoice_date' => now()->format('Y-m-d'),
            'source' => 'store',
            'discount_type' => null,
            'discount_value' => 0,
            'tax_rate' => 0,
            'shipping_charge' => 0,
        ], $overrides);
    }

    private function saleItems(): array
    {
        return [
            [
                'product_id' => $this->product->id,
                'quantity' => 2,
                'unit_price' => 200,
                'discount_amount' => 0,
            ],
        ];
    }

    public function test_create_sale(): void
    {
        $sale = $this->service->createSale($this->saleData(), $this->saleItems());

        $this->assertInstanceOf(Sale::class, $sale);
        $this->assertNotEmpty($sale->invoice_number);
        $this->assertNotNull($sale->status);
        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
        $this->assertDatabaseHas('sale_items', ['sale_id' => $sale->id, 'quantity' => 2]);
    }

    public function test_create_sale_calculates_totals(): void
    {
        $sale = $this->service->createSale($this->saleData(), $this->saleItems());

        $this->assertEquals(400, $sale->subtotal);
        $this->assertEquals(400, $sale->grand_total);
        $this->assertEquals(400, $sale->due_amount);
        $this->assertEquals('unpaid', $sale->payment_status);
    }

    public function test_create_sale_with_discount(): void
    {
        $data = $this->saleData(['discount_type' => 'fixed', 'discount_value' => 50]);
        $sale = $this->service->createSale($data, $this->saleItems());

        $this->assertEquals(50, $sale->discount_amount);
        $this->assertEquals(350, $sale->grand_total);
    }

    public function test_create_sale_with_percentage_discount(): void
    {
        $data = $this->saleData(['discount_type' => 'percentage', 'discount_value' => 10]);
        $sale = $this->service->createSale($data, $this->saleItems());

        $this->assertEquals(40, $sale->discount_amount);
        $this->assertEquals(360, $sale->grand_total);
    }

    public function test_create_sale_with_payment(): void
    {
        $payments = [['amount' => 200, 'method' => 'cash']];
        $sale = $this->service->createSale($this->saleData(), $this->saleItems(), $payments);

        $this->assertEquals(200, $sale->paid_amount);
        $this->assertEquals(200, $sale->due_amount);
        $this->assertEquals('partial', $sale->payment_status);
    }

    public function test_create_sale_payment_without_account_falls_back_to_default_account(): void
    {
        $cash = \Modules\Payment\Models\PaymentAccount::create([
            'name' => 'Cash', 'account_type' => 'cash',
            'is_default' => true, 'is_active' => true,
        ]);

        // User typed an advance amount but never picked an account/method.
        $payments = [['amount' => 150]];
        $sale = $this->service->createSale($this->saleData(), $this->saleItems(), $payments);

        $this->assertEquals(150, $sale->paid_amount);
        $this->assertEquals('partial', $sale->payment_status);
        $this->assertDatabaseHas('payments', [
            'reference'          => $sale->invoice_number,
            'amount'             => 150,
            'payment_account_id' => $cash->id,
            'payment_method'     => 'cash',
        ]);
    }

    public function test_update_sale_keeps_payment_account_when_form_sends_none(): void
    {
        $bkash = \Modules\Payment\Models\PaymentAccount::create([
            'name' => 'bKash', 'account_type' => 'mobile_banking',
            'is_default' => false, 'is_active' => true,
        ]);

        $payments = [['amount' => 100, 'payment_account_id' => $bkash->id]];
        $sale = $this->service->createSale($this->saleData(), $this->saleItems(), $payments);
        $payment = \Modules\Payment\Models\Payment::where('reference', $sale->invoice_number)->firstOrFail();
        $this->assertEquals($bkash->id, $payment->payment_account_id);

        // Account deleted later; the edit form then can't offer it and posts
        // an empty payment_account_id for the untouched row.
        $bkash->delete();
        $this->service->updateSale($sale, $this->saleData(), [
            ['product_id' => $this->product->id, 'quantity' => 2, 'price' => 200, 'discount' => 0],
        ], [
            ['id' => $payment->id, 'amount' => 100, 'payment_account_id' => '', 'reference' => $sale->invoice_number],
        ]);

        $payment->refresh();
        $this->assertEquals($bkash->id, $payment->payment_account_id, 'Untouched advance row must keep its original account');
        $this->assertEquals('mobile_banking', $payment->payment_method);
    }

    public function test_combo_lines_persist_tags_group_and_allocate(): void
    {
        $combo = \Modules\Ecommerce\Models\Combo::create([
            'name' => 'Eid Pack', 'combo_price' => 1000,
            'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true,
        ]);
        $group = 'cmb-test-1';

        // Two combo component lines (same group, allocated to sum the combo
        // price) + one plain product line.
        $items = [
            ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 540, 'discount_amount' => 0,
                'combo_id' => $combo->id, 'combo_group' => $group, 'combo_name' => 'Eid Pack', 'combo_price' => 1000],
            ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 460, 'discount_amount' => 0,
                'combo_id' => $combo->id, 'combo_group' => $group, 'combo_name' => 'Eid Pack', 'combo_price' => 1000],
            ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 200, 'discount_amount' => 0],
        ];

        $sale = $this->service->createSale($this->saleData(), $items)->fresh('items');

        $comboLines = $sale->items->whereNotNull('combo_id');
        $this->assertCount(2, $comboLines);
        $this->assertEquals($combo->id, $comboLines->first()->combo_id);
        $this->assertEquals($group, $comboLines->first()->combo_group);
        $this->assertEquals('Eid Pack', $comboLines->first()->combo_name);
        $this->assertEquals(1000.0, (float) $comboLines->first()->combo_price);

        // Allocated component subtotals sum exactly to the combo price.
        $this->assertEquals(1000.0, (float) $comboLines->sum('subtotal'));

        // Derived sale-level flag.
        $this->assertTrue($sale->has_combo);

        // Grouping: one combo group + one plain line = 2 display groups.
        $groups = $sale->itemsGroupedByCombo();
        $this->assertCount(2, $groups);
        $comboGroup = $groups->firstWhere('combo', true);
        $this->assertNotNull($comboGroup);
        $this->assertEquals('Eid Pack', $comboGroup['name']);
        $this->assertCount(2, $comboGroup['items']);
    }

    public function test_sale_without_combo_has_no_combo_flag(): void
    {
        $sale = $this->service->createSale($this->saleData(), $this->saleItems())->fresh('items');
        $this->assertFalse($sale->has_combo);
        $this->assertCount(1, $sale->itemsGroupedByCombo());
    }

    public function test_create_sale_with_full_payment(): void
    {
        $payments = [['amount' => 400, 'method' => 'cash']];
        $sale = $this->service->createSale($this->saleData(), $this->saleItems(), $payments);

        $this->assertEquals(400, $sale->paid_amount);
        $this->assertEquals(0, $sale->due_amount);
        $this->assertEquals('paid', $sale->payment_status);
    }

    public function test_create_sale_updates_customer_totals(): void
    {
        $this->service->createSale($this->saleData(), $this->saleItems());
        $this->customer->refresh();

        $this->assertGreaterThan(0, $this->customer->total_purchased);
    }

    public function test_cancel_sale(): void
    {
        $sale = $this->service->createSale($this->saleData(), $this->saleItems());
        $this->service->cancelSale($sale);
        $sale->refresh();

        $this->assertEquals('cancelled', $sale->status);
    }

    /** Build an ecommerce-sourced sale mirrored by a pending online order. */
    private function makeEcommerceSaleWithOrder(string $orderNumber): array
    {
        $sale = $this->service->createSale($this->saleData(['source' => 'ecommerce']), $this->saleItems());
        $order = \Modules\Ecommerce\Models\EcommerceOrder::create([
            'order_number'   => $orderNumber,
            'customer_id'    => $this->customer->id,
            'customer_name'  => $this->customer->name,
            'customer_phone' => $this->customer->phone,
            'sale_id'        => $sale->id,
            'status'         => 'pending',
            'payment_status' => 'unpaid',
            'subtotal'       => 400,
            'grand_total'    => 400,
        ]);

        return [$sale, $order];
    }

    public function test_delete_sale_removes_linked_ecommerce_order_from_customer_view(): void
    {
        [$sale, $order] = $this->makeEcommerceSaleWithOrder('ORD-DEL-0001');

        $this->service->deleteSale($sale);

        // A deleted sale must not leave its online order visible (as "pending")
        // in the customer's My Orders panel, which reads EcommerceOrder directly.
        $this->assertFalse(
            \Modules\Ecommerce\Models\EcommerceOrder::where('sale_id', $sale->id)->exists(),
            'Deleted sale left its ecommerce order visible to the customer.'
        );
        $this->assertTrue($order->fresh()->trashed());
    }

    public function test_cancel_sale_marks_linked_ecommerce_order_cancelled(): void
    {
        [$sale, $order] = $this->makeEcommerceSaleWithOrder('ORD-CAN-0001');

        $this->service->cancelSale($sale);

        $this->assertSame('cancelled', $order->fresh()->status);
    }

    /**
     * Admin/courier cancellation is the dominant refund scenario (RTO), and it
     * never went through EcommerceService::updateOrderStatus (mobile-only) —
     * so a fulfilled online order cancelled from /admin/sales (or via the
     * Steadfast webhook, which also routes through cancelSale/changeStatus)
     * must still send the GA4 refund event.
     */
    public function test_cancel_sale_reports_ga4_refund_for_previously_reported_order(): void
    {
        \Illuminate\Support\Facades\Bus::fake();
        \Modules\Setting\Models\Setting::set('tracking', 'ga4_measurement_id', 'G-ABC123');
        \Modules\Setting\Models\Setting::set('tracking', 'ga4_api_secret', 'SECRET');
        \Modules\Setting\Models\Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');

        [$sale, $order] = $this->makeEcommerceSaleWithOrder('ORD-CAN-0002');
        $order->forceFill(['status' => 'confirmed', 'purchase_reported_at' => now()])->save();

        $this->service->cancelSale($sale);

        \Illuminate\Support\Facades\Bus::assertDispatched(
            \Modules\Ecommerce\Jobs\SendGa4McEvent::class,
            function ($job) use ($order) {
                return $job->eventName === 'refund'
                    && $job->params['transaction_id'] === $order->order_number;
            }
        );
        $this->assertNotNull($order->fresh()->refund_reported_at);
    }

    public function test_confirming_ecommerce_sale_no_longer_reports_purchase(): void
    {
        \Illuminate\Support\Facades\Bus::fake();
        \Modules\Setting\Models\Setting::set('tracking', 'fbpixel_id', '1234567890');
        \Modules\Setting\Models\Setting::set('tracking', 'fbpixel_access_token', 'TOKEN');

        [$sale, $order] = $this->makeEcommerceSaleWithOrder('ORD-NP-0001');

        $this->service->changeStatus($sale, 'confirmed');

        // Purchase reports at placement now — admin fulfilment must not re-report.
        \Illuminate\Support\Facades\Bus::assertNotDispatched(\Modules\Ecommerce\Jobs\SendFbCapiEvent::class);
        $this->assertNull($order->fresh()->purchase_reported_at);
    }

    public function test_delete_sale_voids_payment_journal_entry(): void
    {
        $payments = [['amount' => 400, 'method' => 'cash']];
        $sale = $this->service->createSale($this->saleData(), $this->saleItems(), $payments);

        $payment = \Modules\Payment\Models\Payment::find($sale->allocations()->value('payment_id'));
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->journal_entry_id);

        $journalEntryId = $payment->journal_entry_id;
        $this->assertEquals('posted', \Modules\Accounting\Models\JournalEntry::find($journalEntryId)->status);

        $this->service->deleteSale($sale);

        // The payment is removed and its receipt entry (DR Cash / CR AR) is voided,
        // so no orphaned posted journal entry is left behind on the ledger.
        $this->assertSoftDeleted('payments', ['id' => $payment->id]);
        $this->assertEquals('voided', \Modules\Accounting\Models\JournalEntry::find($journalEntryId)->status);
        $this->assertDatabaseMissing('journal_entries', [
            'source_type' => 'payment',
            'source_id'   => $payment->id,
            'status'      => 'posted',
        ]);
    }

    public function test_find_sale(): void
    {
        $sale = $this->service->createSale($this->saleData(), $this->saleItems());
        $found = $this->service->find($sale->id);

        $this->assertEquals($sale->id, $found->id);
        $this->assertTrue($found->relationLoaded('items'));
        $this->assertTrue($found->relationLoaded('customer'));
    }

    public function test_list_sales(): void
    {
        $this->service->createSale($this->saleData(), $this->saleItems());
        $this->service->createSale($this->saleData(), $this->saleItems());

        $results = $this->service->list();
        $this->assertEquals(2, $results->total());
    }

    public function test_list_with_search(): void
    {
        $sale = $this->service->createSale($this->saleData(), $this->saleItems());

        $results = $this->service->list(['search' => $sale->invoice_number]);
        $this->assertEquals(1, $results->total());
    }

    public function test_list_searches_by_customer_phone_and_name(): void
    {
        $this->service->createSale($this->saleData(), $this->saleItems());

        // Exact phone as stored on the customer (01700000001).
        $this->assertEquals(1, $this->service->list(['search' => '01700000001'])->total());
        // Dashed variant, as typed by staff — must match despite formatting.
        $this->assertEquals(1, $this->service->list(['search' => '01700-000001'])->total());
        // Customer name.
        $this->assertEquals(1, $this->service->list(['search' => 'Test Customer'])->total());
        // Non-matching phone finds nothing.
        $this->assertEquals(0, $this->service->list(['search' => '01999999999'])->total());
    }

    public function test_get_stats_reports_fixed_time_scoped_kpis(): void
    {
        // Revenue is recognised on delivery — deliver a 400 sale dated today.
        $this->service->createSale($this->saleData(['sale_status' => 'delivered']), $this->saleItems());

        $stats = $this->service->getStats();

        $this->assertEquals(400, (float) $stats['today_sales']);
        $this->assertEquals(400, (float) $stats['month_sales']);
        $this->assertEquals(1, $stats['total_sales_count']);
    }

    public function test_list_totals_follow_the_active_filters(): void
    {
        $this->service->createSale($this->saleData(), $this->saleItems()); // 400 unpaid
        $this->service->createSale($this->saleData(), $this->saleItems(), [['amount' => 400, 'method' => 'cash']]); // 400 paid

        $all = $this->service->getListTotals([]);
        $this->assertEquals(800, (float) $all['grand_total']);
        $this->assertEquals(400, (float) $all['paid_amount']);
        $this->assertEquals(400, (float) $all['due_amount']);

        $paidOnly = $this->service->getListTotals(['payment_status' => 'paid']);
        $this->assertEquals(400, (float) $paidOnly['grand_total']);
        $this->assertEquals(400, (float) $paidOnly['paid_amount']);
        $this->assertEquals(0, (float) $paidOnly['due_amount']);
    }

    public function test_list_with_payment_status_filter(): void
    {
        $this->service->createSale($this->saleData(), $this->saleItems());
        $payments = [['amount' => 400, 'method' => 'cash']];
        $this->service->createSale($this->saleData(), $this->saleItems(), $payments);

        $unpaid = $this->service->list(['payment_status' => 'unpaid']);
        $this->assertEquals(1, $unpaid->total());
    }

    public function test_get_stats(): void
    {
        $this->service->createSale($this->saleData(), $this->saleItems());
        $stats = $this->service->getStats();

        $this->assertArrayHasKey('today_sales', $stats);
        $this->assertArrayHasKey('month_sales', $stats);
        $this->assertArrayHasKey('total_due', $stats);
    }

    public function test_invoice_number_is_unique(): void
    {
        $s1 = $this->service->createSale($this->saleData(), $this->saleItems());
        $s2 = $this->service->createSale($this->saleData(), $this->saleItems());

        $this->assertNotEquals($s1->invoice_number, $s2->invoice_number);
    }
}
