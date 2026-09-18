<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SettingsTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => \Modules\Security\Database\Seeders\RolePermissionSeeder::class]);
        $this->artisan('db:seed', ['--class' => \Modules\Setting\Database\Seeders\SettingsSeeder::class]);

        $this->admin = User::factory()->create([
            'email' => 'admin@bizpos.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->admin->assignRole('Super Admin');
    }

    public function test_settings_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/admin/settings')
                ->assertSee('Settings');
        });
    }

    public function test_settings_has_tabs(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/admin/settings')
                ->assertSee('Business Profile');
        });
    }
}
