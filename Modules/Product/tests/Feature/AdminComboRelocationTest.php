<?php

namespace Modules\Product\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Tests\Concerns\CreatesComboTestData;
use Tests\TestCase;

class AdminComboRelocationTest extends TestCase
{
    use CreatesComboTestData;

    public function test_new_combo_routes_exist(): void
    {
        $this->assertTrue(Route::has('products.combos.create'));
        $this->assertTrue(Route::has('products.combos.store'));
        $this->assertTrue(Route::has('products.combos.edit'));
        $this->assertTrue(Route::has('products.combos.update'));
        $this->assertTrue(Route::has('products.combos.destroy'));
        $this->assertTrue(Route::has('products.combos.toggle-status'));
    }

    public function test_old_admin_combos_index_redirects_to_products(): void
    {
        $res = $this->actingAs($this->admin)->get(route('ecommerce.combos.index'));
        $res->assertStatus(301);
        $res->assertRedirect(route('products.index'));
    }

    public function test_old_admin_combos_create_redirects_to_new_route(): void
    {
        $res = $this->actingAs($this->admin)->get(route('ecommerce.combos.create'));
        $res->assertStatus(301);
        $res->assertRedirect(route('products.combos.create'));
    }

    public function test_admin_can_create_combo_via_new_route(): void
    {
        $p = $this->makeProduct(['sell_price' => 500]);

        $res = $this->actingAs($this->admin)->post(route('products.combos.store'), [
            'name' => 'Relocated Combo', 'combo_price' => 700, 'is_active' => 1,
            'items' => [['product_id' => $p->id, 'variant_id' => null, 'quantity' => 1]],
        ]);

        $res->assertRedirect(route('products.index'));
        $this->assertNotNull(Combo::firstWhere('name', 'Relocated Combo'));
    }
}
