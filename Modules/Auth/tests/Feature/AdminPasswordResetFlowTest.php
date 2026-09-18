<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Modules\Marketing\Contracts\SmsGatewayInterface;
use Modules\Security\Services\TwoFactorService;
use PragmaRX\Google2FAQRCode\Google2FA;
use Tests\TestCase;

class AdminPasswordResetFlowTest extends TestCase
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

    public function test_request_step_never_sends_email(): void
    {
        Mail::fake();
        $this->bindFakeGateway();
        $user = User::factory()->create(['phone' => '01712345678']);

        $res = $this->postJson(route('password.send-otp'), ['email' => $user->email]);

        $res->assertOk()->assertJsonPath('method', 'sms');
        Mail::assertNothingSent();
    }

    public function test_request_step_is_generic_for_unknown_email(): void
    {
        $this->bindFakeGateway();

        $res = $this->postJson(route('password.send-otp'), ['email' => 'nobody@bizpos.test']);

        $res->assertOk()->assertJsonPath('method', 'generic');
    }

    public function test_request_step_routes_enrolled_user_to_totp(): void
    {
        $this->bindFakeGateway();
        $user = User::factory()->create(['phone' => '01712345678']);
        app(TwoFactorService::class)->generateSecret($user);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        $res = $this->postJson(route('password.send-otp'), ['email' => $user->email]);

        $res->assertOk()->assertJsonPath('method', 'totp');
    }

    public function test_full_sms_reset_updates_password(): void
    {
        $this->bindFakeGateway();
        $user = User::factory()->create(['phone' => '01712345678', 'password' => bcrypt('oldpass')]);

        $this->postJson(route('password.send-otp'), ['email' => $user->email])->assertOk();
        $code = (string) cache()->get('admin_pwd_otp_' . $user->email);

        $verify = $this->postJson(route('password.verify'), ['email' => $user->email, 'code' => $code]);
        $verify->assertOk()->assertJsonPath('ok', true);
        $token = $verify->json('token');

        $reset = $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewPass1!',
            'password_confirmation' => 'NewPass1!',
        ]);
        $reset->assertRedirect();
        $this->assertTrue(Hash::check('NewPass1!', $user->fresh()->password));
    }

    public function test_full_totp_reset_updates_password(): void
    {
        $this->bindFakeGateway();
        $user = User::factory()->create(['phone' => '01712345678', 'password' => bcrypt('oldpass')]);
        $secret = app(TwoFactorService::class)->generateSecret($user)['secret'];
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        $this->postJson(route('password.send-otp'), ['email' => $user->email])->assertOk();
        $code = app(Google2FA::class)->getCurrentOtp($secret);

        $verify = $this->postJson(route('password.verify'), ['email' => $user->email, 'code' => $code]);
        $token  = $verify->assertOk()->json('token');

        $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewPass1!',
            'password_confirmation' => 'NewPass1!',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('NewPass1!', $user->fresh()->password));
    }

    public function test_verify_rejects_wrong_code(): void
    {
        $this->bindFakeGateway();
        $user = User::factory()->create(['phone' => '01712345678']);
        $this->postJson(route('password.send-otp'), ['email' => $user->email]);

        $this->postJson(route('password.verify'), ['email' => $user->email, 'code' => '000000'])
            ->assertStatus(422)
            ->assertJsonPath('ok', false);
    }

    public function test_forgot_password_page_renders_method_aware_markup(): void
    {
        $res = $this->get(route('password.request'));
        $res->assertOk();
        $res->assertSee('id="stepIdentify"', false);
        $res->assertSee('id="stepCode"', false);
    }
}
