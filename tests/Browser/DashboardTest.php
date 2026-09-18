<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DashboardTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => \Modules\Security\Database\Seeders\RolePermissionSeeder::class]);
        $this->artisan('db:seed', ['--class' => \Modules\Accounting\Database\Seeders\ChartOfAccountsSeeder::class]);

        $this->admin = User::factory()->create([
            'email' => 'admin@bizpos.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->admin->assignRole('Super Admin');
    }

    public function test_dashboard_renders_with_kpi_cards(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/admin')
                ->assertSee("Today's Sales")
                ->assertPresent('.bp-stat-card');
        });
    }

    public function test_dashboard_has_sidebar_navigation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/admin')
                ->assertPresent('.bp-sidebar')
                ->assertSeeLink('Products')
                ->assertSeeLink('Sales')
                ->assertSeeLink('Purchases');
        });
    }

    public function test_dashboard_shows_recent_sales_table(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/admin')
                ->assertPresent('.bp-card');
        });
    }

    public function test_unauthenticated_redirects_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin')
                ->assertPathIs('/login');
        });
    }
}
