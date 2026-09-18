<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;

class CanonicalHostTest extends TestCase
{
    public function test_non_canonical_host_301s_to_canonical_in_production(): void
    {
        config(['app.env' => 'production', 'app.url' => 'https://shop.test']);
        $res = $this->get('http://www.shop.test/shop');
        $res->assertStatus(301);
        $res->assertRedirect('https://shop.test/shop');
    }

    public function test_canonical_host_not_redirected(): void
    {
        config(['app.env' => 'production', 'app.url' => 'https://shop.test']);
        $res = $this->get('https://shop.test/shop');
        $this->assertNotSame(301, $res->getStatusCode());
    }

    public function test_no_redirect_outside_production(): void
    {
        config(['app.env' => 'testing', 'app.url' => 'https://shop.test']);
        $res = $this->get('http://www.shop.test/shop');
        $this->assertNotSame(301, $res->getStatusCode());
    }
}
