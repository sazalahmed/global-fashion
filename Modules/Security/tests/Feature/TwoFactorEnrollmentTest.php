<?php

namespace Modules\Security\Tests\Feature;

use App\Models\User;
use Modules\Security\Services\TwoFactorService;
use PragmaRX\Google2FAQRCode\Google2FA;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TwoFactorEnrollmentTest extends TestCase
{
    public function test_two_factor_page_renders_for_authenticated_user(): void
    {
        $res = $this->actingAs(User::factory()->create())->get(route('security.two-factor'));
        $res->assertOk();
    }

    public function test_enable_returns_secret_and_qr(): void
    {
        $user = User::factory()->create();
        $res  = $this->actingAs($user)->postJson(route('security.two-factor.enable'));

        $res->assertOk()->assertJsonStructure(['secret', 'qr']);
        $this->assertNotNull($user->fresh()->two_factor_secret);
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_confirm_with_valid_code_enables_two_factor(): void
    {
        $user   = User::factory()->create();
        $secret = app(TwoFactorService::class)->generateSecret($user)['secret'];
        $code   = app(Google2FA::class)->getCurrentOtp($secret);

        $res = $this->actingAs($user->fresh())->postJson(route('security.two-factor.confirm'), ['code' => $code]);

        $res->assertOk()->assertJsonPath('ok', true);
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_confirm_with_bad_code_is_rejected(): void
    {
        $user = User::factory()->create();
        app(TwoFactorService::class)->generateSecret($user);

        $res = $this->actingAs($user->fresh())->postJson(route('security.two-factor.confirm'), ['code' => '000000']);

        $res->assertStatus(422);
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_disable_requires_correct_password(): void
    {
        $user   = User::factory()->create(['password' => bcrypt('rightpass')]);
        $secret = app(TwoFactorService::class)->generateSecret($user)['secret'];
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        // Wrong password → still enabled.
        $this->actingAs($user->fresh())
            ->from(route('security.two-factor'))
            ->delete(route('security.two-factor.disable'), ['current_password' => 'wrong'])
            ->assertRedirect(route('security.two-factor'));
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());

        // Correct password → disabled.
        $this->actingAs($user->fresh())
            ->delete(route('security.two-factor.disable'), ['current_password' => 'rightpass']);
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
        $this->assertNull($user->fresh()->two_factor_secret);
    }

    private function adminWithUsersEdit(): User
    {
        Permission::findOrCreate('users.edit', 'web');
        $role = Role::findOrCreate('Manager', 'web');
        $role->givePermissionTo('users.edit');
        $admin = User::factory()->create();
        $admin->assignRole($role);

        return $admin;
    }

    public function test_admin_can_reset_another_users_two_factor(): void
    {
        $admin  = $this->adminWithUsersEdit();
        $target = User::factory()->create();
        app(TwoFactorService::class)->generateSecret($target);
        $target->forceFill(['two_factor_confirmed_at' => now()])->save();

        $res = $this->actingAs($admin)
            ->from(route('security.users.edit', $target->id))
            ->patch(route('security.users.reset-two-factor', $target->id));

        $res->assertRedirect();
        $this->assertFalse($target->fresh()->hasTwoFactorEnabled());
        $this->assertNull($target->fresh()->two_factor_secret);
    }

    public function test_admin_cannot_reset_super_admin_two_factor(): void
    {
        $admin = $this->adminWithUsersEdit();
        $superRole = Role::findOrCreate(User::SUPER_ADMIN_ROLE, 'web');
        $super = User::factory()->create();
        $super->assignRole($superRole);

        $this->actingAs($admin)
            ->patch(route('security.users.reset-two-factor', $super->id))
            ->assertNotFound();
    }
}
