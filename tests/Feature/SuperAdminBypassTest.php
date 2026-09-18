<?php
// tests/Feature/SuperAdminBypassTest.php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminBypassTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_passes_any_permission_without_explicit_grant(): void
    {
        Role::findOrCreate('Super Admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $this->actingAs($user);

        // No permissions exist at all, but Gate::before short-circuits to true.
        $this->assertTrue($user->can('anything.at.all'));
        $this->assertTrue(checkUserHasPermission('sales.delete'));
    }
}
