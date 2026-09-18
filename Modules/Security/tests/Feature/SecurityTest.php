<?php

namespace Modules\Security\Tests\Feature;

use Tests\TestCase;

class SecurityTest extends TestCase
{
    public function test_roles_page_renders(): void
    {
        $this->actingAsAdmin()->get(route('security.roles'))->assertStatus(200);
    }

    public function test_role_create_renders(): void
    {
        $this->actingAsAdmin()->get(route('security.roles.create'))->assertStatus(200);
    }

    public function test_create_role(): void
    {
        $this->actingAsAdmin()->post(route('security.roles.store'), [
            'name' => 'Custom Role', 'description' => 'A test role',
        ])->assertRedirect();
        $this->assertDatabaseHas('roles', ['name' => 'Custom Role']);
    }

    public function test_users_page_renders(): void
    {
        $this->actingAsAdmin()->get(route('security.users.index'))->assertStatus(200);
    }

    public function test_user_create_renders(): void
    {
        $this->actingAsAdmin()->get(route('security.users.create'))->assertStatus(200);
    }

    public function test_create_user(): void
    {
        $this->actingAsAdmin()->post(route('security.users.store'), [
            'name' => 'Test User', 'email' => 'new@test.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'role' => 'Cashier', 'status' => 'active',
        ])->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'new@test.com']);
    }
}
