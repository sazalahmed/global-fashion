<?php

namespace Modules\Setting\Tests\Feature;

use App\Services\ManifestGenerator;
use Modules\Setting\Models\Setting;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ManifestGeneratorTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed a realistic base manifest so generation preserves the rest.
        $this->tmp = sys_get_temp_dir() . '/bz_manifest_' . getmypid() . '.json';
        file_put_contents($this->tmp, json_encode([
            'name'       => 'BizPOS Pro',
            'short_name' => 'BizPOS',
            'start_url'  => '/',
            'display'    => 'standalone',
            'icons'      => [['src' => '/images/icons/icon-192.png', 'sizes' => '192x192']],
        ]));
    }

    protected function tearDown(): void
    {
        @unlink($this->tmp);
        parent::tearDown();
    }

    private function generator(): ManifestGenerator
    {
        return app(ManifestGenerator::class);
    }

    public function test_it_writes_business_name_and_preserves_icons(): void
    {
        Setting::set('business', 'company_name', 'Global Fashion');
        Setting::flushCache();

        $result = $this->generator()->generate($this->tmp);

        $this->assertSame('Global Fashion', $result['name']);
        // "Global Fashion" (14 chars) → first word for the short label.
        $this->assertSame('Global', $result['short_name']);
        // Untouched keys survive.
        $this->assertArrayHasKey('icons', $result);
        $this->assertSame('/', $result['start_url']);

        // And it was actually written to disk.
        $onDisk = json_decode((string) file_get_contents($this->tmp), true);
        $this->assertSame('Global Fashion', $onDisk['name']);
    }

    public function test_short_name_keeps_a_brief_brand_whole(): void
    {
        Setting::set('business', 'company_name', 'Acme');
        Setting::flushCache();

        $result = $this->generator()->generate($this->tmp);

        $this->assertSame('Acme', $result['short_name']);
    }

    public function test_it_falls_back_when_name_is_blank(): void
    {
        Setting::set('business', 'company_name', '');
        Setting::flushCache();

        $result = $this->generator()->generate($this->tmp);

        $this->assertSame('BizPOS Pro', $result['name']);
    }

    public function test_generate_route_regenerates_and_redirects(): void
    {
        Permission::findOrCreate('settings.edit', 'web');
        $this->admin->givePermissionTo('settings.edit');

        Setting::set('business', 'company_name', 'Global Fashion');
        Setting::flushCache();

        // This action writes the real public/manifest.json — back it up and
        // restore afterwards so the repo file is left untouched.
        $real   = public_path('manifest.json');
        $backup = is_file($real) ? file_get_contents($real) : null;

        try {
            $res = $this->actingAs($this->admin)->post(route('settings.manifest.generate'));

            $res->assertRedirect();
            $res->assertSessionHas('success');

            $written = json_decode((string) file_get_contents($real), true);
            $this->assertSame('Global Fashion', $written['name']);
        } finally {
            if ($backup !== null) {
                file_put_contents($real, $backup);
            }
        }
    }
}
