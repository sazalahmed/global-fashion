<?php

// ============================================================
// GATE 1 — Unit Tests
// Tests: Models, Services, Helpers (no DB, no HTTP)
// Run: php artisan test --testsuite=Gate1-Unit
// ============================================================

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// -------------------------------------------------------
// USER MODEL UNIT TEST
// -------------------------------------------------------
class UserModelTest extends TestCase
{
    /** @test */
    public function it_hashes_password_on_creation(): void
    {
        $user = User::factory()->make(['password' => 'plain-password']);

        $this->assertTrue(Hash::check('plain-password', $user->password));
    }

    /** @test */
    public function it_has_fillable_fields(): void
    {
        $fillable = (new User())->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
    }

    /** @test */
    public function it_hides_password_in_array(): void
    {
        $user = User::factory()->make();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    /** @test */
    public function it_casts_email_verified_at_to_datetime(): void
    {
        $user = User::factory()->make(['email_verified_at' => now()]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->email_verified_at);
    }
}

// -------------------------------------------------------
// EXAMPLE SERVICE UNIT TEST (adapt to your service)
// -------------------------------------------------------
// namespace Tests\Unit;
// use Tests\TestCase;
// use App\Services\InvoiceService;
//
// class InvoiceServiceTest extends TestCase
// {
//     protected InvoiceService $service;
//
//     protected function setUp(): void
//     {
//         parent::setUp();
//         $this->service = new InvoiceService();
//     }
//
//     /** @test */
//     public function it_calculates_total_with_tax(): void
//     {
//         $total = $this->service->calculateTotal(100, 0.15);
//         $this->assertEquals(115.00, $total);
//     }
// }
