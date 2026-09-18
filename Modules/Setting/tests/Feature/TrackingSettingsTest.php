<?php

namespace Modules\Setting\Tests\Feature;

use App\Models\User;
use Modules\Setting\Models\Setting;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TrackingSettingsTest extends TestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();

        Permission::findOrCreate('settings.edit', 'web');
        $this->admin->givePermissionTo('settings.edit');
    }

    public function test_saves_new_tracking_fields(): void
    {
        $this->actingAs($this->admin);

        $this->put(route('settings.update', 'tracking'), [
            'gtm_enabled'                => '1',
            'gtm_container_id'           => 'GTM-ABCD123',
            'fbpixel_enabled'            => '1',
            'fbpixel_id'                 => '1234567890',
            'fbpixel_test_event_code'    => 'TEST12345',
            'ga4_measurement_id'         => 'G-ABCDE12345',
            'ga4_api_secret'             => 'secret_value_xyz',
            'purchase_block_risk_levels' => 'high,critical',
        ])->assertSessionHasNoErrors();

        $this->assertSame('G-ABCDE12345', Setting::get('tracking', 'ga4_measurement_id'));
        $this->assertSame('TEST12345', Setting::get('tracking', 'fbpixel_test_event_code'));
        $this->assertSame('high,critical', Setting::get('tracking', 'purchase_block_risk_levels'));
    }

    public function test_rejects_bad_ga4_measurement_id(): void
    {
        $this->actingAs($this->admin);

        $this->put(route('settings.update', 'tracking'), [
            'ga4_measurement_id' => 'not-a-valid-id',
        ])->assertSessionHasErrors('ga4_measurement_id');
    }

    public function test_saves_ga4_enabled_toggle(): void
    {
        $this->actingAs($this->admin);

        $this->put(route('settings.update', 'tracking'), [
            'ga4_enabled'        => '1',
            'ga4_measurement_id' => 'G-ABCDE12345',
        ])->assertSessionHasNoErrors();

        $this->assertTrue((bool) Setting::get('tracking', 'ga4_enabled', false));

        // Unchecked checkbox (absent from payload) must round-trip to false.
        $this->put(route('settings.update', 'tracking'), [
            'ga4_measurement_id' => 'G-ABCDE12345',
        ])->assertSessionHasNoErrors();

        $this->assertFalse((bool) Setting::get('tracking', 'ga4_enabled', false));
    }
}
