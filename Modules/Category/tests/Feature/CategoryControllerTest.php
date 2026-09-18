<?php

namespace Modules\Category\Tests\Feature;

use Modules\Category\Models\Category;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    public function test_index_renders(): void
    {
        $this->actingAsAdmin()->get(route('categories.index'))->assertStatus(200);
    }

    public function test_create_renders(): void
    {
        $this->actingAsAdmin()->get(route('categories.create'))->assertStatus(200);
    }

    public function test_store_creates_category(): void
    {
        $this->actingAsAdmin()->post(route('categories.store'), [
            'name' => 'Electronics', 'status' => 'active',
        ])->assertRedirect();
        $this->assertDatabaseHas('categories', ['name' => 'Electronics']);
    }

    public function test_store_fails_without_name(): void
    {
        $this->actingAsAdmin()->post(route('categories.store'), ['status' => 'active'])
            ->assertSessionHasErrors('name');
    }

    public function test_update_modifies_category(): void
    {
        $cat = Category::create(['name' => 'Old', 'slug' => 'old', 'status' => 'active', 'sort_order' => 0]);
        $this->actingAsAdmin()->put(route('categories.update', $cat), ['name' => 'New', 'status' => 'active']);
        $this->assertDatabaseHas('categories', ['id' => $cat->id, 'name' => 'New']);
    }

    public function test_destroy_deletes(): void
    {
        $cat = Category::create(['name' => 'Delete', 'slug' => 'delete', 'status' => 'active', 'sort_order' => 0]);
        $this->actingAsAdmin()->delete(route('categories.destroy', $cat))->assertRedirect();
    }
}
