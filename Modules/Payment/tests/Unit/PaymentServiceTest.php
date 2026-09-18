<?php

namespace Modules\Payment\Tests\Unit;

use Modules\Accounting\Models\Account;
use Modules\Customer\Models\Customer;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentService;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    protected PaymentService $service;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PaymentService::class);
        $this->actingAs($this->admin);
        $this->customer = Customer::create([
            'name' => 'Test Customer', 'phone' => '01700000001',
            'customer_group' => 'Retail', 'is_active' => true,
            'created_by' => $this->admin->id,
        ]);
    }

    private function paymentData(array $overrides = []): array
    {
        return array_merge([
            'direction' => 'receive',
            'party_type' => 'customer',
            'party_id' => $this->customer->id,
            'payment_type' => 'sale_payment',
            'amount' => 5000,
            'payment_method' => 'cash',
            'payment_date' => now()->format('Y-m-d'),
        ], $overrides);
    }

    public function test_list_payments(): void
    {
        $this->service->create($this->paymentData());
        $results = $this->service->list();
        $this->assertEquals(1, $results->total());
    }

    public function test_find_payment(): void
    {
        $payment = $this->service->create($this->paymentData());
        $found = $this->service->find($payment->id);

        $this->assertEquals($payment->id, $found->id);
        $this->assertTrue($found->relationLoaded('paymentAccount'));
    }

    public function test_create_payment_with_journal(): void
    {
        $payment = $this->service->create($this->paymentData());

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertNotEmpty($payment->payment_number);
        $this->assertNotNull($payment->journal_entry_id);
        $this->assertDatabaseHas('payments', ['id' => $payment->id]);
    }

    public function test_create_payment_maps_method_to_account(): void
    {
        $payment = $this->service->create($this->paymentData(['payment_method' => 'bkash']));
        $this->assertNotNull($payment->payment_account_id);
    }

    public function test_delete_payment(): void
    {
        $payment = $this->service->create($this->paymentData());
        $result = $this->service->delete($payment);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('payments', ['id' => $payment->id, 'deleted_at' => null]);
    }

    public function test_get_stats(): void
    {
        $this->service->create($this->paymentData());
        $stats = $this->service->getStats();

        $this->assertArrayHasKey('total_received', $stats);
        $this->assertArrayHasKey('total_paid', $stats);
        $this->assertArrayHasKey('total_transactions', $stats);
        $this->assertArrayHasKey('today_received', $stats);
    }

    public function test_map_method_to_account(): void
    {
        $accountId = $this->service->mapMethodToAccount('cash');
        $this->assertGreaterThan(0, $accountId);

        $account = Account::find($accountId);
        $this->assertEquals('1001', $account->account_code);
    }

    public function test_payment_number_is_unique(): void
    {
        $p1 = $this->service->create($this->paymentData());
        $p2 = $this->service->create($this->paymentData());

        $this->assertNotEquals($p1->payment_number, $p2->payment_number);
    }
}
