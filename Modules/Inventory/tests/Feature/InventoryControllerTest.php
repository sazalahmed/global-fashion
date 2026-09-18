<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;

class InventoryControllerTest extends TestCase
{
    public function test_index_renders(): void
    {
        $this->actingAsAdmin()->get(route('inventory.index'))->assertStatus(200);
    }

    public function test_alerts_renders(): void
    {
        $this->actingAsAdmin()->get(route('inventory.alerts'))->assertStatus(200);
    }

    public function test_ledger_renders(): void
    {
        $this->actingAsAdmin()->get(route('inventory.ledger'))->assertStatus(200);
    }

    public function test_adjustments_renders(): void
    {
        $this->actingAsAdmin()->get(route('inventory.adjustments'))->assertStatus(200);
    }

    public function test_transfers_renders(): void
    {
        $this->actingAsAdmin()->get(route('inventory.transfers'))->assertStatus(200);
    }

    public function test_create_adjustment_renders(): void
    {
        $this->actingAsAdmin()->get(route('inventory.adjustments.create'))->assertStatus(200);
    }

    public function test_create_transfer_renders(): void
    {
        $this->actingAsAdmin()->get(route('inventory.transfers.create'))->assertStatus(200);
    }
}
