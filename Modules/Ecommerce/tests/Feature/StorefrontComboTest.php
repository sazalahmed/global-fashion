<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Category\Models\Category;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Tests\Concerns\CreatesComboTestData;
use Modules\Variant\Models\ProductVariant;
use Modules\Variant\Models\VariantAttribute;
use Modules\Variant\Models\VariantAttributeValue;
use Tests\TestCase;

class StorefrontComboTest extends TestCase
{
    use CreatesComboTestData;

    /** Build an active, size-required combo with one product that has M/L variants. */
    private function makeSizeCombo(): Combo
    {
        $attr = VariantAttribute::create(['name' => 'Size', 'display_type' => 'button', 'status' => 'active', 'sort_order' => 1]);
        $product = $this->makeProduct(['track_stock' => false]);
        foreach (['M', 'L'] as $i => $label) {
            $valueId = VariantAttributeValue::create([
                'variant_attribute_id' => $attr->id, 'value' => $label, 'is_active' => true, 'sort_order' => $i,
            ])->id;
            ProductVariant::create(['product_id' => $product->id, 'sku' => uniqid('v-'), 'sell_price' => 100, 'is_active' => true])
                ->attributeValues()->attach($valueId);
        }
        $combo = Combo::create(['name' => 'Sized Combo', 'combo_price' => 900, 'is_active' => true, 'size_required' => true]);
        $combo->items()->create(['product_id' => $product->id, 'quantity' => 1, 'sort_order' => 0]);

        return $combo;
    }

    public function test_listing_and_detail_render(): void
    {
        $p = $this->makeProduct(['sell_price' => 500]);
        $combo = Combo::create(['name' => 'Combo One', 'combo_price' => 800, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true]);
        $combo->items()->create(['product_id' => $p->id, 'quantity' => 1]);

        $this->get(route('storefront.combos.index'))->assertOk()->assertSee('Combo One');
        $this->get(route('storefront.combos.show', $combo->slug))->assertOk()->assertSee('Combo One');
    }

    public function test_inactive_combo_is_404_on_detail(): void
    {
        $combo = Combo::create(['name' => 'Hidden', 'combo_price' => 100, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => false]);
        $this->get(route('storefront.combos.show', $combo->slug))->assertNotFound();
    }

    public function test_storefront_filters_combos_by_category(): void
    {
        $cat = Category::create(['name' => 'Storefront Cat', 'slug' => 'sf-cat-'.uniqid(), 'status' => 'active']);
        $inCat = Combo::create(['name' => 'Shown Combo', 'combo_price' => 100, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true]);
        $other = Combo::create(['name' => 'Hidden Combo', 'combo_price' => 100, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true]);
        $inCat->categories()->sync([$cat->id]);

        $this->get(route('storefront.combos.index', ['category' => $cat->slug]))
            ->assertOk()
            ->assertSee('Shown Combo')
            ->assertDontSee('Hidden Combo');
    }

    public function test_unknown_category_slug_shows_all_combos(): void
    {
        Combo::create(['name' => 'Always Shown', 'combo_price' => 100, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true]);

        $this->get(route('storefront.combos.index', ['category' => 'no-such-slug']))
            ->assertOk()
            ->assertSee('Always Shown');
    }

    public function test_combos_per_page_respects_store_setting(): void
    {
        \Modules\Ecommerce\Models\EcommerceSetting::set('per_page_combos', 1);

        $res = $this->get(route('storefront.combos.index'))->assertOk();

        $this->assertSame(1, $res->viewData('combos')->perPage());
    }

    public function test_size_required_combo_shows_size_selector(): void
    {
        $combo = $this->makeSizeCombo();

        $this->get(route('storefront.combos.show', $combo->slug))
            ->assertOk()
            ->assertSee('combo_size', false) // the hidden input / radio group name
            ->assertSee('>M<', false)
            ->assertSee('>L<', false);
    }

    public function test_size_required_combo_card_quick_adds_with_a_default_size(): void
    {
        $combo = $this->makeSizeCombo();

        // A size-required combo card exposes the same quick-add trigger as any
        // other combo — the default size is resolved client-side (cart.js) via
        // the size-data endpoint instead of sending the shopper to the detail
        // page first.
        $this->get(route('storefront.combos.index'))
            ->assertOk()
            ->assertSee('data-combo-id="'.$combo->id.'"', false)
            ->assertSee('data-size-required="1"', false)
            ->assertSee('data-combo-slug="'.$combo->slug.'"', false);
    }
}
