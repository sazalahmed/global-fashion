<?php

namespace Modules\Supplier\Tests\Unit;

use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Services\SupplierService;
use Tests\TestCase;

class SupplierServiceTest extends TestCase
{
    protected SupplierService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SupplierService::class);
    }

    private function supplierData(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Test Supplier Ltd',
            'contact_person' => 'John Doe',
            'phone' => '01700000001',
            'email' => 'supplier@test.com',
            'status' => 'active',
            'opening_balance' => 0,
        ], $overrides);
    }

    public function test_list_suppliers(): void
    {
        $this->service->create($this->supplierData());
        $this->service->create($this->supplierData(['company_name' => 'Another Supplier', 'phone' => '01700000002']));

        $results = $this->service->list();
        $this->assertEquals(2, $results->total());
    }

    public function test_list_with_search(): void
    {
        $this->service->create($this->supplierData(['company_name' => 'Alpha Corp']));
        $this->service->create($this->supplierData(['company_name' => 'Beta Inc', 'phone' => '01700000002']));

        $results = $this->service->list(['search' => 'Alpha']);
        $this->assertEquals(1, $results->total());
    }

    public function test_create_supplier(): void
    {
        $supplier = $this->service->create($this->supplierData());

        $this->assertInstanceOf(Supplier::class, $supplier);
        $this->assertEquals('Test Supplier Ltd', $supplier->company_name);
        $this->assertDatabaseHas('suppliers', ['company_name' => 'Test Supplier Ltd']);
    }

    public function test_create_supplier_sets_due_balance_from_opening(): void
    {
        $supplier = $this->service->create($this->supplierData(['opening_balance' => 5000]));
        $this->assertEquals(5000, $supplier->due_balance);
    }

    public function test_update_supplier(): void
    {
        $supplier = $this->service->create($this->supplierData());
        $updated = $this->service->update($supplier, ['company_name' => 'Updated Name']);

        $this->assertEquals('Updated Name', $updated->company_name);
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'company_name' => 'Updated Name']);
    }

    public function test_delete_supplier(): void
    {
        $supplier = $this->service->create($this->supplierData());
        $result = $this->service->delete($supplier);

        $this->assertTrue($result);
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    public function test_get_stats(): void
    {
        $this->service->create($this->supplierData());
        $this->service->create($this->supplierData(['company_name' => 'Inactive', 'phone' => '01700000002', 'status' => 'inactive']));

        $stats = $this->service->getStats();
        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['active']);
        $this->assertArrayHasKey('totalPayable', $stats);
    }

    public function test_get_active_suppliers(): void
    {
        $this->service->create($this->supplierData());
        $this->service->create($this->supplierData(['company_name' => 'Inactive', 'phone' => '01700000002', 'status' => 'inactive']));

        $active = $this->service->getActiveSuppliers();
        $this->assertCount(1, $active);
    }

    public function test_get_ledger(): void
    {
        $supplier = $this->service->create($this->supplierData());
        $ledger = $this->service->getLedger($supplier);

        $this->assertArrayHasKey('entries', $ledger);
        $this->assertArrayHasKey('totalDebit', $ledger);
        $this->assertArrayHasKey('totalCredit', $ledger);
        $this->assertArrayHasKey('currentBalance', $ledger);
    }

    public function test_record_payment(): void
    {
        $this->actingAs($this->admin);
        $supplier = $this->service->create($this->supplierData(['opening_balance' => 10000]));

        $payment = $this->service->recordPayment($supplier, [
            'amount' => 5000,
            'payment_method' => 'Cash',
            'payment_date' => now()->toDateString(),
            'payment_type' => 'payment',
        ]);

        $this->assertNotNull($payment);
        $supplier->refresh();
        $this->assertEquals(5000, (float) $supplier->due_balance);
    }
}
