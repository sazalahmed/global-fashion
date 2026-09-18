<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Models\HomepageSection;
use Modules\Ecommerce\Tests\Concerns\CreatesComboTestData;
use Tests\TestCase;

class HomepageProductSectionComboTest extends TestCase
{
    use CreatesComboTestData;

    private function makeCombo(string $name = 'Section Combo'): Combo
    {
        $p = $this->makeProduct(['sell_price' => 500]);
        $combo = Combo::create([
            'name' => $name, 'combo_price' => 800,
            'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true,
        ]);
        $combo->items()->create(['product_id' => $p->id, 'quantity' => 1]);

        return $combo;
    }

    public function test_admin_can_mix_products_and_combos_in_a_product_section(): void
    {
        $this->seed(\Modules\Security\Database\Seeders\RolePermissionSeeder::class);
        $this->admin->assignRole('Super Admin');

        $product = $this->makeProduct(['name' => 'Section Product']);
        $combo = $this->makeCombo('Picker Combo');

        $section = HomepageSection::create([
            'section_type' => 'trending', 'title' => 'Trending', 'is_active' => true, 'sort_order' => 1,
        ]);

        // The Manage Products & Combos screen lists both pickers.
        $this->actingAs($this->admin)
            ->get(route('ecommerce.homepage-sections.products', $section))
            ->assertOk()
            ->assertSee('Section Product')
            ->assertSee('Picker Combo');

        $this->actingAs($this->admin)
            ->post(route('ecommerce.homepage-sections.products.update', $section), [
                'items' => [
                    ['type' => 'product', 'id' => $product->id, 'sort_order' => 0],
                    ['type' => 'combo', 'id' => $combo->id, 'sort_order' => 1],
                ],
            ])
            ->assertRedirect(route('ecommerce.homepage-sections'));

        $this->assertTrue($section->products()->whereKey($product->id)->exists());
        $this->assertTrue($section->combos()->whereKey($combo->id)->exists());
    }

    public function test_homepage_renders_a_curated_combo_inside_a_product_section(): void
    {
        $product = $this->makeProduct(['name' => 'Grid Product']);
        $combo = $this->makeCombo('Grid Combo');

        $section = HomepageSection::create([
            'section_type' => 'trending', 'title' => 'Trending', 'is_active' => true, 'sort_order' => 1,
        ]);
        $section->products()->attach($product->id, ['sort_order' => 0]);
        $section->combos()->attach($combo->id, ['sort_order' => 1]);

        $this->get(route('storefront.home'))
            ->assertOk()
            ->assertSee('Grid Product')
            ->assertSee('Grid Combo');
    }
}
