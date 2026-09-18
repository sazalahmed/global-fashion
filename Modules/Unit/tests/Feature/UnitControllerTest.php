<?php

namespace Modules\Unit\Tests\Feature;

use Modules\Unit\Models\Unit;
use Tests\TestCase;

class UnitControllerTest extends TestCase
{
    public function test_index_renders(): void
    {
        $this->actingAsAdmin()->get(route('units.index'))->assertStatus(200);
    }

    public function test_store_creates_unit(): void
    {
        $this->actingAsAdmin()->post(route('units.store'), [
            'name' => 'Kilogram', 'short_name' => 'kg', 'status' => 'active',
        ])->assertRedirect();
        $this->assertDatabaseHas('units', ['name' => 'Kilogram']);
    }

    public function test_update_modifies_unit(): void
    {
        $unit = Unit::create(['name' => 'Old', 'short_name' => 'old', 'status' => 'active']);
        $this->actingAsAdmin()->put(route('units.update', $unit), ['name' => 'New', 'short_name' => 'new', 'status' => 'active']);
        $this->assertDatabaseHas('units', ['id' => $unit->id, 'name' => 'New']);
    }

    public function test_destroy_deletes(): void
    {
        $unit = Unit::create(['name' => 'Del', 'short_name' => 'del', 'status' => 'active']);
        $this->actingAsAdmin()->delete(route('units.destroy', $unit))->assertRedirect();
    }
}
