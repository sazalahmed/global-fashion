<?php

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Ecommerce\Models\HomepageSection;

class HomepageSectionSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            ['section_type' => 'hero_slider',     'title' => 'Hero Slider',         'sort_order' => 1,  'is_active' => true,  'settings' => ['auto_play' => true, 'interval' => 5000]],
            ['section_type' => 'features',        'title' => 'Trust Badges',        'sort_order' => 2,  'is_active' => true,  'settings' => []],
            ['section_type' => 'flash_deals',     'title' => 'Flash Sale',          'sort_order' => 3,  'is_active' => true,  'settings' => ['items_count' => 12]],
            ['section_type' => 'categories',      'title' => 'Top Categories',      'sort_order' => 4,  'is_active' => true,  'settings' => ['items_count' => 9]],
            ['section_type' => 'new_arrivals',    'title' => 'New Arrivals',        'sort_order' => 5,  'is_active' => true,  'settings' => ['items_count' => 5]],
            ['section_type' => 'trending',        'title' => 'Trending Products',   'sort_order' => 6,  'is_active' => true,  'settings' => ['items_count' => 10]],
            ['section_type' => 'best_selling',    'title' => 'Best Selling',        'sort_order' => 7,  'is_active' => true,  'settings' => ['items_count' => 4]],
            ['section_type' => 'special_brand',   'title' => 'Special Products',    'sort_order' => 8,  'is_active' => true,  'settings' => ['items_count' => 9]],
            ['section_type' => 'favourite',       'title' => 'Favourite Products',  'sort_order' => 9,  'is_active' => true,  'settings' => ['items_count' => 6]],
            ['section_type' => 'brands',          'title' => 'Our Brands',          'sort_order' => 10, 'is_active' => true,  'settings' => ['items_count' => 12]],
            ['section_type' => 'blog',            'title' => 'Latest Articles',     'sort_order' => 11, 'is_active' => true,  'settings' => ['items_count' => 4]],
            ['section_type' => 'newsletter',      'title' => 'Newsletter',          'sort_order' => 12, 'is_active' => true,  'settings' => []],
        ];

        foreach ($sections as $section) {
            HomepageSection::updateOrCreate(
                ['section_type' => $section['section_type']],
                $section
            );
        }
    }
}
