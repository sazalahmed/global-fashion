<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Services\AdminPasswordResetService;
use Modules\Marketing\Contracts\SmsGatewayInterface;
use Modules\Security\Services\TwoFactorService;
use PragmaRX\Google2FAQRCode\Google2FA;
use Tests\TestCase;

class AdminPasswordResetServiceTest extends TestCase
{
    private function bindFakeGateway(bool $succeed = true): object
    {
        $fake = new class($succeed) implements SmsGatewayInterface {
            public array $sent = [];
            public function __construct(public bool $succeed) {}
            public function send(string $number, string $message): bool
            {
                $this->sent[] = $number;
                return $this->succeed;
            }
            public function sendBulk(array $numbers, string $message): array
            {
                return ['sent' => 0, 'failed' => 0];
            }
        };
        $this->app->instance(SmsGatewayInterface::class, $fake);

        return $fake;
    }

    private function service(): AdminPasswordResetService
    {
        return app(AdminPasswordResetService::class);
    }

    public function test_initiate_returns_generic_for_unknown_email_and_sends_nothing(): void
    {
        $fake = $this->bindFakeGateway();
        $out  = $this->service()->initiate('nobody@bizpos.test');

        $this->assertSame('generic', $out['method']);
        $this->assertCount(0, $fake->sent);
    }

    public function test_initiate_routes_enrolled_user_to_totp_without_sms(): void
    {
        $fake = $this->bindFakeGateway();
        $user = User::factory()->create(['phone' => '01712345678']);
        app(TwoFactorService::class)->generateSecret($user);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        $out = $this->service()->initiate($user->email);

        $this->assertSame('totp', $out['method']);
        $this->assertCount(0, $fake->sent);
    }

    public function test_initiate_sends_sms_for_unenrolled_user_with_phone(): void
    {
        $fake = $this->bindFakeGateway();
        $user = User::factory()->create(['phone' => '01712345678']);

        $out = $this->service()->initiate($user->email);

        $this->assertSame('sms', $out['method']);
        $this->assertArrayHasKey('maskedPhone', $out);
        $this->assertSame(['01712345678'], $fake->sent);
        // An OTP row was written for this email.
        $this->assertNotNull(DB::table('password_reset_tokens')->where('email', $user->email)->first());
    }

    public function test_initiate_returns_unavailable_when_no_phone_and_not_enrolled(): void
    {
        $fake = $this->bindFakeGateway();
        $user = User::factory()->create(['phone' => null]);

        $out = $this->service()->initiate($user->email);

        $this->assertSame('unavailable', $out['method']);
        $this->assertCount(0, $fake->sent);
    }

    public function test_initiate_send_failed_sets_no_cooldown(): void
    {
        $this->bindFakeGateway(succeed: false);
        $user = User::factory()->create(['phone' => '01712345678']);

        $out = $this->service()->initiate($user->email);

        $this->assertSame('sms', $out['method']);
        $this->assertFalse($out['sent']);
        $this->assertSame('send_failed', $out['reason']);

        // Retry immediately succeeds (no cooldown was set) once the gateway works.
        $this->bindFakeGateway(succeed: true);
        $retry = $this->service()->initiate($user->email);
        $this->assertSame('sms', $retry['method']);
        $this->assertArrayNotHasKey('reason', $retry);
    }

    public function test_verify_issues_reset_token_for_sms_code(): void
    {
        $this->bindFakeGateway();
        $user = User::factory()->create(['phone' => '01712345678']);
        $this->service()->initiate($user->email);
        // Read the plaintext OTP from cache (service stores it there for delivery).
        $code = (string) cache()->get('admin_pwd_otp_' . $user->email);

        $token = $this->service()->verify($user->email, $code);

        $this->assertNotNull($token);
        $this->assertIsString($token);
    }

    public function test_verify_issues_reset_token_for_totp_code(): void
    {
        $this->bindFakeGateway();
        $user = User::factory()->create(['phone' => '01712345678']);
        $secret = app(TwoFactorService::class)->generateSecret($user)['secret'];
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        $code  = app(Google2FA::class)->getCurrentOtp($secret);
        $token = $this->service()->verify($user->email, $code);

        $this->assertNotNull($token);
    }

    public function test_verify_rejects_wrong_code(): void
    {
        $this->bindFakeGateway();
        $user = User::factory()->create(['phone' => '01712345678']);
        $this->service()->initiate($user->email);

        $this->assertNull($this->service()->verify($user->email, '000000'));
    }
}
