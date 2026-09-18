<?php

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Ecommerce\Models\SeoPage;

class SeoPageSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['home', 'shop', 'categories', 'blog', 'flash-deals'] as $key) {
            SeoPage::firstOrCreate(['key' => $key]);
        }
    }
}
