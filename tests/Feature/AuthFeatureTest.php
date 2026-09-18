<?php

// ============================================================
// GATE 2 — Feature Tests
// Tests: Auth, UI flows, Middleware, Redirects
// Run: php artisan test --testsuite=Gate2-Feature
// ============================================================

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

// -------------------------------------------------------
// AUTHENTICATION FEATURE TESTS
// -------------------------------------------------------
class AuthFeatureTest extends TestCase
{
    use RefreshDatabase;

    // --- Login ---

    /** @test */
    public function guest_can_view_login_page(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('Password@123')]);

        $response = $this->post(route('login'), [
            'email'    => $user->email,
            'password' => 'Password@123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function user_cannot_login_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct')]);

        $response = $this->post(route('login'), [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    /** @test */
    public function user_is_locked_out_after_too_many_attempts(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 6; $i++) {
            $response = $this->post(route('login'), [
                'email'    => $user->email,
                'password' => 'wrong',
            ]);
        }

        $response->assertStatus(429);
    }

    // --- Logout ---

    /** @test */
    public function authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    // --- Middleware ---

    /** @test */
    public function guest_is_redirected_from_dashboard(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
    }
}
