<?php
// tests/Feature/RolePermissionSeederTest.php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Security\Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_catalog_and_prunes_dead_permissions(): void
    {
        // Stale permission that must be pruned.
        Permission::findOrCreate('pos.access', 'web');

        $this->seed(RolePermissionSeeder::class);

        $this->assertDatabaseHas('permissions', ['name' => 'manufacturing.view', 'guard_name' => 'web']);
        $this->assertDatabaseMissing('permissions', ['name' => 'pos.access']);
        $this->assertTrue(Role::where('name', 'Super Admin')->where('guard_name', 'web')->exists());

        $manager = Role::where('name', 'Manager')->first();
        $this->assertTrue($manager->hasPermissionTo('products.view'));
        $this->assertFalse($manager->hasPermissionTo('users.delete'));
    }
}
