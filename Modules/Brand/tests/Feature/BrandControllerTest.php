<?php

namespace Modules\Brand\Tests\Feature;

use Modules\Brand\Models\Brand;
use Tests\TestCase;

class BrandControllerTest extends TestCase
{
    public function test_index_renders(): void
    {
        $this->actingAsAdmin()->get(route('brands.index'))->assertStatus(200);
    }

    public function test_store_creates_brand(): void
    {
        $this->actingAsAdmin()->post(route('brands.store'), [
            'name' => 'Samsung', 'status' => 'active',
        ])->assertRedirect();
        $this->assertDatabaseHas('brands', ['name' => 'Samsung']);
    }

    public function test_store_fails_without_name(): void
    {
        $this->actingAsAdmin()->post(route('brands.store'), ['status' => 'active'])
            ->assertSessionHasErrors('name');
    }

    public function test_update_modifies_brand(): void
    {
        $brand = Brand::create(['name' => 'Old', 'slug' => 'old', 'status' => 'active', 'sort_order' => 0]);
        $this->actingAsAdmin()->put(route('brands.update', $brand), ['name' => 'New', 'status' => 'active']);
        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'name' => 'New']);
    }

    public function test_destroy_deletes(): void
    {
        $brand = Brand::create(['name' => 'Del', 'slug' => 'del', 'status' => 'active', 'sort_order' => 0]);
        $this->actingAsAdmin()->delete(route('brands.destroy', $brand))->assertRedirect();
    }
}
