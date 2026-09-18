<?php

namespace Modules\Customer\Tests\Unit;

use Modules\Branch\Models\Branch;
use Modules\Customer\Models\Customer;
use Modules\Customer\Services\CustomerService;
use Tests\TestCase;

class CustomerServiceTest extends TestCase
{
    protected CustomerService $service;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->admin);
        $this->service = app(CustomerService::class);
        $this->branch = Branch::create(['name' => 'Main', 'code' => 'BR-001', 'is_main' => true, 'is_active' => true, 'is_pos_enabled' => true, 'is_ecom_enabled' => false]);
    }

    private function customerData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Customer',
            'phone' => '01700' . rand(100000, 999999),
            'customer_group' => 'Retail',
            'is_active' => true,
        ], $overrides);
    }

    public function test_create_customer(): void
    {
        $customer = $this->service->create($this->customerData());
        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertDatabaseHas('customers', ['name' => 'Test Customer']);
    }

    public function test_list_customers(): void
    {
        $this->service->create($this->customerData(['name' => 'Customer A', 'phone' => '01711111111']));
        $this->service->create($this->customerData(['name' => 'Customer B', 'phone' => '01722222222']));
        $result = $this->service->list();
        $this->assertEquals(2, $result->total());
    }

    public function test_list_with_search(): void
    {
        $this->service->create($this->customerData(['name' => 'Rahim Khan', 'phone' => '01711111111']));
        $this->service->create($this->customerData(['name' => 'Karim Ahmed', 'phone' => '01722222222']));
        $result = $this->service->list(['search' => 'Rahim']);
        $this->assertEquals(1, $result->total());
    }

    public function test_find_customer(): void
    {
        $customer = $this->service->create($this->customerData());
        $found = $this->service->find($customer->id);
        $this->assertEquals($customer->id, $found->id);
    }

    public function test_update_customer(): void
    {
        $customer = $this->service->create($this->customerData());
        $updated = $this->service->update($customer, ['name' => 'Updated Name']);
        $this->assertEquals('Updated Name', $updated->name);
    }

    public function test_delete_customer(): void
    {
        $customer = $this->service->create($this->customerData());
        $this->service->delete($customer);
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_search_returns_limited_results(): void
    {
        $this->service->create($this->customerData(['name' => 'Test One', 'phone' => '01711111111']));
        $results = $this->service->search('Test');
        $this->assertCount(1, $results);
    }

    public function test_quick_create(): void
    {
        $customer = $this->service->quickCreate(['name' => 'Quick Customer', 'phone' => '01799999999']);
        $this->assertDatabaseHas('customers', ['name' => 'Quick Customer', 'phone' => '01799999999']);
    }

    public function test_get_stats(): void
    {
        $this->service->create($this->customerData(['phone' => '01711111111']));
        $stats = $this->service->getStats();
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('active', $stats);
        $this->assertEquals(1, $stats['total']);
    }

    public function test_get_ledger(): void
    {
        $customer = $this->service->create($this->customerData());
        $ledger = $this->service->getLedger($customer->id);
        $this->assertArrayHasKey('entries', $ledger);
        $this->assertArrayHasKey('totalDebit', $ledger);
        $this->assertArrayHasKey('totalCredit', $ledger);
        $this->assertArrayHasKey('currentBalance', $ledger);
    }
}
