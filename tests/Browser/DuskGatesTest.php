<?php

// ============================================================
// GATE 4 — Laravel Dusk Browser Tests (Visual Gates)
// Tests: UI Flows, Screenshots, Visual Validation
// Setup: php artisan dusk:install
// Run:   php artisan dusk
// Screenshots saved to: tests/Browser/screenshots/
// ============================================================

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

// -------------------------------------------------------
// GATE 4A — Login Page Visual + Interaction Test
// -------------------------------------------------------
class Gate4A_LoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * GATE 4A: Login page renders correctly and user can authenticate.
     * Screenshots: gate4a-01 through gate4a-04
     */
    public function test_gate4a_login_flow(): void
    {
        $user = User::factory()->create(['password' => bcrypt('Password@123')]);

        $this->browse(function (Browser $browser) use ($user) {

            // SCREENSHOT 1: Initial login page state
            $browser->visit('/login')
                    ->assertSee('Login')
                    ->screenshot('gate4a-01-login-page');
            // ✅ AI validates: Login form visible, no errors shown

            // SCREENSHOT 2: Form filled with credentials
            $browser->type('@email', $user->email)
                    ->type('@password', 'Password@123')
                    ->screenshot('gate4a-02-form-filled');
            // ✅ AI validates: Email and password fields populated

            // SCREENSHOT 3: Submit the form
            $browser->press('@login-btn')
                    ->waitForLocation('/dashboard', 5)
                    ->screenshot('gate4a-03-after-login');
            // ✅ AI validates: Dashboard loaded, user name visible

            // SCREENSHOT 4: Dashboard state
            $browser->assertSee($user->name)
                    ->assertPathIs('/dashboard')
                    ->screenshot('gate4a-04-dashboard');
            // ✅ AI validates: Welcome message, navigation menu visible

        });
    }

    /**
     * GATE 4A-ERR: Login page shows validation errors correctly.
     */
    public function test_gate4a_login_validation_errors(): void
    {
        $this->browse(function (Browser $browser) {

            $browser->visit('/login')
                    ->press('@login-btn')
                    ->screenshot('gate4a-err-01-empty-submit');
            // ✅ AI validates: Error messages visible for email and password

            $browser->assertSee('required')
                    ->type('@email', 'not-an-email')
                    ->press('@login-btn')
                    ->screenshot('gate4a-err-02-invalid-email');
            // ✅ AI validates: "Invalid email" error visible

        });
    }
}

// -------------------------------------------------------
// GATE 4B — Registration Flow Visual Test
// -------------------------------------------------------
class Gate4B_RegisterTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_gate4b_registration_flow(): void
    {
        $this->browse(function (Browser $browser) {

            // SCREENSHOT 1: Registration page
            $browser->visit('/register')
                    ->assertSee('Register')
                    ->screenshot('gate4b-01-register-page');
            // ✅ AI validates: Name, email, password, confirm fields visible

            // SCREENSHOT 2: Fill registration form
            $browser->type('@name', 'John Doe')
                    ->type('@email', 'john@example.com')
                    ->type('@password', 'Password@123')
                    ->type('@password_confirmation', 'Password@123')
                    ->screenshot('gate4b-02-form-filled');
            // ✅ AI validates: All fields populated

            // SCREENSHOT 3: Submit and verify redirect
            $browser->press('@register-btn')
                    ->waitForLocation('/dashboard', 5)
                    ->screenshot('gate4b-03-registered');
            // ✅ AI validates: Dashboard visible, user is logged in

        });
    }
}

// -------------------------------------------------------
// GATE 4C — Dashboard Navigation Test
// -------------------------------------------------------
class Gate4C_DashboardTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_gate4c_dashboard_navigation(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret')]);

        $this->browse(function (Browser $browser) use ($user) {

            $browser->loginAs($user)
                    ->visit('/dashboard')
                    ->screenshot('gate4c-01-dashboard-initial');
            // ✅ AI validates: Sidebar, header, content area visible

            // Test sidebar navigation links
            $browser->clickLink('Profile')
                    ->waitForLocation('/profile', 5)
                    ->screenshot('gate4c-02-profile-page');
            // ✅ AI validates: Profile form with user data

            $browser->back()
                    ->screenshot('gate4c-03-back-to-dashboard');
            // ✅ AI validates: Dashboard restored

        });
    }
}

// -------------------------------------------------------
// GATE 6A — Responsive Layout Test (Mobile viewport)
// -------------------------------------------------------
class Gate6A_ResponsiveTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_gate6a_mobile_layout(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {

            // Set mobile viewport (iPhone 12 size)
            $browser->resize(390, 844);

            $browser->visit('/login')
                    ->screenshot('gate6a-01-mobile-login');
            // ✅ AI validates: Login form responsive, no overflow

            $browser->loginAs($user)
                    ->visit('/dashboard')
                    ->screenshot('gate6a-02-mobile-dashboard');
            // ✅ AI validates: Hamburger menu visible, content stacked

            // Test hamburger menu opens
            $browser->click('@mobile-menu-btn')
                    ->screenshot('gate6a-03-mobile-menu-open');
            // ✅ AI validates: Sidebar drawer visible

        });
    }
}

// -------------------------------------------------------
// GATE 6B — Form Submission Test
// -------------------------------------------------------
class Gate6B_FormTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_gate6b_profile_update_form(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret')]);

        $this->browse(function (Browser $browser) use ($user) {

            $browser->loginAs($user)
                    ->visit('/profile')
                    ->screenshot('gate6b-01-profile-initial');
            // ✅ AI validates: Form pre-filled with user data

            $browser->clear('@name')
                    ->type('@name', 'Updated Name')
                    ->screenshot('gate6b-02-form-editing');
            // ✅ AI validates: Name field shows new value

            $browser->press('@save-btn')
                    ->waitForText('Saved', 5)
                    ->screenshot('gate6b-03-success-message');
            // ✅ AI validates: Success toast/alert visible

        });
    }
}

// -------------------------------------------------------
// GATE 6C — AJAX / React Component Interaction Test
// -------------------------------------------------------
class Gate6C_AjaxComponentTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_gate6c_react_component_loads(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {

            $browser->loginAs($user)
                    ->visit('/dashboard')
                    ->waitFor('[data-testid="react-root"]', 10)
                    ->screenshot('gate6c-01-react-loaded');
            // ✅ AI validates: React component rendered, no blank screen

            // Test AJAX data fetch
            $browser->waitFor('[data-testid="data-table"]', 10)
                    ->screenshot('gate6c-02-data-table');
            // ✅ AI validates: Table rows loaded via AJAX

        });
    }
}
