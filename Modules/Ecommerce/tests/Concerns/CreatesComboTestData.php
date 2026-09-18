<?php

namespace Modules\Ecommerce\Tests\Concerns;

use Modules\Category\Models\Category;
use Modules\Product\Models\Product;

/**
 * Shared helpers for combo tests: build valid products (with the required
 * category FK) without depending on the Product module factory namespace.
 */
trait CreatesComboTestData
{
    protected ?Category $comboTestCategory = null;

    protected function comboCategory(): Category
    {
        return $this->comboTestCategory ??= Category::create([
            'name' => 'Combo Test Category',
            'slug' => 'combo-test-category-'.uniqid(),
        ]);
    }

    /**
     * Create a valid product for combo tests. Pass overrides for sell_price,
     * track_stock, allow_negative_stock, status, etc.
     */
    protected function makeProduct(array $attrs = []): Product
    {
        return Product::create(array_merge([
            'name'        => 'Combo Component',
            'sku'         => uniqid('sku-'),
            'category_id' => $this->comboCategory()->id,
            'sell_price'  => 100,
            'status'      => 'active',
        ], $attrs));
    }
}
