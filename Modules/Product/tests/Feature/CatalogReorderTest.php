<?php

namespace Modules\Product\Tests\Feature;

use App\Models\User;
use Modules\Category\Models\Category;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Tests\Concerns\CreatesComboTestData;
use Modules\Product\Models\CatalogPosition;
use Modules\Product\Services\CatalogService;
use Modules\Security\Database\Seeders\RolePermissionSeeder;
use Tests\TestCase;

class CatalogReorderTest extends TestCase
{
    use CreatesComboTestData;

    private function combo(string $name): Combo
    {
        return Combo::create(['name' => $name, 'combo_price' => 100, 'is_active' => true]);
    }

    /** A Super Admin user (Gate::before grants all permissions). */
    private function superAdmin(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $user;
    }

    public function test_reorder_writes_1_based_positions_for_a_category_context(): void
    {
        $cat = Category::create(['name' => 'Shirts', 'slug' => 'shirts-'.uniqid(), 'status' => 'active']);
        $p = $this->makeProduct();
        $c = $this->combo('Bundle A');

        app(CatalogService::class)->reorder($cat->id, [
            ['type' => 'product', 'id' => $p->id],
            ['type' => 'combo', 'id' => $c->id],
        ]);

        $this->assertDatabaseHas('catalog_positions', [
            'category_id' => $cat->id, 'positionable_type' => 'product',
            'positionable_id' => $p->id, 'position' => 1,
        ]);
        $this->assertDatabaseHas('catalog_positions', [
            'category_id' => $cat->id, 'positionable_type' => 'combo',
            'positionable_id' => $c->id, 'position' => 2,
        ]);
    }

    public function test_same_combo_can_hold_different_positions_per_category_and_global(): void
    {
        $a = Category::create(['name' => 'A', 'slug' => 'a-'.uniqid(), 'status' => 'active']);
        $b = Category::create(['name' => 'B', 'slug' => 'b-'.uniqid(), 'status' => 'active']);
        $c = $this->combo('Bundle');
        $p1 = $this->makeProduct();
        $p2 = $this->makeProduct();
        $svc = app(CatalogService::class);

        // Category A: combo first (position 1).
        $svc->reorder($a->id, [['type' => 'combo', 'id' => $c->id]]);
        // Category B: combo third (position 3).
        $svc->reorder($b->id, [
            ['type' => 'product', 'id' => $p1->id],
            ['type' => 'product', 'id' => $p2->id],
            ['type' => 'combo', 'id' => $c->id],
        ]);
        // Global: combo second (position 2).
        $svc->reorder(null, [
            ['type' => 'product', 'id' => $p1->id],
            ['type' => 'combo', 'id' => $c->id],
        ]);

        $this->assertEquals(1, CatalogPosition::where('category_id', $a->id)->where('positionable_type', 'combo')->where('positionable_id', $c->id)->value('position'));
        $this->assertEquals(3, CatalogPosition::where('category_id', $b->id)->where('positionable_type', 'combo')->where('positionable_id', $c->id)->value('position'));
        $this->assertEquals(2, CatalogPosition::whereNull('category_id')->where('positionable_type', 'combo')->where('positionable_id', $c->id)->value('position'));
    }

    public function test_reorder_is_idempotent_and_updates_existing_rows(): void
    {
        $c1 = $this->combo('One');
        $c2 = $this->combo('Two');
        $svc = app(CatalogService::class);
        $svc->reorder(null, [['type' => 'combo', 'id' => $c1->id], ['type' => 'combo', 'id' => $c2->id]]);
        $svc->reorder(null, [['type' => 'combo', 'id' => $c2->id], ['type' => 'combo', 'id' => $c1->id]]);

        $this->assertEquals(1, CatalogPosition::whereNull('category_id')->where('positionable_id', $c2->id)->value('position'));
        $this->assertEquals(2, CatalogPosition::whereNull('category_id')->where('positionable_id', $c1->id)->value('position'));
        $this->assertEquals(2, CatalogPosition::whereNull('category_id')->count());
    }

    public function test_reorder_endpoint_persists_positions(): void
    {
        $c1 = $this->combo('One');
        $c2 = $this->combo('Two');

        $res = $this->actingAs($this->superAdmin())->postJson(route('products.catalog.reorder'), [
            'category_id' => null,
            'items' => [
                ['type' => 'combo', 'id' => $c2->id],
                ['type' => 'combo', 'id' => $c1->id],
            ],
        ]);

        $res->assertOk()->assertJson(['success' => true]);
        $this->assertEquals(1, CatalogPosition::whereNull('category_id')->where('positionable_id', $c2->id)->value('position'));
        $this->assertEquals(2, CatalogPosition::whereNull('category_id')->where('positionable_id', $c1->id)->value('position'));
    }
}
