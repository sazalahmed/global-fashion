<?php

namespace Modules\Ecommerce\Tests\Unit;

use Modules\Ecommerce\Models\EcommerceSetting;
use Modules\Ecommerce\Support\Seo;
use Tests\TestCase;

class SeoTest extends TestCase
{
    public function test_resolve_prefers_admin_then_derived_then_default(): void
    {
        EcommerceSetting::set('seo_default_title', 'Default');
        $seo = Seo::make();
        $this->assertSame('Admin', $seo->resolve('Admin', 'Derived', 'seo_default_title'));
        $this->assertSame('Derived', $seo->resolve(null, 'Derived', 'seo_default_title'));
        $this->assertSame('Default', $seo->resolve(null, null, 'seo_default_title'));
    }

    public function test_rendered_title_appends_site_with_separator_and_caps_length(): void
    {
        EcommerceSetting::set('seo_site_name', 'BizShop');
        EcommerceSetting::set('seo_title_separator', '|');
        $seo = Seo::make()->title('Red Shirt');
        $this->assertSame('Red Shirt | BizShop', $seo->renderedTitle());

        $long = str_repeat('a', 100);
        $this->assertLessThanOrEqual(80, strlen(Seo::make()->title($long)->renderedTitle()));
    }

    public function test_rendered_description_strips_tags_and_caps_160(): void
    {
        $seo = Seo::make()->description('<p>' . str_repeat('x', 300) . '</p>');
        $out = $seo->renderedDescription();
        $this->assertStringNotContainsString('<p>', $out);
        $this->assertLessThanOrEqual(160, strlen($out));
    }

    public function test_rendered_image_makes_absolute(): void
    {
        $seo = Seo::make()->image('storage/og.jpg');
        $this->assertStringStartsWith('http', $seo->renderedImage());
        $this->assertSame('https://cdn.test/x.jpg', Seo::make()->image('https://cdn.test/x.jpg')->renderedImage());
    }
}
