<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Page;
use Tests\TestCase;

class PageTest extends TestCase
{
    public function test_slug_is_auto_generated_from_title_when_blank(): void
    {
        $page = Page::create(['title' => 'Privacy Policy', 'content' => '<p>Hi</p>']);

        $this->assertSame('privacy-policy', $page->slug);
    }

    public function test_published_scope_excludes_unpublished(): void
    {
        Page::create(['title' => 'Live', 'is_published' => true]);
        Page::create(['title' => 'Draft', 'is_published' => false]);

        $this->assertSame(['Live'], Page::published()->pluck('title')->all());
    }

    public function test_admin_can_create_page(): void
    {
        $this->actingAs($this->admin)->post(route('ecommerce.pages.store'), [
            'title'   => 'About Us',
            'content' => '<p>Our story</p>',
            'is_published' => 1,
        ])->assertRedirect(route('ecommerce.pages.index'));

        $this->assertDatabaseHas('pages', ['slug' => 'about-us']);
    }

    public function test_reserved_slug_is_rejected(): void
    {
        $this->actingAs($this->admin)->post(route('ecommerce.pages.store'), [
            'title' => 'Hijack', 'slug' => 'shop', 'is_published' => 1,
        ])->assertSessionHasErrors('slug');

        $this->assertDatabaseMissing('pages', ['title' => 'Hijack']);
    }

    public function test_published_page_renders_on_storefront(): void
    {
        Page::create(['title' => 'Privacy Policy', 'slug' => 'privacy-policy',
            'content' => '<p>We respect your privacy.</p>', 'is_published' => true]);

        $this->get('/privacy-policy')->assertOk()
            ->assertSee('Privacy Policy')->assertSee('We respect your privacy.');
    }

    public function test_unpublished_page_returns_404(): void
    {
        Page::create(['title' => 'Secret', 'slug' => 'secret-page', 'is_published' => false]);

        $this->get('/secret-page')->assertNotFound();
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->get('/no-such-page')->assertNotFound();
    }

    public function test_catch_all_does_not_shadow_real_routes(): void
    {
        $this->get('/shop')->assertOk();
    }

    public function test_menu_item_page_type_resolves_to_page_url(): void
    {
        $page = Page::create(['title' => 'Terms', 'slug' => 'terms', 'is_published' => true]);

        $menu = \Modules\Ecommerce\Models\Menu::create([
            'name' => 'Footer', 'location' => 'footer-test', 'is_active' => true,
        ]);
        $item = \Modules\Ecommerce\Models\MenuItem::create([
            'menu_id' => $menu->id, 'label' => 'Terms', 'type' => 'page', 'value' => (string) $page->id,
        ]);

        $this->assertSame(route('storefront.page.show', 'terms'), $item->resolveUrl());
    }
}
