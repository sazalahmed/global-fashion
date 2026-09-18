<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Modules\Ecommerce\Models\StorefrontCustomer;
use Modules\Ecommerce\Services\CustomerOtpService;
use Modules\Marketing\Contracts\SmsGatewayInterface;
use Tests\TestCase;

class CustomerPasswordResetOtpTest extends TestCase
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

    private function makeCustomer(string $phone = '01712345678'): StorefrontCustomer
    {
        return StorefrontCustomer::create([
            'name' => 'Reset User', 'phone' => $phone, 'password' => 'oldsecret', 'is_active' => true,
        ]);
    }

    public function test_send_reset_otp_blocks_unknown_phone_without_sms(): void
    {
        $fake = $this->bindFakeGateway();

        $res = $this->from(route('storefront.customer.password.request'))
            ->post(route('storefront.customer.password.email'), ['phone' => '01999999999']);

        $res->assertRedirect(route('storefront.customer.password.request'));
        $res->assertSessionHasErrors('phone');
        $this->assertCount(0, $fake->sent);
        $this->assertNull(session('reset_phone'));
    }

    public function test_send_reset_otp_rejects_invalid_phone(): void
    {
        $this->bindFakeGateway();

        $res = $this->from(route('storefront.customer.password.request'))
            ->post(route('storefront.customer.password.email'), ['phone' => '123']);

        $res->assertRedirect(route('storefront.customer.password.request'));
        $res->assertSessionHasErrors('phone');
    }

    public function test_send_reset_otp_sends_and_redirects_for_existing_customer(): void
    {
        $fake = $this->bindFakeGateway();
        $this->makeCustomer('01712345678');

        $res = $this->post(route('storefront.customer.password.email'), ['phone' => '01712345678']);

        $res->assertRedirect(route('storefront.customer.password.reset'));
        $this->assertSame(['01712345678'], $fake->sent);
        $this->assertSame('01712345678', session('reset_phone'));
    }

    public function test_reset_form_redirects_without_pending_session(): void
    {
        $this->bindFakeGateway();

        $res = $this->get(route('storefront.customer.password.reset'));

        $res->assertRedirect(route('storefront.customer.password.request'));
    }

    public function test_reset_password_rejects_wrong_code(): void
    {
        $this->bindFakeGateway();
        $customer = $this->makeCustomer('01712345678');
        app(CustomerOtpService::class)->send('01712345678', 'pwd');

        $res = $this->withSession(['reset_phone' => '01712345678'])
            ->from(route('storefront.customer.password.reset'))
            ->post(route('storefront.customer.password.store'), [
                'code' => '000000', 'password' => 'newsecret', 'password_confirmation' => 'newsecret',
            ]);

        $res->assertRedirect(route('storefront.customer.password.reset'));
        $res->assertSessionHasErrors('code');
        $this->assertTrue(Hash::check('oldsecret', $customer->fresh()->password));
    }

    public function test_reset_password_succeeds_with_correct_code(): void
    {
        $this->bindFakeGateway();
        $customer = $this->makeCustomer('01712345678');
        $service = app(CustomerOtpService::class);
        $service->send('01712345678', 'pwd');
        $code = (string) Cache::get('customer_pwd_otp_' . $service->normalize('01712345678'));

        $res = $this->withSession(['reset_phone' => '01712345678'])
            ->post(route('storefront.customer.password.store'), [
                'code' => $code, 'password' => 'newsecret', 'password_confirmation' => 'newsecret',
            ]);

        $res->assertRedirect(route('storefront.customer.login'));
        $this->assertTrue(Hash::check('newsecret', $customer->fresh()->password));
        $this->assertNull(session('reset_phone'));
    }

    public function test_reset_password_rejected_without_session(): void
    {
        $this->bindFakeGateway();

        $res = $this->post(route('storefront.customer.password.store'), [
            'code' => '123456', 'password' => 'newsecret', 'password_confirmation' => 'newsecret',
        ]);

        $res->assertRedirect(route('storefront.customer.password.request'));
    }
}
