<?php

namespace Modules\Security\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Security\Services\TwoFactorService;
use PragmaRX\Google2FAQRCode\Google2FA;
use Tests\TestCase;

class TwoFactorServiceTest extends TestCase
{
    private function service(): TwoFactorService
    {
        return app(TwoFactorService::class);
    }

    public function test_secret_is_stored_encrypted_and_enabled_flag_tracks_confirmation(): void
    {
        $user = User::factory()->create();

        $user->forceFill(['two_factor_secret' => 'PLAINTEXTSECRET'])->save();

        // Stored value in the DB must NOT equal the plaintext (encrypted cast).
        $raw = DB::table('users')->where('id', $user->id)->value('two_factor_secret');
        $this->assertNotSame('PLAINTEXTSECRET', $raw);
        // But the model decrypts it back.
        $this->assertSame('PLAINTEXTSECRET', $user->fresh()->two_factor_secret);

        // Not enrolled until confirmed_at is set.
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_generate_secret_stores_unconfirmed_and_returns_qr(): void
    {
        $user = User::factory()->create();
        $out  = $this->service()->generateSecret($user);

        $this->assertArrayHasKey('secret', $out);
        $this->assertArrayHasKey('qr', $out);
        $this->assertNotEmpty($out['secret']);
        $this->assertStringContainsString('svg', strtolower($out['qr']));

        $user->refresh();
        $this->assertSame($out['secret'], $user->two_factor_secret);
        $this->assertFalse($user->hasTwoFactorEnabled());
    }

    public function test_confirm_and_verify_accept_a_valid_code_and_reject_a_bad_one(): void
    {
        $user = User::factory()->create();
        $out  = $this->service()->generateSecret($user);

        $google2fa  = app(Google2FA::class);
        $validCode  = $google2fa->getCurrentOtp($out['secret']);

        $this->assertFalse($this->service()->verify($user->fresh(), '000000'));
        $this->assertTrue($this->service()->confirm($user->fresh(), $validCode));
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
        $this->assertTrue($this->service()->verify($user->fresh(), $validCode));
    }

    public function test_disable_clears_both_columns(): void
    {
        $user = User::factory()->create();
        $this->service()->generateSecret($user);
        $this->service()->disable($user);

        $user->refresh();
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertFalse($user->hasTwoFactorEnabled());
    }
}
