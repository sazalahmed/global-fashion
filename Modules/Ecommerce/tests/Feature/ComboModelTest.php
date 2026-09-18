<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Tests\Concerns\CreatesComboTestData;
use Tests\TestCase;

class ComboModelTest extends TestCase
{
    use CreatesComboTestData;

    public function test_combo_autogenerates_slug_and_relations_resolve(): void
    {
        $product = $this->makeProduct(['sell_price' => 500]);

        $combo = Combo::create([
            'name' => 'Eid Combo', 'combo_price' => 900, 'discount_type' => 'none',
            'discount_value' => 0, 'is_active' => true,
        ]);
        $combo->items()->create(['product_id' => $product->id, 'quantity' => 2, 'sort_order' => 0]);
        $combo->galleryImages()->create(['image_path' => 'uploads/products/x.png', 'sort_order' => 0]);

        $this->assertSame('eid-combo', $combo->slug);
        $this->assertCount(1, $combo->fresh()->items);
        $this->assertCount(1, $combo->fresh()->galleryImages);
        $this->assertSame($product->id, $combo->items->first()->product->id);
        $this->assertTrue(Combo::active()->whereKey($combo->id)->exists());
    }

    public function test_combo_size_required_defaults_false_and_is_fillable(): void
    {
        $combo = Combo::create([
            'name' => 'Sized Combo', 'combo_price' => 1000, 'is_active' => true,
        ]);
        $this->assertFalse((bool) $combo->fresh()->size_required);

        $combo->update(['size_required' => true]);
        $this->assertTrue($combo->fresh()->size_required);
    }
}
