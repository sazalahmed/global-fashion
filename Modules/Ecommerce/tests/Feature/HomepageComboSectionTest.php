<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Models\HomepageSection;
use Modules\Ecommerce\Tests\Concerns\CreatesComboTestData;
use Tests\TestCase;

class HomepageComboSectionTest extends TestCase
{
    use CreatesComboTestData;

    public function test_homepage_renders_selected_combos(): void
    {
        $p = $this->makeProduct(['sell_price' => 500]);
        $combo = Combo::create(['name' => 'Home Combo', 'combo_price' => 800, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true]);
        $combo->items()->create(['product_id' => $p->id, 'quantity' => 1]);

        $section = HomepageSection::create([
            'section_type' => 'combos', 'title' => 'Combos', 'is_active' => true, 'sort_order' => 1,
        ]);
        $section->combos()->attach($combo->id, ['sort_order' => 0]);

        $this->get(route('storefront.home'))->assertOk()->assertSee('Home Combo');
    }

    public function test_admin_combo_picker_renders_and_syncs(): void
    {
        $this->seed(\Modules\Security\Database\Seeders\RolePermissionSeeder::class);
        $this->admin->assignRole('Super Admin');

        $p = $this->makeProduct(['sell_price' => 500]);
        $combo = Combo::create(['name' => 'Picker Combo', 'combo_price' => 800, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true]);
        $combo->items()->create(['product_id' => $p->id, 'quantity' => 1]);

        $section = HomepageSection::create([
            'section_type' => 'combos', 'title' => 'Combos', 'is_active' => true, 'sort_order' => 1,
        ]);

        $this->actingAs($this->admin)
            ->get(route('ecommerce.homepage-sections.combos', $section))
            ->assertOk()->assertSee('Picker Combo');

        $this->actingAs($this->admin)
            ->post(route('ecommerce.homepage-sections.combos.update', $section), [
                'combos' => [['combo_id' => $combo->id, 'sort_order' => 0]],
            ])
            ->assertRedirect(route('ecommerce.homepage-sections'));

        $this->assertTrue($section->combos()->whereKey($combo->id)->exists());
    }
}
