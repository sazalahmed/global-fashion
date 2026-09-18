<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Category\Models\Category;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Services\StorefrontService;
use Modules\Ecommerce\Tests\Concerns\CreatesComboTestData;
use Tests\TestCase;

/**
 * Related items on the product/combo detail pages, in priority order:
 * 1) same design (base title match) products, 2) same-title combos,
 * 3) other products in the category — rendered as a plain grid, no slider.
 */
class RelatedCatalogItemsTest extends TestCase
{
    use CreatesComboTestData;

    public function test_base_title_strips_color_and_combo_suffixes(): void
    {
        $service = app(StorefrontService::class);

        $this->assertSame(
            'Premium Cotton Full Sleeve Formal Shirt',
            $service->baseTitleOf('Premium Cotton Full Sleeve Formal Shirt - (Sky Blue)')
        );
        $this->assertSame(
            'Premium Cotton Full Sleeve Formal Shirt',
            $service->baseTitleOf('Premium Cotton Full Sleeve Formal Shirt - Combo-02-(Beige & Aqua-blue)')
        );
        $this->assertSame('Plain Name', $service->baseTitleOf('Plain Name'));
    }

    public function test_product_detail_page_orders_same_title_then_combo_then_category(): void
    {
        $category = Category::create(['name' => 'Shirts', 'slug' => 'shirts-'.uniqid(), 'status' => 'active']);

        $current = $this->makeProduct(['name' => 'Design A - (Red)', 'category_id' => $category->id, 'track_stock' => false]);
        $sibling = $this->makeProduct(['name' => 'Design A - (Blue)', 'category_id' => $category->id, 'track_stock' => false]);
        $other   = $this->makeProduct(['name' => 'Totally Different Item', 'category_id' => $category->id, 'track_stock' => false]);

        $combo = Combo::create([
            'name' => 'Design A - Combo-01-(Red & Blue)', 'combo_price' => 900, 'is_active' => true,
        ]);
        $combo->items()->create(['product_id' => $sibling->id, 'quantity' => 1]);

        $html = $this->get(route('storefront.shop.show', $current->slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('related_product_slider', $html);

        $posSibling = strpos($html, 'Design A - (Blue)');
        $posCombo   = strpos($html, 'Design A - Combo-01-(Red &amp; Blue)') ?: strpos($html, 'Design A - Combo-01-(Red & Blue)');
        $posOther   = strpos($html, 'Totally Different Item');

        $this->assertNotFalse($posSibling);
        $this->assertNotFalse($posCombo);
        $this->assertNotFalse($posOther);
        $this->assertTrue($posSibling < $posCombo, 'Same-title product should appear before the related combo.');
        $this->assertTrue($posCombo < $posOther, 'Related combo should appear before the category fallback product.');
    }

    public function test_combo_detail_page_orders_same_title_then_combo_then_category(): void
    {
        $category = Category::create(['name' => 'Combo Cat', 'slug' => 'combo-cat-'.uniqid(), 'status' => 'active']);

        $componentProduct = $this->makeProduct(['name' => 'Component Product', 'track_stock' => false]);
        $current = Combo::create([
            'name' => 'Design B - Combo-01-(Red & Blue)', 'combo_price' => 900, 'is_active' => true,
        ]);
        $current->items()->create(['product_id' => $componentProduct->id, 'quantity' => 1]);
        $current->categories()->sync([$category->id]);

        $sameTitleProduct = $this->makeProduct(['name' => 'Design B - (Red)', 'category_id' => $category->id, 'track_stock' => false]);
        $sameTitleCombo = Combo::create([
            'name' => 'Design B - Combo-02-(Green & Yellow)', 'combo_price' => 900, 'is_active' => true,
        ]);
        $sameTitleCombo->items()->create(['product_id' => $componentProduct->id, 'quantity' => 1]);

        $categoryProduct = $this->makeProduct(['name' => 'Unrelated Category Item', 'category_id' => $category->id, 'track_stock' => false]);

        $html = $this->get(route('storefront.combos.show', $current->slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('related_product_slider', $html);

        $posSameTitleProduct = strpos($html, 'Design B - (Red)');
        $posSameTitleCombo   = strpos($html, 'Design B - Combo-02-(Green &amp; Yellow)') ?: strpos($html, 'Design B - Combo-02-(Green & Yellow)');
        $posCategoryProduct  = strpos($html, 'Unrelated Category Item');

        $this->assertNotFalse($posSameTitleProduct);
        $this->assertNotFalse($posSameTitleCombo);
        $this->assertNotFalse($posCategoryProduct);
        $this->assertTrue($posSameTitleProduct < $posSameTitleCombo, 'Same-title product should appear before the related combo.');
        $this->assertTrue($posSameTitleCombo < $posCategoryProduct, 'Related combo should appear before the category fallback product.');
    }

    public function test_out_of_stock_products_and_combos_are_excluded(): void
    {
        $category = Category::create(['name' => 'Stock Test', 'slug' => 'stock-test-'.uniqid(), 'status' => 'active']);

        $current = $this->makeProduct(['name' => 'Design C - (Red)', 'category_id' => $category->id, 'track_stock' => false]);

        // Tracks stock with a zero-quantity warehouse_stock row → out of stock.
        // (A product with NO stock row at all is a different case — ComboService
        // treats "no history" as unconstrained — so the row must exist at 0.)
        $soldOutSibling = $this->makeProduct([
            'name' => 'Design C - (Blue)', 'category_id' => $category->id, 'track_stock' => true,
        ]);
        \Modules\Inventory\Models\WarehouseStock::create(['product_id' => $soldOutSibling->id, 'quantity' => 0]);

        $soldOutCategoryItem = $this->makeProduct([
            'name' => 'Sold Out Item', 'category_id' => $category->id, 'track_stock' => true,
        ]);
        \Modules\Inventory\Models\WarehouseStock::create(['product_id' => $soldOutCategoryItem->id, 'quantity' => 0]);

        $soldOutCombo = Combo::create([
            'name' => 'Design C - Combo-01-(Red & Blue)', 'combo_price' => 900, 'is_active' => true,
        ]);
        $soldOutCombo->items()->create(['product_id' => $soldOutSibling->id, 'quantity' => 1]);

        $html = $this->get(route('storefront.shop.show', $current->slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('Design C - (Blue)', $html);
        $this->assertStringNotContainsString('Sold Out Item', $html);
        $this->assertStringNotContainsString('Design C - Combo-01', $html);
    }
}
