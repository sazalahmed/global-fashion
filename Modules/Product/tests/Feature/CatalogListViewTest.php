<?php

namespace Modules\Product\Tests\Feature;

use App\Models\User;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Tests\Concerns\CreatesComboTestData;
use Modules\Security\Database\Seeders\RolePermissionSeeder;
use Tests\TestCase;

class CatalogListViewTest extends TestCase
{
    use CreatesComboTestData;

    private function superAdmin(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $user;
    }

    public function test_products_index_lists_a_product_and_a_combo_row(): void
    {
        $this->makeProduct(['name' => 'Visible Product']);
        Combo::create(['name' => 'Visible Combo', 'combo_price' => 100, 'is_active' => true]);

        $res = $this->actingAs($this->superAdmin())->get(route('products.index'));

        $res->assertOk();
        $res->assertSee('Visible Product');
        $res->assertSee('Visible Combo');
    }
}
