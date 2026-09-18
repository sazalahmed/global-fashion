<?php

namespace Modules\Ecommerce\Tests\Unit;

use Illuminate\Http\Request;
use Modules\Ecommerce\Support\BuildsSeo;
use Tests\TestCase;

class CleanCanonicalTest extends TestCase
{
    private function helper(): object
    {
        return new class { use BuildsSeo; public function call($r, $b, $k) { return $this->cleanCanonical($r, $b, $k); } };
    }

    public function test_strips_sort_price_and_tracking_keeps_category(): void
    {
        $req = Request::create('/shop?category=mugs&sort=price&min_price=10&utm_source=fb&fbclid=x', 'GET');
        $out = $this->helper()->call($req, 'https://x.test/shop', ['category', 'page']);
        $this->assertSame('https://x.test/shop?category=mugs', $out);
    }

    public function test_keeps_page_only_when_gt_1(): void
    {
        $h = $this->helper();
        $this->assertSame('https://x.test/shop', $h->call(Request::create('/shop?page=1', 'GET'), 'https://x.test/shop', ['page']));
        $this->assertSame('https://x.test/shop?page=3', $h->call(Request::create('/shop?page=3', 'GET'), 'https://x.test/shop', ['page']));
    }
}
