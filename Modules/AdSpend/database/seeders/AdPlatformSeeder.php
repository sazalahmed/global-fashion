<?php

namespace Modules\AdSpend\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AdSpend\Models\AdPlatform;

class AdPlatformSeeder extends Seeder
{
    public function run(): void
    {
        $platforms = [
            ['name' => 'Facebook',   'icon' => 'fa-brands fa-facebook',   'color' => '#1877F2', 'sort_order' => 1],
            ['name' => 'Google Ads', 'icon' => 'fa-brands fa-google',     'color' => '#4285F4', 'sort_order' => 2],
            ['name' => 'Instagram',  'icon' => 'fa-brands fa-instagram',  'color' => '#E4405F', 'sort_order' => 3],
            ['name' => 'TikTok',     'icon' => 'fa-brands fa-tiktok',     'color' => '#000000', 'sort_order' => 4],
            ['name' => 'YouTube',    'icon' => 'fa-brands fa-youtube',    'color' => '#FF0000', 'sort_order' => 5],
            ['name' => 'LinkedIn',   'icon' => 'fa-brands fa-linkedin',   'color' => '#0A66C2', 'sort_order' => 6],
            ['name' => 'Twitter/X',  'icon' => 'fa-brands fa-x-twitter',  'color' => '#000000', 'sort_order' => 7],
            ['name' => 'Snapchat',   'icon' => 'fa-brands fa-snapchat',   'color' => '#FFFC00', 'sort_order' => 8],
            ['name' => 'Pinterest',  'icon' => 'fa-brands fa-pinterest',  'color' => '#E60023', 'sort_order' => 9],
            ['name' => 'Other',      'icon' => 'fa-solid fa-globe',       'color' => '#6C757D', 'sort_order' => 99],
        ];

        foreach ($platforms as $platform) {
            AdPlatform::firstOrCreate(['name' => $platform['name']], $platform);
        }
    }
}
