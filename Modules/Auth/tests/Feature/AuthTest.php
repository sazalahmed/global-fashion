<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_login_page_renders(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertViewIs('auth::login');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@bizpos.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $response = $this->post(route('login.submit'), [
            'email' => 'test@bizpos.test',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'test@bizpos.test']);

        $response = $this->post(route('login.submit'), [
            'email' => 'test@bizpos.test',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect();
        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $response = $this->actingAs($this->admin)->post(route('logout'));
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect(route('login'));
    }

    public function test_forgot_password_page_renders(): void
    {
        $response = $this->get(route('password.request'));
        $response->assertStatus(200);
    }
}
