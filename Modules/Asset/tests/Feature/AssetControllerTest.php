<?php

namespace Modules\Asset\Tests\Feature;

use Modules\Asset\Models\Asset;
use Modules\Branch\Models\Branch;
use Tests\TestCase;

class AssetControllerTest extends TestCase
{
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->branch = Branch::create(['name' => 'Main', 'code' => 'BR-001', 'is_main' => true, 'is_active' => true, 'is_pos_enabled' => true, 'is_ecom_enabled' => false]);
    }

    public function test_index_renders(): void
    {
        $this->actingAsAdmin()->get(route('assets.index'))->assertStatus(200);
    }

    public function test_create_renders(): void
    {
        $this->actingAsAdmin()->get(route('assets.create'))->assertStatus(200);
    }

    public function test_store_creates_asset(): void
    {
        $response = $this->actingAsAdmin()->post(route('assets.store'), [
            'name' => 'Office Laptop',
            'asset_code' => 'AST-001',
            'purchase_date' => now()->format('Y-m-d'),
            'purchase_price' => 75000,
            'current_value' => 75000,
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('assets', ['name' => 'Office Laptop']);
    }

    public function test_show_displays_asset(): void
    {
        $asset = Asset::create([
            'name' => 'Test Asset', 'asset_code' => 'AST-002',
            'purchase_date' => now(), 'purchase_price' => 50000,
            'current_value' => 50000, 'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
        $this->actingAsAdmin()->get(route('assets.show', $asset))->assertStatus(200);
    }

    public function test_destroy_deletes_asset(): void
    {
        $asset = Asset::create([
            'name' => 'Delete Asset', 'asset_code' => 'AST-003',
            'purchase_date' => now(), 'purchase_price' => 30000,
            'current_value' => 30000, 'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
        $this->actingAsAdmin()->delete(route('assets.destroy', $asset))->assertRedirect();
        $this->assertSoftDeleted('assets', ['id' => $asset->id]);
    }
}
