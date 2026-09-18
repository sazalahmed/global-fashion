<?php

namespace Modules\Category\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Category\Models\Category;

class CategoryDatabaseSeeder extends Seeder
{
    /**
     * Seed the Global Fashion category tree.
     *
     * Source: GLOBAL FASHION categories export.
     *   - "Parent Category" = "N/A" → a root category
     *   - "Show Home"  → show_in_top   (homepage "Top Categories")
     *   - "Show Menu"  → show_in_menu  (navigation dropdown)
     *   - "Priority"   → sort_order
     *
     * Two-pass: create every category first (idempotent, by slug), then wire
     * up parent_id so child rows can reference parents seeded in the same run.
     */
    public function run(): void
    {
        // name, parent (null = root), show_in_top, show_in_menu, sort_order
        $categories = [
            ['COMBO OFFERS',      null,            true,  true,  10],
            ['Cuban Collar Shirt', 'HALF SLEEVES', true,  false, 1],
            ['DENIM',             'PANT',          true,  false, 0],
            ['DROP SHOULDER',     null,            true,  true,  5],
            ['FULL SLEEVES',      'SHIRT',         true,  true,  4],
            ['HALF SLEEVES',      'SHIRT',         true,  true,  0],
            ['KATUA',             null,            true,  true,  3],
            ['PANT',              null,            true,  true,  2],
            ['POLO & T-SHIRT',    null,            false, true,  5],
            ['Printed Shirt',     'HALF SLEEVES',  true,  false, 2],
            ['SHIRT',             null,            true,  true,  1],
            ['Solid Color Shirt', 'HALF SLEEVES',  true,  false, 3],
            ['TROUSER',           null,            true,  true,  7],
        ];

        // Pass 1 — create/update every category without parent linkage.
        foreach ($categories as [$name, $parent, $showTop, $showMenu, $sort]) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name'         => $name,
                    'status'       => 'active',
                    'show_in_top'  => $showTop,
                    'show_in_menu' => $showMenu,
                    'sort_order'   => $sort,
                ]
            );
        }

        // Pass 2 — resolve parent relationships now that all rows exist.
        foreach ($categories as [$name, $parent]) {
            if ($parent === null) {
                continue;
            }

            $parentId = Category::where('slug', Str::slug($parent))->value('id');
            if ($parentId) {
                Category::where('slug', Str::slug($name))->update(['parent_id' => $parentId]);
            }
        }

        $this->command->info('Seeded ' . count($categories) . ' Global Fashion categories.');
    }
}
