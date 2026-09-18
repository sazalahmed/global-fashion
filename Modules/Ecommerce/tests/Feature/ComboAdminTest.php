<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Category\Models\Category;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Tests\Concerns\CreatesComboTestData;
use Tests\TestCase;

class ComboAdminTest extends TestCase
{
    use CreatesComboTestData;

    public function test_admin_can_create_combo_with_items(): void
    {
        $p1 = $this->makeProduct(['sell_price' => 500]);
        $p2 = $this->makeProduct(['sell_price' => 300]);

        $res = $this->actingAs($this->admin)->post(route('products.combos.store'), [
            'name' => 'Festive Combo', 'combo_price' => 700,
            'discount_type' => 'fixed', 'discount_value' => 50, 'is_active' => 1,
            'items' => [
                ['product_id' => $p1->id, 'variant_id' => null, 'quantity' => 1],
                ['product_id' => $p2->id, 'variant_id' => null, 'quantity' => 2],
            ],
        ]);

        $res->assertRedirect(route('products.index'));
        $combo = Combo::firstWhere('name', 'Festive Combo');
        $this->assertNotNull($combo);
        $this->assertCount(2, $combo->items);
        $this->assertSame('festive-combo', $combo->slug);
    }

    public function test_admin_can_enable_customer_size_selection(): void
    {
        $product = $this->makeProduct(['sell_price' => 500]);

        $res = $this->actingAs($this->admin)->post(route('products.combos.store'), [
            'name' => 'Sized Combo', 'combo_price' => 1500, 'is_active' => 1,
            'size_required' => 1,
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]);

        $res->assertRedirect(route('products.index'));
        $this->assertTrue((bool) Combo::firstWhere('name', 'Sized Combo')->size_required);
    }

    public function test_combo_requires_at_least_one_item(): void
    {
        $res = $this->actingAs($this->admin)->from(route('products.combos.create'))
            ->post(route('products.combos.store'), [
                'name' => 'Empty', 'combo_price' => 100, 'discount_type' => 'none', 'discount_value' => 0,
            ]);
        $res->assertRedirect(route('products.combos.create'));
        $res->assertSessionHasErrors('items');
    }

    public function test_admin_can_toggle_and_delete(): void
    {
        $combo = Combo::create(['name' => 'X', 'combo_price' => 100, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true]);
        $this->actingAs($this->admin)->patch(route('products.combos.toggle-status', $combo))->assertRedirect();
        $this->assertFalse($combo->fresh()->is_active);
        $this->actingAs($this->admin)->delete(route('products.combos.destroy', $combo))->assertRedirect();
        $this->assertSoftDeleted($combo);
    }

    public function test_admin_can_create_combo_with_categories(): void
    {
        $p1 = $this->makeProduct(['sell_price' => 500]);
        $catA = Category::create(['name' => 'Bundles', 'slug' => 'bundles-'.uniqid()]);
        $catB = Category::create(['name' => 'Gifts', 'slug' => 'gifts-'.uniqid()]);

        $this->actingAs($this->admin)->post(route('products.combos.store'), [
            'name' => 'Categorized Combo', 'combo_price' => 400,
            'discount_type' => 'none', 'discount_value' => 0, 'is_active' => 1,
            'items' => [['product_id' => $p1->id, 'variant_id' => null, 'quantity' => 1]],
            'categories' => [$catA->id, $catB->id],
        ])->assertRedirect(route('products.index'));

        $combo = Combo::firstWhere('name', 'Categorized Combo');
        $this->assertEqualsCanonicalizing([$catA->id, $catB->id], $combo->categories->pluck('id')->all());
    }

    public function test_update_can_clear_categories(): void
    {
        $p1 = $this->makeProduct(['sell_price' => 500]);
        $cat = Category::create(['name' => 'Temp', 'slug' => 'temp-'.uniqid()]);
        $combo = Combo::create([
            'name' => 'Editable', 'combo_price' => 100,
            'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true,
        ]);
        $combo->items()->create(['product_id' => $p1->id, 'quantity' => 1, 'sort_order' => 0]);
        $combo->categories()->sync([$cat->id]);

        $this->actingAs($this->admin)->put(route('products.combos.update', $combo), [
            'name' => 'Editable', 'combo_price' => 100,
            'discount_type' => 'none', 'discount_value' => 0, 'is_active' => 1,
            'items' => [['product_id' => $p1->id, 'variant_id' => null, 'quantity' => 1]],
            // no 'categories' key => should clear
        ])->assertRedirect(route('products.index'));

        $this->assertCount(0, $combo->fresh()->categories);
    }

    public function test_invalid_category_id_is_rejected(): void
    {
        $p1 = $this->makeProduct(['sell_price' => 500]);
        $this->actingAs($this->admin)->from(route('products.combos.create'))
            ->post(route('products.combos.store'), [
                'name' => 'Bad Cat', 'combo_price' => 100,
                'discount_type' => 'none', 'discount_value' => 0, 'is_active' => 1,
                'items' => [['product_id' => $p1->id, 'variant_id' => null, 'quantity' => 1]],
                'categories' => [999999],
            ])->assertSessionHasErrors('categories.0');
    }

    public function test_combo_is_saved_without_any_discount(): void
    {
        $p1 = $this->makeProduct(['sell_price' => 500]);

        // Even if discount fields are posted (e.g. stale client), the combo
        // must be stored with no discount — effective price == combo price.
        $this->actingAs($this->admin)->post(route('products.combos.store'), [
            'name' => 'No Discount Combo', 'combo_price' => 700,
            'discount_type' => 'fixed', 'discount_value' => 50, 'is_active' => 1,
            'items' => [['product_id' => $p1->id, 'variant_id' => null, 'quantity' => 1]],
        ])->assertRedirect(route('products.index'));

        $combo = Combo::firstWhere('name', 'No Discount Combo');
        $this->assertSame('none', $combo->discount_type);
        $this->assertSame(0.0, (float) $combo->discount_value);
        $this->assertSame(700.0, app(\Modules\Ecommerce\Services\ComboService::class)->effectivePrice($combo));
    }
}
