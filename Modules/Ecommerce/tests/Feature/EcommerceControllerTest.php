<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;

class EcommerceControllerTest extends TestCase
{
    public function test_products_renders(): void
    {
        $this->actingAsAdmin()->get(route('ecommerce.products'))->assertStatus(200);
    }

    public function test_coupons_renders(): void
    {
        $this->actingAsAdmin()->get(route('ecommerce.coupons'))->assertStatus(200);
    }

    public function test_shipping_renders(): void
    {
        $this->actingAsAdmin()->get(route('ecommerce.shipping'))->assertStatus(200);
    }

    public function test_settings_renders(): void
    {
        $this->actingAsAdmin()->get(route('ecommerce.settings'))->assertStatus(200);
    }
}
