<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Services\ComboService;
use Modules\Ecommerce\Tests\Concerns\CreatesComboTestData;
use Modules\Product\Models\Product;
use Tests\TestCase;

class ComboStockTest extends TestCase
{
    use CreatesComboTestData;

    private function stockedProduct(int $level): Product
    {
        $p = $this->makeProduct(['track_stock' => true, 'allow_negative_stock' => false]);
        DB::table('warehouse_stock')->insert([
            'product_id' => $p->id, 'variant_id' => null, 'quantity' => $level,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $p;
    }

    public function test_available_is_min_over_components_divided_by_qty(): void
    {
        $svc = new ComboService();
        $a = $this->stockedProduct(10); // qty 2 -> 5 combos
        $b = $this->stockedProduct(9);  // qty 3 -> 3 combos
        $combo = Combo::create(['name' => 'C', 'combo_price' => 100, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true]);
        $combo->items()->create(['product_id' => $a->id, 'quantity' => 2]);
        $combo->items()->create(['product_id' => $b->id, 'quantity' => 3]);

        $this->assertSame(3, $svc->availableStock($combo->fresh('items')));
        $this->assertTrue($svc->isInStock($combo, 3));
        $this->assertFalse($svc->isInStock($combo, 4));
    }

    public function test_untracked_component_does_not_constrain(): void
    {
        $svc = new ComboService();
        $p = $this->makeProduct(['track_stock' => false]);
        $combo = Combo::create(['name' => 'C', 'combo_price' => 100, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true]);
        $combo->items()->create(['product_id' => $p->id, 'quantity' => 1]);

        $this->assertNull($svc->availableStock($combo->fresh('items')));
        $this->assertTrue($svc->isInStock($combo, 999));
    }
}
