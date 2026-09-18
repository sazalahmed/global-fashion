<?php

namespace Modules\Accounting\Tests\Unit;

use Modules\Accounting\Database\Seeders\ChartOfAccountsSeeder;
use Modules\Accounting\Services\SimpleMoneyService;
use Modules\Customer\Models\Customer;
use Modules\Payment\Services\PaymentService;
use Tests\TestCase;

class SimpleMoneyServiceTest extends TestCase
{
    protected SimpleMoneyService $service;
    protected PaymentService $payments;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        // Cash flow reads cash/bank accounts by code and name, so the chart of
        // accounts has to exist — migrations alone don't create it.
        $this->seed(ChartOfAccountsSeeder::class);
        $this->service = app(SimpleMoneyService::class);
        $this->payments = app(PaymentService::class);
        $this->actingAs($this->admin);
        $this->customer = Customer::create([
            'name' => 'Cashflow Customer', 'phone' => '01700000099',
            'customer_group' => 'Retail', 'is_active' => true,
            'created_by' => $this->admin->id,
        ]);
    }

    private function receive(string $paymentType, float $amount): void
    {
        $this->payments->create([
            'direction'      => 'receive',
            'party_type'     => 'customer',
            'party_id'       => $this->customer->id,
            'payment_type'   => $paymentType,
            'amount'         => $amount,
            'payment_method' => 'cash',
            'payment_date'   => now()->format('Y-m-d'),
        ]);
    }

    public function test_cashflow_splits_sale_takings_from_due_collection(): void
    {
        // The two customer receipt types mean different things and get their
        // own rows: against_invoice settles an outstanding due, sale_payment
        // is cash taken against the sale itself.
        $this->receive('against_invoice', 3000);
        $this->receive('sale_payment', 2000);

        $flow = $this->service->cashFlow();

        $this->assertArrayHasKey('customer_due_receive', $flow['data']);
        $this->assertEquals(3000.0, $flow['data']['customer_due_receive']);
        $this->assertEquals(2000.0, $flow['data']['product_sale']);

        // Whatever the split, neither receipt may go missing from Cash In —
        // that was the original defect.
        $this->assertEquals(5000.0, $flow['totalReceive']);
    }

    public function test_cashflow_totals_include_customer_against_invoice_receipts(): void
    {
        $this->receive('against_invoice', 3000);

        $flow = $this->service->cashFlow();

        // The receipt must be part of total Cash In so that
        // Opening + Cash In − Cash Out reconciles to the closing balance.
        $this->assertEquals(3000.0, $flow['totalReceive']);
        $this->assertEquals($flow['totalReceive'] - $flow['totalPay'], $flow['currentBalance']);
    }

    public function test_cashflow_reconciles_when_payment_is_soft_deleted_without_voiding(): void
    {
        $payment = $this->payments->create([
            'direction'      => 'receive',
            'party_type'     => 'customer',
            'party_id'       => $this->customer->id,
            'payment_type'   => 'against_invoice',
            'amount'         => 3000,
            'payment_method' => 'cash',
            'payment_date'   => now()->format('Y-m-d'),
        ]);

        // Soft-delete the payment row while leaving its journal posted — the
        // state legacy data is in. The GL still holds the cash, so the report
        // must still account for it rather than silently dropping it.
        $payment->delete();

        $flow = $this->service->cashFlow();

        $this->assertEquals(3000.0, $flow['data']['other_in']);
        $this->assertEquals($flow['totalReceive'] - $flow['totalPay'], $flow['currentBalance']);
    }
}
