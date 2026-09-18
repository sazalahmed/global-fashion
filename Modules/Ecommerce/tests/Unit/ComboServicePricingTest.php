<?php

namespace Modules\Ecommerce\Tests\Unit;

use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Services\ComboService;
use Modules\Ecommerce\Tests\Concerns\CreatesComboTestData;
use Tests\TestCase;

class ComboServicePricingTest extends TestCase
{
    use CreatesComboTestData;

    private function combo(array $attrs, array $items): Combo
    {
        $combo = Combo::create(array_merge([
            'name' => 'C', 'combo_price' => 0, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true,
        ], $attrs));
        foreach ($items as $it) {
            $p = $this->makeProduct(['sell_price' => $it['price']]);
            $combo->items()->create(['product_id' => $p->id, 'quantity' => $it['qty'], 'sort_order' => 0]);
        }

        return $combo->fresh('items');
    }

    public function test_summed_price_sums_components(): void
    {
        $svc = new ComboService();
        $c = $this->combo(['combo_price' => 1000], [['price' => 500, 'qty' => 2], ['price' => 300, 'qty' => 1]]);
        $this->assertSame(1300.0, $svc->summedPrice($c)); // 500*2 + 300
    }

    public function test_fixed_and_percentage_discounts(): void
    {
        $svc = new ComboService();
        $fixed = $this->combo(['combo_price' => 1000, 'discount_type' => 'fixed', 'discount_value' => 150], [['price' => 500, 'qty' => 2]]);
        $this->assertSame(150.0, $svc->discountAmount($fixed));
        $this->assertSame(850.0, $svc->effectivePrice($fixed));

        $pct = $this->combo(['combo_price' => 1000, 'discount_type' => 'percentage', 'discount_value' => 10], [['price' => 500, 'qty' => 2]]);
        $this->assertSame(100.0, $svc->discountAmount($pct));
        $this->assertSame(900.0, $svc->effectivePrice($pct));
    }

    public function test_discount_cannot_exceed_price(): void
    {
        $svc = new ComboService();
        $c = $this->combo(['combo_price' => 200, 'discount_type' => 'fixed', 'discount_value' => 999], [['price' => 100, 'qty' => 1]]);
        $this->assertSame(0.0, $svc->effectivePrice($c));
    }

    public function test_allocated_prices_sum_to_combo_total(): void
    {
        $svc = new ComboService();
        // effective 1000, components 500x1 and 300x1 (sum 800) -> allocate 1000 across them
        $c = $this->combo(['combo_price' => 1000], [['price' => 500, 'qty' => 1], ['price' => 300, 'qty' => 1]]);
        $alloc = $svc->allocatePrices($c, 2); // comboQty 2 -> total 2000
        $total = 0.0;
        foreach ($c->items as $item) {
            $total += round($alloc[$item->id] * $item->quantity * 2, 2);
        }
        $this->assertSame(2000.0, round($total, 2));
    }
}
