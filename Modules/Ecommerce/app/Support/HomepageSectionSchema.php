<?php

namespace Modules\Ecommerce\Support;

use Illuminate\Support\Facades\Route;

/**
 * Single source of truth for the editable content of each homepage section.
 *
 * Each section_type maps to an ordered list of fields. A field is:
 *   ['key' => 'heading', 'label' => 'Heading', 'type' => 'text',
 *    'group' => 'Heading', 'default' => '...', 'options' => [...]]
 *
 * Field types: text · textarea · image · link · number · switch · highlight
 * Groups (form sections): Heading · Banner · Button · Behavior
 *
 * The same schema drives: the admin settings form, server-side validation,
 * and what each storefront partial reads via $section->getSetting(key, default).
 */
class HomepageSectionSchema
{
    /** Full map: section_type => field[]. */
    public static function all(): array
    {
        return [
            'hero_slider' => [
                self::field('autoplay', 'Auto-play slides', 'switch', 'Behavior', true),
                self::field('interval', 'Slide interval (ms)', 'number', 'Behavior', 5000),
            ],

            'features' => array_merge(
                [self::field('heading', 'Heading', 'text', 'Heading', 'Why Shop With Us')],
                [self::field('highlight', 'Emphasized word', 'text', 'Heading', 'Shop')],
                self::badge(1, 'feature-icon_1.svg', 'Return & Refund', 'Money back guarantee'),
                self::badge(2, 'feature-icon_3.svg', 'Quality Support', 'Always online 24/7'),
                self::badge(3, 'feature-icon_2.svg', 'Secure Payment', 'Protected checkout'),
                self::badge(4, 'feature-icon_4.svg', 'Daily Offers', 'Discounts every day'),
            ),

            'flash_deals' => array_merge(
                self::heading('Flash Sell', 'Flash'),
                self::viewAll('View all', 'storefront.flash-deals'),
                self::itemsCount(12),
            ),

            'categories' => array_merge(
                self::heading('Top Categories', 'Top'),
                self::itemsCount(9),
            ),

            'new_arrivals' => array_merge(
                self::heading('Our New arrival Products', 'New'),
                self::viewAll('View all', 'storefront.shop.index'),
                self::itemsCount(5),
            ),

            'combos' => array_merge(
                self::heading('Combo Packages', 'Combo'),
                self::viewAll('View all', 'storefront.combos.index'),
                self::itemsCount(6),
            ),

            'trending' => array_merge(
                self::heading('Trending Products', 'Trending'),
                self::viewAll('View all', 'storefront.shop.index'),
                self::itemsCount(10),
            ),

            'best_selling' => array_merge(
                self::heading('Our Best Selling Products', 'Best'),
                self::viewAll('View all', 'storefront.shop.index'),
                self::itemsCount(4),
                [
                    self::field('promo_image', 'Banner image', 'image', 'Banner', 'website/assets/images/best_sell_pro_img_4.jpg'),
                    self::field('promo_button_link', 'Banner URL', 'link', 'Banner', 'storefront.shop.index'),
                ],
            ),

            'special_brand' => array_merge(
                self::heading('Our Spatial Brand Products', 'Spatial'),
                self::viewAll('View all', 'storefront.shop.index'),
                self::itemsCount(9),
                self::sideBanner('website/assets/images/home2_special_banner.jpg'),
            ),

            'favourite' => array_merge(
                self::heading('Our Favorite Style Product', 'Favorite'),
                self::itemsCount(6),
                self::sideBanner('website/assets/images/favourite_pro_2_banner_img.png'),
            ),

            'blog' => array_merge(
                self::heading('Our News & Articles', 'News'),
                self::viewAll('View all', 'storefront.blog.index'),
                self::itemsCount(4),
                [
                    self::field('ad_image', 'Sidebar ad image', 'image', 'Sidebar Ad', 'website/assets/images/blog_sidebar_add_img.png'),
                    self::field('ad_link', 'Sidebar ad URL', 'link', 'Sidebar Ad', 'storefront.shop.index'),
                ],
            ),

            'brands' => array_merge(
                self::heading('Our Top Brands', 'Brands'),
                self::viewAll('View all', 'storefront.shop.index'),
                self::itemsCount(12),
            ),

            'newsletter' => [
                self::field('background_image', 'Background image', 'image', 'Banner', 'website/assets/images/subscribe_2_bg.jpg'),
                self::field('heading', 'Heading', 'text', 'Heading', 'Get Upto 70% Off Discount Coupon'),
                self::field('highlight', 'Emphasized word', 'text', 'Heading', '70%'),
                self::field('subheading', 'Subheading', 'text', 'Heading', 'by Subscribe our Newsletter'),
                self::field('input_placeholder', 'Email placeholder', 'text', 'Behavior', 'Your email'),
                self::field('button_label', 'Button label', 'text', 'Button', 'Subscribe'),
            ],
        ];
    }

    /** Fields for one section_type (empty array if unknown). */
    public static function for(string $sectionType): array
    {
        return self::all()[$sectionType] ?? [];
    }

    /** Whether a section type has any editable content fields. */
    public static function has(string $sectionType): bool
    {
        return ! empty(self::for($sectionType));
    }

    /** Preset storefront link targets: route name => human label. */
    public static function linkPresets(): array
    {
        return [
            'storefront.home'           => 'Home',
            'storefront.shop.index'     => 'Shop',
            'storefront.flash-deals'    => 'Flash Deals',
            'storefront.category.index' => 'Categories',
            'storefront.blog.index'     => 'Blog',
        ];
    }

    /**
     * Resolve a stored link value (a preset route name or a custom URL) into a
     * final href for the storefront. Unknown/blank values fall back to '#'.
     */
    public static function resolveLink(?string $value): string
    {
        if (! $value) {
            return '#';
        }
        if (array_key_exists($value, self::linkPresets()) && Route::has($value)) {
            return route($value);
        }
        return $value; // custom URL (or already-resolved href)
    }

    // ── builders ─────────────────────────────────────────────────────────

    private static function field(string $key, string $label, string $type, string $group, $default, array $options = []): array
    {
        return compact('key', 'label', 'type', 'group', 'default', 'options');
    }

    private static function heading(string $heading, string $highlight = '', string $subtitle = ''): array
    {
        return [
            self::field('heading', 'Heading', 'text', 'Heading', $heading),
            self::field('highlight', 'Emphasized word', 'text', 'Heading', $highlight),
            self::field('subtitle', 'Subtitle', 'text', 'Heading', $subtitle),
        ];
    }

    private static function viewAll(string $label, string $link): array
    {
        return [
            self::field('view_all_label', 'View-all button label', 'text', 'Button', $label),
            self::field('view_all_link', 'View-all link', 'link', 'Button', $link),
        ];
    }

    private static function itemsCount(int $default): array
    {
        return [self::field('items_count', 'Products to show', 'number', 'Behavior', $default)];
    }

    private static function sideBanner(string $image): array
    {
        return [
            self::field('banner_image', 'Banner image', 'image', 'Banner', $image),
            self::field('banner_button_link', 'Banner URL', 'link', 'Banner', 'storefront.shop.index'),
        ];
    }

    private static function badge(int $n, string $icon, string $title, string $subtitle): array
    {
        return [
            self::field("badge_{$n}_icon", "Badge {$n} icon", 'image', 'Banner', "website/assets/images/{$icon}"),
            self::field("badge_{$n}_title", "Badge {$n} title", 'text', 'Banner', $title),
            self::field("badge_{$n}_subtitle", "Badge {$n} subtitle", 'text', 'Banner', $subtitle),
        ];
    }
}
