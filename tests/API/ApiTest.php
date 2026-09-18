<?php

// ============================================================
// GATE 3 — API Tests
// Tests: REST API endpoints, Auth tokens, CRUD, Validation
// Run: php artisan test --testsuite=Gate3-API
// ============================================================

namespace Tests\API;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    // --- Auth: Login ---

    /** @test */
    public function api_user_can_login_and_receive_token(): void
    {
        $user = User::factory()->create(['password' => bcrypt('Password@123')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'Password@123',
        ], $this->apiHeaders());

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => ['token', 'user' => ['id', 'name', 'email']],
                 ])
                 ->assertJson(['success' => true]);
    }

    /** @test */
    public function api_login_fails_with_wrong_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'wrongpassword',
        ], $this->apiHeaders());

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Invalid email or password.',
                 ]);
    }

    /** @test */
    public function api_returns_401_for_unauthenticated_requests(): void
    {
        $response = $this->getJson('/api/v1/auth/profile', $this->apiHeaders());
        $response->assertStatus(401);
    }

    /** @test */
    public function api_user_can_logout(): void
    {
        ['user' => $user, 'token' => $token] = $this->actingAsUser();

        $response = $this->postJson('/api/v1/auth/logout', [], $this->apiHeaders($token));

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Logged out successfully.',
                 ]);
    }

    // --- Profile ---

    /** @test */
    public function authenticated_user_can_get_their_profile(): void
    {
        ['user' => $user, 'token' => $token] = $this->actingAsUser();

        $response = $this->getJson('/api/v1/auth/profile', $this->apiHeaders($token));

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonPath('data.id', $user->id)
                 ->assertJsonPath('data.email', $user->email);
    }

    /** @test */
    public function user_can_update_their_profile(): void
    {
        ['user' => $user, 'token' => $token] = $this->actingAsUser();

        $response = $this->putJson('/api/v1/auth/profile', [
            'name' => 'Updated Name',
        ], $this->apiHeaders($token));

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'id'   => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function profile_update_validates_name_length(): void
    {
        ['token' => $token] = $this->actingAsUser();

        $response = $this->putJson('/api/v1/auth/profile', [
            'name' => str_repeat('a', 300),
        ], $this->apiHeaders($token));

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    // --- Auth Protection ---

    /** @test */
    public function unauthenticated_user_cannot_update_profile(): void
    {
        $response = $this->putJson('/api/v1/auth/profile', [
            'name' => 'Hacker',
        ], $this->apiHeaders());

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/v1/auth/logout', [], $this->apiHeaders());
        $response->assertStatus(401);
    }

    /** @test */
    public function invalid_token_returns_401(): void
    {
        $response = $this->getJson('/api/v1/auth/profile', $this->apiHeaders('invalid-token-value'));
        $response->assertStatus(401);
    }
}
