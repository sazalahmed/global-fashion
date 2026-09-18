<?php

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Ecommerce\Models\Menu;
use Modules\Ecommerce\Models\MenuItem;
use Modules\Ecommerce\Services\MenuService;

class MenuSeeder extends Seeder
{
    /**
     * Reproduce the storefront's current (hardcoded) menus so behaviour is
     * identical on day one, then fully editable from the admin builder.
     * Idempotent — safe to re-run.
     */
    public function run(): void
    {
        foreach (Menu::LOCATIONS as $location) {
            $this->seedLocation($location);
        }

        app(MenuService::class)->forgetAll();
    }

    /** Seed a single location to defaults (used by run() and admin "reset"). */
    public function seedLocation(string $location): void
    {
        match ($location) {
            'header_main'   => $this->seedHeaderMain(),
            'footer'        => $this->seedFooter(),
            'mobile_drawer' => $this->seedMobileDrawer(),
            'mobile_bottom' => $this->seedMobileBottom(),
            default         => null,
        };
    }

    private function seedHeaderMain(): void
    {
        $menu = $this->menu('header_main', 'Main Menu');

        $items = [
            ['type' => 'categories_dropdown', 'label' => 'Browse Categories', 'settings' => ['limit' => 10]],
            ['type' => 'route', 'label' => 'Home',        'value' => 'storefront.home'],
            ['type' => 'route', 'label' => 'Shop',        'value' => 'storefront.shop.index'],
            ['type' => 'route', 'label' => 'Categories',  'value' => 'storefront.category.index'],
            ['type' => 'route', 'label' => 'Flash Deals', 'value' => 'storefront.flash-deals'],
            ['type' => 'route', 'label' => 'Blog',        'value' => 'storefront.blog.index'],
            ['type' => 'route', 'label' => 'Contact',     'value' => 'storefront.contact'],
            ['type' => 'widget', 'label' => 'Wishlist',   'value' => 'wishlist'],
            ['type' => 'widget', 'label' => 'Compare',    'value' => 'compare'],
            ['type' => 'widget', 'label' => 'Cart',       'value' => 'cart'],
            ['type' => 'widget', 'label' => 'Account',    'value' => 'account'],
        ];

        foreach ($items as $i => $attrs) {
            $this->item($menu, $attrs + ['sort_order' => $i]);
        }
    }

    private function seedFooter(): void
    {
        $menu = $this->menu('footer', 'Footer');

        $company = $this->item($menu, ['type' => 'heading', 'label' => 'Company', 'sort_order' => 0]);
        foreach ([
            ['type' => 'route', 'label' => 'Shop',       'value' => 'storefront.shop.index'],
            ['type' => 'route', 'label' => 'Categories', 'value' => 'storefront.category.index'],
            ['type' => 'route', 'label' => 'Blog',       'value' => 'storefront.blog.index'],
            ['type' => 'route', 'label' => 'Login',      'value' => 'storefront.customer.login',   'visibility' => 'guest'],
            ['type' => 'route', 'label' => 'My Account', 'value' => 'storefront.customer.profile', 'visibility' => 'auth'],
        ] as $i => $attrs) {
            $this->item($menu, $attrs + ['parent_id' => $company->id, 'sort_order' => $i]);
        }

        $category = $this->item($menu, ['type' => 'heading', 'label' => 'Category', 'sort_order' => 1]);
        $this->item($menu, ['type' => 'categories_dropdown', 'label' => 'Categories', 'settings' => ['limit' => 5], 'parent_id' => $category->id, 'sort_order' => 0]);

        $quick = $this->item($menu, ['type' => 'heading', 'label' => 'Quick Links', 'sort_order' => 2]);
        foreach ([
            ['type' => 'route', 'label' => 'Flash Deals', 'value' => 'storefront.flash-deals'],
            ['type' => 'route', 'label' => 'Wishlist',    'value' => 'storefront.wishlist.index'],
            ['type' => 'route', 'label' => 'Cart',        'value' => 'storefront.cart.index'],
            ['type' => 'url',   'label' => "FAQ's",       'value' => '#'],
        ] as $i => $attrs) {
            $this->item($menu, $attrs + ['parent_id' => $quick->id, 'sort_order' => $i]);
        }
    }

    private function seedMobileDrawer(): void
    {
        $menu = $this->menu('mobile_drawer', 'Mobile Menu');

        foreach ([
            ['type' => 'route', 'label' => 'Home',        'value' => 'storefront.home'],
            ['type' => 'route', 'label' => 'Shop',        'value' => 'storefront.shop.index'],
            ['type' => 'route', 'label' => 'Categories',  'value' => 'storefront.category.index'],
            ['type' => 'route', 'label' => 'Flash Deals', 'value' => 'storefront.flash-deals'],
            ['type' => 'route', 'label' => 'Blog',        'value' => 'storefront.blog.index'],
            ['type' => 'route', 'label' => 'Wishlist',    'value' => 'storefront.wishlist.index'],
            ['type' => 'route', 'label' => 'Cart',        'value' => 'storefront.cart.index'],
        ] as $i => $attrs) {
            $this->item($menu, $attrs + ['sort_order' => $i]);
        }
    }

    private function seedMobileBottom(): void
    {
        $menu = $this->menu('mobile_bottom', 'Bottom Navigation');

        foreach ([
            ['type' => 'route',  'label' => 'Home',       'value' => 'storefront.home',           'icon' => 'fas fa-home'],
            ['type' => 'route',  'label' => 'Categories', 'value' => 'storefront.category.index', 'icon' => 'fas fa-th-large'],
            ['type' => 'widget', 'label' => 'Search',     'value' => 'search',                    'icon' => 'fas fa-search'],
            ['type' => 'widget', 'label' => 'Cart',       'value' => 'cart',                      'icon' => 'fas fa-shopping-cart'],
            ['type' => 'widget', 'label' => 'Profile',    'value' => 'account',                   'icon' => 'fas fa-user'],
        ] as $i => $attrs) {
            $this->item($menu, $attrs + ['sort_order' => $i]);
        }
    }

    // ── Helpers ──

    private function menu(string $location, string $name): Menu
    {
        $menu = Menu::updateOrCreate(['location' => $location], ['name' => $name, 'is_active' => true]);

        // Clear existing items (children first to respect the self-ref FK).
        $menu->items()->whereNotNull('parent_id')->delete();
        $menu->items()->delete();

        return $menu;
    }

    private function item(Menu $menu, array $attrs): MenuItem
    {
        return $menu->items()->create($attrs + [
            'type'       => 'url',
            'target'     => '_self',
            'visibility' => 'all',
            'is_active'  => true,
            'sort_order' => 0,
        ]);
    }
}
