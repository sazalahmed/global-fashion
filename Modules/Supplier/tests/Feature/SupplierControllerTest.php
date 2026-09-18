<?php

namespace Modules\Supplier\Tests\Feature;

use Modules\Supplier\Models\Supplier;
use Tests\TestCase;

class SupplierControllerTest extends TestCase
{
    public function test_index_renders(): void
    {
        $this->actingAsAdmin()->get(route('supplier.index'))->assertStatus(200);
    }

    public function test_store_creates_supplier(): void
    {
        $this->actingAsAdmin()->post(route('supplier.store'), [
            'company_name' => 'Test Supplier',
            'contact_person' => 'John',
            'phone' => '01811111111',
        ])->assertRedirect();
        $this->assertDatabaseHas('suppliers', ['company_name' => 'Test Supplier']);
    }

    public function test_show_displays(): void
    {
        $supplier = Supplier::create(['company_name' => 'Show', 'contact_person' => 'X', 'phone' => '01811111111', 'status' => 'active', 'payment_terms' => 'Net 30', 'opening_balance' => 0, 'credit_limit' => 0]);
        $this->actingAsAdmin()->get(route('supplier.show', $supplier))->assertStatus(200);
    }

    public function test_destroy_soft_deletes(): void
    {
        $supplier = Supplier::create(['company_name' => 'Del', 'contact_person' => 'X', 'phone' => '01811111112', 'status' => 'active', 'payment_terms' => 'Net 30', 'opening_balance' => 0, 'credit_limit' => 0]);
        $this->actingAsAdmin()->delete(route('supplier.destroy', $supplier))->assertRedirect();
    }

    public function test_ledger_renders(): void
    {
        $supplier = Supplier::create(['company_name' => 'Ledger', 'contact_person' => 'X', 'phone' => '01811111113', 'status' => 'active', 'payment_terms' => 'Net 30', 'opening_balance' => 0, 'credit_limit' => 0]);
        $this->actingAsAdmin()->get(route('supplier.ledger', $supplier))->assertStatus(200);
    }
}
