<?php

namespace Modules\Ecommerce\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Modules\Ecommerce\Services\CustomerOtpService;
use Modules\Marketing\Contracts\SmsGatewayInterface;
use Tests\TestCase;

class CustomerOtpServiceTest extends TestCase
{
    private function fakeGateway(bool $succeed = true): SmsGatewayInterface
    {
        return new class($succeed) implements SmsGatewayInterface {
            public array $sent = [];
            public function __construct(public bool $succeed) {}
            public function send(string $number, string $message): bool
            {
                $this->sent[] = ['number' => $number, 'message' => $message];
                return $this->succeed;
            }
            public function sendBulk(array $numbers, string $message): array
            {
                return ['sent' => 0, 'failed' => 0];
            }
        };
    }

    public function test_send_stores_code_and_sends_sms(): void
    {
        $gateway = $this->fakeGateway();
        $service = new CustomerOtpService($gateway);

        $result = $service->send('01712-345678');

        $this->assertTrue($result['ok']);
        $this->assertCount(1, $gateway->sent);
        $code = Cache::get('customer_reg_otp_' . $service->normalize('01712-345678'));
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $code);
    }

    public function test_resend_within_cooldown_is_blocked(): void
    {
        $gateway = $this->fakeGateway();
        $service = new CustomerOtpService($gateway);

        $service->send('01712345678');
        $second = $service->send('01712345678');

        $this->assertFalse($second['ok']);
        $this->assertSame('cooldown', $second['reason']);
        $this->assertCount(1, $gateway->sent); // no second SMS
    }

    public function test_send_failure_does_not_set_cooldown(): void
    {
        $gateway = $this->fakeGateway(succeed: false);
        $service = new CustomerOtpService($gateway);

        $first = $service->send('01712345678');
        $this->assertFalse($first['ok']);
        $this->assertSame('send_failed', $first['reason']);

        // A retry is allowed immediately (no cooldown set on failure).
        $okGateway = $this->fakeGateway();
        $service2 = new CustomerOtpService($okGateway);
        $this->assertTrue($service2->send('01712345678')['ok']);
    }

    public function test_verify_succeeds_for_correct_code_and_clears_it(): void
    {
        $service = new CustomerOtpService($this->fakeGateway());
        $service->send('01712345678');
        $code = Cache::get('customer_reg_otp_' . $service->normalize('01712345678'));

        $this->assertTrue($service->verify('01712345678', (string) $code));
        // Code consumed; second verify fails.
        $this->assertFalse($service->verify('01712345678', (string) $code));
    }

    public function test_verify_fails_for_wrong_code(): void
    {
        $service = new CustomerOtpService($this->fakeGateway());
        $service->send('01712345678');

        $this->assertFalse($service->verify('01712345678', '000000'));
    }

    public function test_verify_invalidates_after_five_attempts(): void
    {
        $service = new CustomerOtpService($this->fakeGateway());
        $service->send('01712345678');
        $code = (string) Cache::get('customer_reg_otp_' . $service->normalize('01712345678'));

        for ($i = 0; $i < 5; $i++) {
            $service->verify('01712345678', '111111'); // wrong
        }
        // 6th attempt invalidates even with the correct code.
        $this->assertFalse($service->verify('01712345678', $code));
    }

    public function test_normalize_strips_non_digits(): void
    {
        $service = new CustomerOtpService($this->fakeGateway());
        $this->assertSame('01712345678', $service->normalize(' 01712-345678 '));
    }

    public function test_default_purpose_preserves_registration_cache_keys(): void
    {
        $service = new CustomerOtpService($this->fakeGateway());
        $service->send('01712345678');

        // Default purpose 'reg' keeps the original key shape (backward compat).
        $this->assertNotNull(Cache::get('customer_reg_otp_01712345678'));
    }

    public function test_purpose_scopes_codes_so_reg_and_pwd_do_not_collide(): void
    {
        $service = new CustomerOtpService($this->fakeGateway());

        $service->send('01712345678', 'reg');
        $service->send('01712345678', 'pwd');

        $regCode = (string) Cache::get('customer_reg_otp_01712345678');
        $pwdCode = (string) Cache::get('customer_pwd_otp_01712345678');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $regCode);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $pwdCode);

        // Verifying with the wrong purpose must fail; the correct purpose succeeds
        // and only consumes its own code.
        $this->assertFalse($service->verify('01712345678', $pwdCode, 'reg'));
        $this->assertTrue($service->verify('01712345678', $pwdCode, 'pwd'));
        $this->assertTrue($service->verify('01712345678', $regCode, 'reg'));
    }
}
