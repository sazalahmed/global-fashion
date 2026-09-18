<?php

namespace Modules\Ecommerce\Tests\Feature;

use App\Models\User;
use Modules\Category\Database\Factories\CategoryFactory;
use Modules\Product\Database\Factories\ProductFactory;
use Modules\Setting\Models\Setting;
use Tests\TestCase;

class StorefrontTrackingTest extends TestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        Setting::set('tracking', 'gtm_enabled', true, 'boolean');
        Setting::set('tracking', 'gtm_container_id', 'GTM-ABCD123');
        Setting::set('tracking', 'fbpixel_enabled', true, 'boolean');
        Setting::set('tracking', 'fbpixel_id', '1234567890');
        Setting::set('tracking', 'ga4_enabled', true, 'boolean');
        Setting::set('tracking', 'ga4_measurement_id', 'G-TEST12345');
    }

    public function test_storefront_home_includes_gtm_and_pixel_for_guests(): void
    {
        $res = $this->get(route('storefront.home'));
        $res->assertSee('googletagmanager.com/gtm.js', false);
        $res->assertSee('GTM-ABCD123', false);
        $res->assertSee("fbq('init', '1234567890')", false);
        $res->assertSee('js/tracking.js', false);
    }

    public function test_storefront_tracks_logged_in_staff_too(): void
    {
        $res = $this->actingAs($this->admin)->get(route('storefront.home'));
        $res->assertSee('googletagmanager.com/gtm.js', false);
        $res->assertSee("fbq('init'", false);
    }

    public function test_product_detail_fires_view_item(): void
    {
        $category = CategoryFactory::new()->create();
        $product = ProductFactory::new()->create([
            'status'      => 'active',
            'slug'        => 'test-widget-tracking',
            'name'        => 'Test Widget',
            'sell_price'  => 500.00,
            'category_id' => $category->id,
        ]);

        $res = $this->get(route('storefront.shop.show', $product->slug));
        $res->assertSee('ViewContent', false);
        $res->assertSee('content_ids', false);
    }

    public function test_shop_list_fires_view_item_list(): void
    {
        $category = CategoryFactory::new()->create();
        ProductFactory::new()->create([
            'status'      => 'active',
            'category_id' => $category->id,
            'sell_price'  => 100.00,
        ]);

        $res = $this->get(route('storefront.shop.index'));
        $res->assertOk();
        $res->assertSee('ViewCategory', false);
    }

    public function test_shop_search_fires_search(): void
    {
        $category = CategoryFactory::new()->create();
        ProductFactory::new()->create([
            'status'      => 'active',
            'name'        => 'Classic Cotton Shirt',
            'category_id' => $category->id,
            'sell_price'  => 500.00,
        ]);

        $res = $this->get(route('storefront.shop.index', ['q' => 'shirt']));
        $res->assertOk();
        $res->assertSee('search_term', false);
        $res->assertSee('shirt', false);
        $res->assertSee('search_results', false); // item_list_id on the list event
    }

    public function test_cart_fires_view_cart(): void
    {
        $category = CategoryFactory::new()->create();
        $product = ProductFactory::new()->create([
            'status'      => 'active',
            'category_id' => $category->id,
            'sell_price'  => 300.00,
        ]);

        $cart = [
            (string) $product->id => [
                'product_id'         => $product->id,
                'variant_id'         => null,
                'variant_name'       => null,
                'variant_attributes' => [],
                'name'               => $product->name,
                'slug'               => $product->slug,
                'price'              => 300.00,
                'sell_price'         => 300.00,
                'image'              => null,
                'quantity'           => 2,
                'sku'                => $product->sku,
            ],
        ];

        $res = $this->withSession(['cart' => $cart])
            ->get(route('storefront.cart.index'));

        $res->assertOk();
        $res->assertSee('ViewCart', false);
    }

    public function test_checkout_fires_begin_checkout(): void
    {
        $category = CategoryFactory::new()->create();
        $product = ProductFactory::new()->create([
            'status'      => 'active',
            'category_id' => $category->id,
            'sell_price'  => 450.00,
        ]);

        $cart = [
            (string) $product->id => [
                'product_id'         => $product->id,
                'variant_id'         => null,
                'variant_name'       => null,
                'variant_attributes' => [],
                'name'               => $product->name,
                'slug'               => $product->slug,
                'price'              => 450.00,
                'sell_price'         => 450.00,
                'image'              => null,
                'quantity'           => 1,
                'sku'                => $product->sku,
            ],
        ];

        $res = $this->withSession(['cart' => $cart])
            ->get(route('storefront.checkout.index'));

        $res->assertOk();
        $res->assertSee('InitiateCheckout', false);
    }

    public function test_cart_items_carry_variant_and_discount(): void
    {
        $category = CategoryFactory::new()->create();
        $product = ProductFactory::new()->create([
            'status'      => 'active',
            'category_id' => $category->id,
            'sell_price'  => 350.00,
        ]);

        $cart = [
            (string) $product->id => [
                'product_id'         => $product->id,
                'variant_id'         => 9,
                'variant_name'       => 'M',
                'variant_attributes' => [],
                'name'               => $product->name,
                'slug'               => $product->slug,
                'price'              => 300.00,
                'sell_price'         => 350.00,
                'image'              => null,
                'quantity'           => 1,
                'sku'                => $product->sku,
            ],
        ];

        $res = $this->withSession(['cart' => $cart])
            ->get(route('storefront.cart.index'));

        $res->assertOk();
        $res->assertSee('item_variant', false);
        $res->assertSee('"M"', false);
        $res->assertSee('discount', false);
        $res->assertSee('50', false);
    }

    public function test_mini_cart_drawer_items_carry_tracking_data(): void
    {
        $category = CategoryFactory::new()->create();
        $product = ProductFactory::new()->create([
            'status'      => 'active',
            'category_id' => $category->id,
            'sell_price'  => 350.00,
        ]);

        $cart = [
            (string) $product->id => [
                'product_id'         => $product->id,
                'variant_id'         => 9,
                'variant_name'       => 'M',
                'variant_attributes' => [],
                'name'               => $product->name,
                'slug'               => $product->slug,
                'price'              => 300.00,
                'sell_price'         => 350.00,
                'image'              => null,
                'quantity'           => 2,
                'sku'                => $product->sku,
            ],
        ];

        // The mini-cart drawer renders in the layout on every storefront page.
        $res = $this->withSession(['cart' => $cart])->get(route('storefront.home'));

        $res->assertOk();
        $res->assertSee('data-item-id="' . $product->id . '"', false);
        $res->assertSee('data-variant="M"', false);
        $res->assertSee('data-discount="50"', false);
        $res->assertSee('data-quantity="2"', false);
    }

    public function test_newsletter_subscribe_fires_lead(): void
    {
        $res = $this->followingRedirects()->post(route('storefront.newsletter.subscribe'), [
            'email' => 'lead@example.com',
        ]);
        $res->assertSee('Lead', false);
    }

    public function test_storefront_loads_gtag_when_ga4_enabled(): void
    {
        $res = $this->get(route('storefront.home'));
        $res->assertSee('googletagmanager.com/gtag/js?id=G-TEST12345', false);
        $res->assertSee("gtag('config', 'G-TEST12345')", false);
    }

    public function test_storefront_omits_gtag_when_ga4_disabled(): void
    {
        Setting::set('tracking', 'ga4_enabled', false, 'boolean');
        $res = $this->get(route('storefront.home'));
        $res->assertDontSee('gtag/js?id=', false);
    }

    public function test_shop_cards_carry_select_item_data_attributes(): void
    {
        $category = CategoryFactory::new()->create();
        ProductFactory::new()->create([
            'status'      => 'active',
            'category_id' => $category->id,
            'sell_price'  => 750.00,
        ]);

        $res = $this->get(route('storefront.shop.index'));
        $res->assertOk();
        $res->assertSee('js-bp-item', false);
        $res->assertSee('data-item-id', false);
    }

    public function test_homepage_banner_carries_promotion_attributes(): void
    {
        \Illuminate\Support\Facades\DB::table('banners')->insert([
            'title'      => 'Mega Sale',
            'image'      => 'uploads/banners/test.jpg',
            'position'   => 'hero',
            'sort_order' => 1,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $res = $this->get(route('storefront.home'));
        $res->assertOk();
        $res->assertSee('js-bp-promo', false);
        $res->assertSee('data-promotion-name="Mega Sale"', false);
    }

    private function makeSuccessPageOrder(array $attrs = []): \Modules\Ecommerce\Models\EcommerceOrder
    {
        $order = \Modules\Ecommerce\Models\EcommerceOrder::create(array_merge([
            'order_number'     => 'ORD-SP-' . uniqid(),
            'customer_name'    => 'Guest',
            'customer_phone'   => '01712345678',
            'shipping_address' => 'Dhaka',
            'billing_address'  => 'Dhaka',
            'status'           => 'pending',
            'subtotal'         => 500,
            'grand_total'      => 560,
            'shipping_charge'  => 60,
        ], $attrs));

        // ecommerce_order_items.product_id is FK-constrained — persist a real product.
        $category = CategoryFactory::new()->create();
        $product = ProductFactory::new()->create([
            'category_id' => $category->id, 'sell_price' => 500.00,
        ]);
        $order->items()->create([
            'product_id' => $product->id, 'product_name' => $product->name,
            'quantity' => 1, 'unit_price' => 500.00, 'subtotal' => 500.00,
        ]);

        return $order;
    }

    public function test_success_page_fires_purchase_with_flash(): void
    {
        $order = $this->makeSuccessPageOrder();

        $res = $this->withSession(['purchase_order' => $order->order_number])
            ->get(route('storefront.checkout.success', $order->order_number));

        $res->assertOk();
        $res->assertSee("BizPOS.track('Purchase'", false);
        $res->assertSee($order->order_number, false);
        $res->assertSee('purchase.' . $order->order_number, false); // shared eventID for Meta dedup
        $res->assertSee('customer_type', false);
        // Customer info for GA4 enhanced conversions / GTM-side CAPI mapping.
        $res->assertSee('phone_number', false);
        $res->assertSee('+8801712345678', false);
        $res->assertSee('shipping_address', false);
        // tracking.js must be cache-busted or browsers keep the old param whitelist.
        $res->assertSee('js/tracking.js?v=', false);
    }

    public function test_success_page_reload_does_not_refire_purchase(): void
    {
        $order = $this->makeSuccessPageOrder();

        // No flash in the session — a reload, revisit, or shared link.
        $res = $this->get(route('storefront.checkout.success', $order->order_number));

        $res->assertOk();
        $res->assertDontSee("BizPOS.track('Purchase'", false);
    }

    public function test_success_page_omits_purchase_for_blocked_risk(): void
    {
        Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');
        $order = $this->makeSuccessPageOrder([
            'fraud_report' => ['aggregate' => ['total_deliveries' => 10, 'success_ratio' => 20]],
        ]);

        $res = $this->withSession(['purchase_order' => $order->order_number])
            ->get(route('storefront.checkout.success', $order->order_number));

        $res->assertOk();
        $res->assertDontSee("BizPOS.track('Purchase'", false);
    }
}
