<?php

namespace Modules\Customer\Tests\Feature;

use Modules\Customer\Models\Customer;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    public function test_index_renders(): void
    {
        $this->actingAsAdmin()->get(route('customers.index'))->assertStatus(200);
    }

    public function test_create_page_renders(): void
    {
        $this->actingAsAdmin()->get(route('customers.create'))->assertStatus(200);
    }

    public function test_store_creates_customer(): void
    {
        $response = $this->actingAsAdmin()->post(route('customers.store'), [
            'name' => 'New Customer',
            'phone' => '01711111111',
            'customer_group' => 'Retail',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('customers', ['name' => 'New Customer']);
    }

    public function test_store_fails_with_duplicate_phone(): void
    {
        Customer::create(['name' => 'Existing', 'phone' => '01711111111', 'customer_group' => 'Retail', 'is_active' => true, 'created_by' => $this->admin->id]);
        $response = $this->actingAsAdmin()->post(route('customers.store'), [
            'name' => 'Duplicate',
            'phone' => '01711111111',
            'customer_group' => 'Retail',
        ]);
        $response->assertSessionHasErrors('phone');
    }

    public function test_show_displays_customer(): void
    {
        $customer = Customer::create(['name' => 'Test', 'phone' => '01711111111', 'customer_group' => 'Retail', 'is_active' => true, 'created_by' => $this->admin->id]);
        $this->actingAsAdmin()->get(route('customers.show', $customer))->assertStatus(200);
    }

    public function test_update_modifies_customer(): void
    {
        $customer = Customer::create(['name' => 'Old Name', 'phone' => '01711111111', 'customer_group' => 'Retail', 'is_active' => true, 'created_by' => $this->admin->id]);
        $this->actingAsAdmin()->put(route('customers.update', $customer), [
            'name' => 'New Name', 'phone' => '01711111111', 'customer_group' => 'Wholesale',
        ]);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'New Name']);
    }

    public function test_destroy_soft_deletes(): void
    {
        $customer = Customer::create(['name' => 'Delete Me', 'phone' => '01711111111', 'customer_group' => 'Retail', 'is_active' => true, 'created_by' => $this->admin->id]);
        $this->actingAsAdmin()->delete(route('customers.destroy', $customer));
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_search_ajax(): void
    {
        Customer::create(['name' => 'Rahim', 'phone' => '01711111111', 'customer_group' => 'Retail', 'is_active' => true, 'created_by' => $this->admin->id]);
        $response = $this->actingAsAdmin()->getJson(route('customers.search', ['term' => 'Rahim']));
        $response->assertOk();
    }

    public function test_ledger_page_renders(): void
    {
        $this->actingAsAdmin()->get(route('customers.ledger'))->assertStatus(200);
    }
}
