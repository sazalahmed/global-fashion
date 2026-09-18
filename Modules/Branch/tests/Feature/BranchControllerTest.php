<?php

namespace Modules\Branch\Tests\Feature;

use Modules\Branch\Models\Branch;
use Tests\TestCase;

class BranchControllerTest extends TestCase
{
    public function test_index_renders(): void
    {
        $this->actingAsAdmin()->get(route('branches.index'))->assertStatus(200);
    }

    public function test_store_creates_branch(): void
    {
        $this->actingAsAdmin()->post(route('branches.store'), [
            'name' => 'Test Branch', 'phone' => '01700000001',
        ])->assertRedirect();
        $this->assertDatabaseHas('branches', ['name' => 'Test Branch']);
    }

    public function test_show_displays(): void
    {
        $branch = Branch::create(['name' => 'Show Branch', 'code' => 'BR-SHW', 'is_active' => true, 'is_main' => false, 'is_pos_enabled' => true, 'is_ecom_enabled' => false]);
        $this->actingAsAdmin()->get(route('branches.show', $branch))->assertStatus(200);
    }

    public function test_destroy_deletes(): void
    {
        $branch = Branch::create(['name' => 'Del Branch', 'code' => 'BR-DEL', 'is_active' => true, 'is_main' => false, 'is_pos_enabled' => true, 'is_ecom_enabled' => false]);
        $this->actingAsAdmin()->delete(route('branches.destroy', $branch))->assertRedirect();
    }
}
