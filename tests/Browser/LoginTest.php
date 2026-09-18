<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => \Modules\Security\Database\Seeders\RolePermissionSeeder::class]);
    }

    public function test_login_page_displays(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->assertSee('BizPOS')
                ->assertPresent('input[name="email"]')
                ->assertPresent('input[name="password"]');
        });
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@bizpos.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $user->assignRole('Super Admin');

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'admin@bizpos.test')
                ->type('password', 'password')
                ->press('Login')
                ->waitForLocation('/admin')
                ->assertPathIs('/admin')
                ->assertAuthenticated();
        });
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'test@bizpos.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'test@bizpos.test')
                ->type('password', 'wrong-password')
                ->press('Login')
                ->assertPathIs('/login')
                ->assertSee('credentials');
        });
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@bizpos.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $user->assignRole('Super Admin');

        $this->browse(function (Browser $browser) {
            $browser->loginAs($user)
                ->visit('/admin')
                ->assertAuthenticated();
        });
    }
}
