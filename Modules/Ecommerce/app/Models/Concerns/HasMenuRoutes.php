<?php

namespace Modules\Ecommerce\Models\Concerns;

use Illuminate\Support\Facades\Route;

/**
 * Centralizes the whitelisted storefront routes a menu item may link to.
 *
 * The route NAME is what gets stored in the database (stable across URL
 * changes), but the admin UI shows a friendly label + the resolved URL —
 * route names like "storefront.shop.index" only confuse non-technical admins.
 */
trait HasMenuRoutes
{
    /**
     * Whitelisted storefront routes → friendly admin label.
     * Add new linkable destinations here (single source of truth).
     */
    public static function menuRoutes(): array
    {
        return [
            'storefront.home'              => 'Home',
            'storefront.shop.index'        => 'Shop',
            'storefront.category.index'    => 'All Categories',
            'storefront.flash-deals'       => 'Flash Deals',
            'storefront.combos.index'      => 'Combo Packages',
            'storefront.blog.index'        => 'Blog',
            'storefront.faq.index'         => 'FAQ',
            'storefront.contact'           => 'Contact',
            'storefront.cart.index'        => 'Cart',
            'storefront.wishlist.index'    => 'Wishlist',
            'storefront.compare.index'     => 'Compare',
            'storefront.customer.login'    => 'Login',
            'storefront.customer.register' => 'Register',
            'storefront.customer.profile'  => 'My Account',
        ];
    }

    /** Allowed route names (used for validation + resolveUrl). */
    public static function menuRouteNames(): array
    {
        return array_keys(static::menuRoutes());
    }

    /**
     * Options for the admin picker, keyed by route name:
     *   ['storefront.shop.index' => ['label' => 'Shop', 'url' => '…', 'path' => '/shop']]
     * The select stores the name as value but shows label + path.
     */
    public static function menuRouteOptions(): array
    {
        $options = [];

        foreach (static::menuRoutes() as $name => $label) {
            $url  = Route::has($name) ? route($name) : null;
            $path = $url ? (parse_url($url, PHP_URL_PATH) ?: '/') : '#';

            $options[$name] = [
                'label' => $label,
                'url'   => $url ?? '#',
                'path'  => $path,
            ];
        }

        return $options;
    }
}
