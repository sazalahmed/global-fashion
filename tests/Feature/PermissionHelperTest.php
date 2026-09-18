<?php

namespace Tests\Feature;

use App\Exceptions\PermissionDeniedException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PermissionHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_bp_authorize_throws_when_user_lacks_permission(): void
    {
        Permission::findOrCreate('products.view', 'web');
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->expectException(PermissionDeniedException::class);
        bpAuthorize('products.view');
    }

    public function test_bp_can_any_true_when_user_has_one(): void
    {
        Permission::findOrCreate('products.view', 'web');
        Permission::findOrCreate('products.create', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo('products.create');
        $this->actingAs($user);

        $this->assertTrue(bpCanAny('products.view', 'products.create'));
        $this->assertFalse(bpCanAny('sales.view', 'sales.create'));
    }
}
