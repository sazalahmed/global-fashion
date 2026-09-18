<?php

namespace Modules\Payment\Tests\Feature;

use Modules\Customer\Models\Customer;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentService;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = Customer::create([
            'name' => 'Test Customer', 'phone' => '01700000001',
            'customer_group' => 'Retail', 'is_active' => true,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_index_renders(): void
    {
        $this->actingAsAdmin()->get(route('payments.index'))->assertStatus(200);
    }

    public function test_create_renders(): void
    {
        $this->actingAsAdmin()->get(route('payments.create'))->assertStatus(200);
    }

    public function test_store_creates_payment(): void
    {
        $response = $this->actingAsAdmin()->post(route('payments.store'), [
            'direction' => 'receive',
            'party_type' => 'customer',
            'party_id' => $this->customer->id,
            'payment_type' => 'sale_payment',
            'amount' => 5000,
            'payment_method' => 'cash',
            'payment_date' => now()->format('Y-m-d'),
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('payments', ['party_id' => $this->customer->id, 'amount' => 5000]);
    }

    public function test_show_displays_payment(): void
    {
        $payment = app(PaymentService::class)->create([
            'direction' => 'receive',
            'party_type' => 'customer',
            'party_id' => $this->customer->id,
            'payment_type' => 'sale_payment',
            'amount' => 3000,
            'payment_method' => 'cash',
            'payment_date' => now()->format('Y-m-d'),
        ]);
        $this->actingAsAdmin()->get(route('payments.show', $payment))->assertStatus(200);
    }

    public function test_destroy_deletes_payment(): void
    {
        $payment = app(PaymentService::class)->create([
            'direction' => 'receive',
            'party_type' => 'customer',
            'party_id' => $this->customer->id,
            'payment_type' => 'sale_payment',
            'amount' => 2000,
            'payment_method' => 'cash',
            'payment_date' => now()->format('Y-m-d'),
        ]);
        $this->actingAsAdmin()->delete(route('payments.destroy', $payment))->assertRedirect();
    }

    public function test_party_search_ajax(): void
    {
        $response = $this->actingAsAdmin()->getJson(route('payments.party-search', [
            'type' => 'customer',
            'term' => 'Test',
        ]));
        $response->assertOk();
    }

    public function test_advances_renders(): void
    {
        $this->actingAsAdmin()->get(route('payments.advances'))->assertStatus(200);
    }
}
