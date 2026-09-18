<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;

class CanonicalTest extends TestCase
{
    public function test_shop_filters_canonicalize_to_clean_base(): void
    {
        config(['app.env' => 'production']);
        $res = $this->get('/shop?sort=price&min_price=10&utm_source=fb');
        $res->assertSee('rel="canonical" href="' . url('/shop') . '"', false);
    }

    public function test_search_results_are_noindex(): void
    {
        config(['app.env' => 'production']);
        $res = $this->get('/shop?q=anything');
        $res->assertSee('content="noindex,follow"', false);
    }
}
