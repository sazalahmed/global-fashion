<?php

namespace Modules\Sale\Tests\Unit;

use Modules\Accounting\Database\Seeders\ChartOfAccountsSeeder;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryLine;
use Modules\Branch\Models\Branch;
use Modules\Category\Models\Category;
use Modules\Customer\Models\Customer;
use Modules\Product\Models\Product;
use Modules\Sale\Models\Sale;
use Modules\Sale\Services\SaleService;
use Modules\Unit\Models\Unit;
use Tests\TestCase;

/**
 * Revenue recognition: a sale reaches the general ledger when it is
 * delivered, not when it is merely confirmed. Sales travel to 'delivered'
 * through changeStatus(), so that path has to post.
 */
class SaleJournalPostingTest extends TestCase
{
    protected SaleService $service;
    protected Branch $branch;
    protected Product $product;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ChartOfAccountsSeeder::class);
        $this->actingAs($this->admin);
        $this->service = app(SaleService::class);
        $this->branch = Branch::create(['name' => 'Main', 'code' => 'BR-001', 'is_main' => true, 'is_active' => true, 'is_pos_enabled' => true, 'is_ecom_enabled' => false]);
        $category = Category::create(['name' => 'General', 'slug' => 'general', 'status' => 'active', 'sort_order' => 0]);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc', 'status' => 'active']);
        $this->product = Product::create([
            'name' => 'Test Item', 'slug' => 'test-item', 'sku' => 'TST-001',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'cost_price' => 100, 'sell_price' => 200, 'product_type' => 'simple',
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

    private function makeSale(array $overrides = []): Sale
    {
        return $this->service->createSale(array_merge([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'invoice_date' => now()->format('Y-m-d'),
            'source' => 'store',
            'discount_type' => null,
            'discount_value' => 0,
            'tax_rate' => 0,
            'shipping_charge' => 0,
        ], $overrides), [[
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 200,
            'discount_amount' => 0,
        ]]);
    }

    private function postedEntry(Sale $sale): ?JournalEntry
    {
        return JournalEntry::where('source_type', 'sale')
            ->where('source_id', $sale->id)
            ->where('status', 'posted')
            ->first();
    }

    /** Net movement on an account code across this sale's posted journal. */
    private function amountOn(Sale $sale, string $accountCode): float
    {
        $entry = $this->postedEntry($sale);
        if (! $entry) {
            return 0.0;
        }

        return (float) JournalEntryLine::query()
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entry_lines.journal_entry_id', $entry->id)
            ->where('accounts.account_code', $accountCode)
            ->selectRaw('COALESCE(SUM(debit_amount - credit_amount), 0) AS net')
            ->value('net');
    }

    public function test_delivering_an_online_sale_posts_revenue_to_the_ledger(): void
    {
        // Online orders are not earned at creation, so this one starts unposted
        // and reaching 'delivered' is what puts it in the ledger.
        $sale = $this->makeSale(['source' => 'ecommerce', 'status' => 'pending']);
        $this->assertNull($this->postedEntry($sale), 'an undelivered online order must not post revenue');

        $this->service->changeStatus($sale, 'delivered');

        $this->assertNotNull($this->postedEntry($sale->fresh()));
        // DR Accounts Receivable / CR Sales Revenue
        $this->assertEquals(400.0, $this->amountOn($sale, '1010'));
        $this->assertEquals(-400.0, $this->amountOn($sale, '4001'));
    }

    public function test_delivering_a_sale_posts_cost_of_goods_sold(): void
    {
        $sale = $this->makeSale();
        $this->service->changeStatus($sale, 'delivered');

        // DR COGS / CR Inventory at the cost the stock ledger recorded
        // (2 units x 100 cost).
        $this->assertEquals(200.0, $this->amountOn($sale, '5001'));
        $this->assertEquals(-200.0, $this->amountOn($sale, '1020'));
    }

    /**
     * A counter sale hands the goods over at the till, so 'confirmed' IS its
     * delivery. POS writes sales as 'confirmed' and never advances them, so
     * requiring 'delivered' here would stop POS revenue reaching the ledger.
     */
    public function test_confirming_a_counter_sale_posts_revenue(): void
    {
        $sale = $this->makeSale(['source' => 'pos']);
        $this->service->changeStatus($sale, 'confirmed');

        $this->assertNotNull($this->postedEntry($sale->fresh()));
        $this->assertEquals(400.0, $this->amountOn($sale, '1010'));
    }

    /**
     * An online order is earned once it's actually handed to the courier —
     * merely being 'confirmed' isn't enough, since stock hasn't shipped yet.
     */
    public function test_confirming_an_online_sale_does_not_post_revenue(): void
    {
        $sale = $this->makeSale(['source' => 'ecommerce']);
        $this->service->changeStatus($sale, 'confirmed');

        $this->assertNull($this->postedEntry($sale->fresh()));

        $this->service->changeStatus($sale->fresh(), 'courier');
        $this->assertNotNull($this->postedEntry($sale->fresh()));
    }

    /**
     * A 'store' sale sent to courier earns its revenue right there — same
     * moment its stock leaves (ONLINE_STOCK_OUT_STATUSES), not held back for
     * delivery confirmation.
     */
    public function test_a_store_sale_posts_revenue_once_sent_to_courier(): void
    {
        $sale = $this->makeSale(['source' => 'store']);
        $this->service->changeStatus($sale, 'confirmed');
        $this->assertNull($this->postedEntry($sale->fresh()), 'a confirmed store sale is not yet earned');

        $this->service->changeStatus($sale->fresh(), 'courier');
        $this->assertNotNull($this->postedEntry($sale->fresh()), 'revenue follows stock: earned once handed to courier');
    }

    public function test_a_draft_sale_never_posts_revenue(): void
    {
        $sale = $this->makeSale();
        $this->service->changeStatus($sale, 'draft');

        $this->assertNull($this->postedEntry($sale->fresh()));
    }

    public function test_delivering_twice_does_not_double_post(): void
    {
        $sale = $this->makeSale();
        $this->service->changeStatus($sale, 'delivered');
        $this->service->changeStatus($sale->fresh(), 'delivered');

        $this->assertEquals(1, JournalEntry::where('source_type', 'sale')
            ->where('source_id', $sale->id)->where('status', 'posted')->count());
        $this->assertEquals(400.0, $this->amountOn($sale, '1010'));
    }

    public function test_sale_journal_balances(): void
    {
        $sale = $this->makeSale();
        $this->service->changeStatus($sale, 'delivered');
        $entry = $this->postedEntry($sale->fresh());

        $totals = JournalEntryLine::where('journal_entry_id', $entry->id)
            ->selectRaw('COALESCE(SUM(debit_amount),0) dr, COALESCE(SUM(credit_amount),0) cr')->first();

        $this->assertEquals((float) $totals->dr, (float) $totals->cr);
        $this->assertGreaterThan(0, (float) $totals->dr);
    }

    public function test_cancelling_a_delivered_sale_voids_its_journal(): void
    {
        $sale = $this->makeSale();
        $this->service->changeStatus($sale, 'delivered');
        $this->assertNotNull($this->postedEntry($sale->fresh()));

        $this->service->cancelSale($sale->fresh());

        $this->assertNull($this->postedEntry($sale->fresh()), 'a cancelled sale must not keep posted revenue');
    }
}
