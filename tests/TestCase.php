<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * A baseline authenticated admin user available to every test as
     * $this->admin. Many module tests rely on this in their own setUp()
     * (e.g. `$this->actingAs($this->admin)`); tests that need a specific
     * user can still create and use their own.
     */
    protected \App\Models\User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = \App\Models\User::factory()->create();
    }

    /**
     * Authenticate as the baseline admin user for web (session) tests.
     */
    protected function actingAsAdmin(): static
    {
        return $this->actingAs($this->admin);
    }

    /**
     * Authenticate as a user and return the token (for API tests).
     */
    protected function actingAsUser(array $overrides = []): array
    {
        $user = \App\Models\User::factory()->create($overrides);
        $token = $user->createToken('test-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Return JSON API headers.
     */
    protected function apiHeaders(string $token = null): array
    {
        $headers = ['Accept' => 'application/json'];
        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
        }
        return $headers;
    }

    /**
     * Assert a validation error response.
     */
    protected function assertValidationError($response, string $field): void
    {
        $response->assertStatus(422)
                 ->assertJsonValidationErrors([$field]);
    }
}
