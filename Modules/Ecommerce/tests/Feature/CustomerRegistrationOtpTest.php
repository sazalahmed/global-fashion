<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\EcommerceSetting;
use Modules\Ecommerce\Models\StorefrontCustomer;
use Modules\Ecommerce\Services\CustomerOtpService;
use Modules\Marketing\Contracts\SmsGatewayInterface;
use Tests\TestCase;

class CustomerRegistrationOtpTest extends TestCase
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

    public function test_send_otp_blocks_already_registered_phone_without_sms(): void
    {
        $fake = $this->bindFakeGateway();
        StorefrontCustomer::create([
            'name' => 'Existing', 'phone' => '01712345678', 'password' => 'secret123', 'is_active' => true,
        ]);

        $res = $this->postJson(route('storefront.customer.register.send-otp'), ['phone' => '01712345678']);

        $res->assertOk()->assertJson(['ok' => false, 'code' => 'exists']);
        $this->assertCount(0, $fake->sent);
    }

    public function test_send_otp_rejects_invalid_phone(): void
    {
        $this->bindFakeGateway();

        $res = $this->postJson(route('storefront.customer.register.send-otp'), ['phone' => '123']);

        $res->assertStatus(422)->assertJsonValidationErrors(['phone']);
    }

    public function test_send_otp_sends_for_new_phone(): void
    {
        $fake = $this->bindFakeGateway();

        $res = $this->postJson(route('storefront.customer.register.send-otp'), ['phone' => '01812345678']);

        $res->assertOk()->assertJson(['ok' => true]);
        $this->assertSame(['01812345678'], $fake->sent);
    }

    public function test_verify_otp_sets_session_on_correct_code(): void
    {
        $this->bindFakeGateway();
        $service = app(CustomerOtpService::class);
        $service->send('01812345678');
        $code = (string) \Illuminate\Support\Facades\Cache::get(
            'customer_reg_otp_' . $service->normalize('01812345678')
        );

        $res = $this->postJson(route('storefront.customer.register.verify-otp'), [
            'phone' => '01812345678', 'code' => $code,
        ]);

        $res->assertOk()->assertJson(['ok' => true]);
        $this->assertSame('01812345678', session('register_verified_phone'));
    }

    public function test_register_is_rejected_without_verified_phone(): void
    {
        $this->bindFakeGateway();

        $res = $this->from(route('storefront.customer.register'))->post(route('storefront.customer.register.post'), [
            'name' => 'New User', 'phone' => '01912345678',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ]);

        $res->assertRedirect(route('storefront.customer.register'));
        $res->assertSessionHasErrors('phone');
        $this->assertDatabaseMissing('customers', ['phone' => '01912345678']);
    }

    public function test_register_succeeds_with_verified_phone(): void
    {
        $this->bindFakeGateway();

        $res = $this->withSession(['register_verified_phone' => '01912345678'])
            ->post(route('storefront.customer.register.post'), [
                'name' => 'New User', 'phone' => '01912345678',
                'password' => 'secret123', 'password_confirmation' => 'secret123',
            ]);

        $res->assertRedirect(route('storefront.customer.profile'));
        $this->assertDatabaseHas('customers', ['phone' => '01912345678', 'name' => 'New User']);
        $this->assertNull(session('register_verified_phone'));
    }

    public function test_register_bypasses_otp_when_setting_disabled(): void
    {
        $this->bindFakeGateway();
        EcommerceSetting::set('customer_otp_required', '0');

        $res = $this->post(route('storefront.customer.register.post'), [
            'name' => 'No OTP', 'phone' => '01612345678',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ]);

        $res->assertRedirect(route('storefront.customer.profile'));
        $this->assertDatabaseHas('customers', ['phone' => '01612345678']);
    }

    public function test_register_page_shows_otp_step_when_required(): void
    {
        $this->bindFakeGateway();

        $res = $this->get(route('storefront.customer.register'));

        $res->assertOk();
        $res->assertSee('id="regStep1"', false);
        $res->assertSee('id="regStep2"', false);
        $res->assertSee('id="regStep3"', false);
    }

    public function test_register_page_is_flat_when_otp_disabled(): void
    {
        $this->bindFakeGateway();
        EcommerceSetting::set('customer_otp_required', '0');

        $res = $this->get(route('storefront.customer.register'));

        $res->assertOk();
        $res->assertSee('name="password"', false);
        $res->assertDontSee('id="regStep2"', false);
    }
}
