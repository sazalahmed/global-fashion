<?php

namespace Modules\Ecommerce\Tests\Feature;

use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Modules\Ecommerce\Models\StorefrontCustomer;
use Tests\TestCase;

class CustomerGoogleLoginTest extends TestCase
{
    /** Mock Socialite's Google driver to return the given identity. */
    private function fakeGoogleUser(string $id, string $email, string $name): void
    {
        $user = Mockery::mock(SocialiteUser::class);
        $user->shouldReceive('getId')->andReturn($id);
        $user->shouldReceive('getEmail')->andReturn($email);
        $user->shouldReceive('getName')->andReturn($name);
        $user->shouldReceive('getNickname')->andReturn(null);

        $provider = Mockery::mock(SocialiteProvider::class);
        $provider->shouldReceive('user')->andReturn($user);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_google_login_reuses_a_soft_deleted_customer_instead_of_duplicating(): void
    {
        // Existing customer that was soft-deleted — the customers.email unique
        // index still covers it, so a naive create() would 500 (the bug).
        $customer = StorefrontCustomer::create([
            'name'      => 'Freelancer Sazal',
            'email'     => 'freelancersazal@gmail.com',
            'google_id' => '118402943084322609418',
            'is_active' => true,
        ]);
        $customer->delete();

        $this->fakeGoogleUser('118402943084322609418', 'freelancersazal@gmail.com', 'Freelancer Sazal');

        $res = $this->get(route('storefront.customer.google.callback'));

        // No duplicate row; the existing record is reused and restored.
        $this->assertSame(1, StorefrontCustomer::withTrashed()->where('email', 'freelancersazal@gmail.com')->count());
        $this->assertFalse($customer->fresh()->trashed());
        $this->assertAuthenticatedAs($customer->fresh(), 'customer');
        $res->assertRedirect();
    }

    public function test_google_login_links_an_existing_active_customer_by_email(): void
    {
        // Customer registered normally (no google_id yet).
        $customer = StorefrontCustomer::create([
            'name'      => 'Regular User',
            'email'     => 'regular@example.com',
            'is_active' => true,
        ]);

        $this->fakeGoogleUser('999888777', 'regular@example.com', 'Regular User');

        $this->get(route('storefront.customer.google.callback'))->assertRedirect();

        $this->assertSame('999888777', $customer->fresh()->google_id);
        $this->assertSame(1, StorefrontCustomer::where('email', 'regular@example.com')->count());
        $this->assertAuthenticatedAs($customer->fresh(), 'customer');
    }
}
