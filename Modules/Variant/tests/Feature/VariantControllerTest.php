<?php

namespace Modules\Variant\Tests\Feature;

use Modules\Variant\Models\VariantAttribute;
use Tests\TestCase;

class VariantControllerTest extends TestCase
{
    public function test_index_renders(): void
    {
        $this->actingAsAdmin()->get(route('variants.index'))->assertStatus(200);
    }

    public function test_store_creates_variant_attribute(): void
    {
        $response = $this->actingAsAdmin()->post(route('variants.store'), [
            'name' => 'Color',
            'display_name' => 'Color',
            'display_type' => 'color_swatch',
            'status' => 'active',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('variant_attributes', ['name' => 'Color']);
    }

    public function test_update_modifies_variant_attribute(): void
    {
        $variant = VariantAttribute::create([
            'name' => 'Size',
            'display_name' => 'Size',
            'display_type' => 'button',
            'sort_order' => 0,
            'status' => 'active',
        ]);
        $this->actingAsAdmin()->put(route('variants.update', $variant), [
            'name' => 'Updated Size',
            'display_name' => 'Updated Size',
            'display_type' => 'button',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('variant_attributes', ['id' => $variant->id, 'name' => 'Updated Size']);
    }

    public function test_destroy_deletes_variant_attribute(): void
    {
        $variant = VariantAttribute::create([
            'name' => 'Material',
            'display_name' => 'Material',
            'display_type' => 'button',
            'sort_order' => 0,
            'status' => 'active',
        ]);
        $this->actingAsAdmin()->delete(route('variants.destroy', $variant))->assertRedirect();
    }
}
