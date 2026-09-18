<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;

class SeoQuickWinsTest extends TestCase
{
    public function test_csp_allows_google_and_facebook_analytics(): void
    {
        $res = $this->get(route('storefront.home'));
        $csp = $res->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString('https://www.googletagmanager.com', $csp);
        $this->assertStringContainsString('https://connect.facebook.net', $csp);
        $this->assertStringContainsString('https://www.google-analytics.com', $csp);
        $this->assertMatchesRegularExpression('/script-src[^;]*googletagmanager/', $csp);
        $this->assertMatchesRegularExpression('/connect-src[^;]*google-analytics/', $csp);
    }

    public function test_csp_allows_pixelfly(): void
    {
        $res = $this->get(route('storefront.home'));
        $csp = $res->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        foreach (['script-src', 'connect-src', 'img-src', 'frame-src'] as $directive) {
            $this->assertMatchesRegularExpression(
                '/' . $directive . '[^;]*https:\/\/pixelfly\.io https:\/\/\*\.pixelfly\.io/',
                $csp,
                "CSP {$directive} must allow pixelfly.io and *.pixelfly.io"
            );
        }
    }

    public function test_non_production_forces_noindex(): void
    {
        config(['app.env' => 'testing']);
        $res = $this->get(route('storefront.home'));
        $res->assertSee('content="noindex,nofollow"', false);
    }

    public function test_production_keeps_index_on_home(): void
    {
        config(['app.env' => 'production']);
        $res = $this->get(route('storefront.home'));
        $res->assertSee('name="robots" content="index,follow"', false);
    }

    public function test_cart_page_is_noindex_follow(): void
    {
        config(['app.env' => 'production']);
        $res = $this->get(route('storefront.cart.index'));
        $res->assertSee('content="noindex,follow"', false);
    }

    public function test_login_page_is_noindex_nofollow(): void
    {
        config(['app.env' => 'production']);
        $res = $this->get(route('storefront.customer.login'));
        $res->assertSee('content="noindex,nofollow"', false);
    }

    public function test_storefront_head_has_fixed_viewport_and_pwa(): void
    {
        $res = $this->get(route('storefront.home'));
        $res->assertSee('width=device-width, initial-scale=1.0">', false);
        $res->assertDontSee('user-scalable=no', false);
        $res->assertSee('rel="manifest"', false);
        $res->assertSee('name="theme-color"', false);
    }
}
