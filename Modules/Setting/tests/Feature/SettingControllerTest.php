<?php

namespace Modules\Setting\Tests\Feature;

use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Modules\Setting\Database\Seeders\SettingsSeeder::class);
    }

    public function test_settings_page_renders(): void
    {
        $this->actingAsAdmin()->get(route('settings.index'))->assertStatus(200);
    }

    public function test_update_business_profile(): void
    {
        $this->actingAsAdmin()->post(route('settings.update', 'business'), [
            'business_name' => 'BizPOS Test',
            'business_phone' => '01700000000',
        ])->assertRedirect();
    }
}
