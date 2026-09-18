<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Category\Models\Category;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Tests\Concerns\CreatesComboTestData;
use Tests\TestCase;

class ComboCategoryTest extends TestCase
{
    use CreatesComboTestData;

    public function test_combo_and_category_relate_through_pivot(): void
    {
        $combo = Combo::create([
            'name' => 'Relation Combo', 'combo_price' => 100,
            'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true,
        ]);
        $catA = Category::create(['name' => 'Cat A', 'slug' => 'cat-a-'.uniqid()]);
        $catB = Category::create(['name' => 'Cat B', 'slug' => 'cat-b-'.uniqid()]);

        $combo->categories()->sync([$catA->id, $catB->id]);

        $this->assertCount(2, $combo->fresh()->categories);
        $this->assertTrue($catA->fresh()->combos->contains($combo->id));
    }

    public function test_create_form_renders_category_checkboxes(): void
    {
        Category::create(['name' => 'Visible Cat', 'slug' => 'visible-cat-'.uniqid(), 'status' => 'active']);

        $this->actingAs($this->admin)->get(route('products.combos.create'))
            ->assertOk()
            ->assertSee('name="categories[]"', false)
            ->assertSee('Visible Cat');
    }

    public function test_create_form_has_quick_add_category_button(): void
    {
        $this->actingAs($this->admin)->get(route('products.combos.create'))
            ->assertOk()
            ->assertSee('comboAddCategoryBtn', false)
            ->assertSee('comboQuickCategoryModal', false);
    }

    // NOTE: the standalone admin combo INDEX page (search / status / sort /
    // category filter) and the combo-only reorder endpoint were removed when
    // combo management moved into the unified products list. That behaviour is
    // now covered by CatalogServiceListTest (membership/filtering) and
    // CatalogReorderTest (per-category interleaved ordering).
}
