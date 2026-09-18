<?php

namespace Modules\Product\Tests\Feature;

use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Ecommerce\Tests\Concerns\CreatesComboTestData;
use Modules\Product\Models\CatalogPosition;
use Tests\TestCase;

class CatalogPositionTest extends TestCase
{
    use CreatesComboTestData;

    public function test_morph_map_registers_product_and_combo_aliases(): void
    {
        $map = Relation::morphMap();
        $this->assertSame(\Modules\Product\Models\Product::class, $map['product'] ?? null);
        $this->assertSame(\Modules\Ecommerce\Models\Combo::class, $map['combo'] ?? null);
    }

    public function test_position_row_resolves_back_to_its_product(): void
    {
        $product = $this->makeProduct();
        CatalogPosition::create([
            'category_id' => null,
            'positionable_type' => 'product',
            'positionable_id' => $product->id,
            'position' => 1,
        ]);

        $row = CatalogPosition::first();
        $this->assertTrue($row->positionable->is($product));
        $this->assertEquals('product', $row->positionable_type);
    }
}
