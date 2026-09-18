# Admin Password Reset via Google Authenticator + SMS — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace email in the admin password-reset flow with Google Authenticator (TOTP) when the admin is enrolled, falling back to SMS OTP (to the stored phone) when not — plus opt-in TOTP enrollment and an admin "reset/clear 2FA for others" control.

**Architecture:** Two new service classes (`TwoFactorService` for the TOTP secret lifecycle, `AdminPasswordResetService` for the reset handshake) keep `AuthController` thin. The existing `password_reset_tokens` table and `resetPassword` step are reused unchanged — only the "prove identity" step changes. SMS reuses the already-bound `SmsGatewayInterface` (BulkSMSBD). Enrollment lives in the Security module's self-service area; lockout recovery reuses the existing super-admin "set another user's password" route.

**Tech Stack:** Laravel 12, PHP 8.2+, Blade + jQuery + Bootstrap 5, MySQL, `pragmarx/google2fa-qrcode` (+ `bacon/bacon-qr-code`), Spatie Permission, PHPUnit 11.

## Global Constraints

- PHP `^8.2`; Laravel `^12.0`. Composer packages only (no CDN); commit nothing that pulls remote assets at runtime.
- **No email** in the admin reset path. After this work, `Mail::to(...)` / `OtpMail` must not be reachable from password reset.
- All new JS starts with `'use strict';`. No inline `style="..."`; new visual states use `bp-`-prefixed classes in `public/css/style.css`, each with a `[data-theme="dark"]` override. Runtime show/hide may use jQuery `.addClass('d-none')` / `.removeClass('d-none')` (the allowed exception).
- Named routes only — never hardcode URL paths (Blade, PHP, and inline JS).
- Currency/date formatting rules do not apply here (no money/date display).
- Tests run with: `php artisan test --filter=<ClassOrMethod>`. The base `Tests\TestCase` uses `RefreshDatabase` and exposes `$this->admin` (a factory user).
- Secrets/codes must never be logged.
- Mass assignment: set `two_factor_secret` / `two_factor_confirmed_at` via `forceFill([...])->save()` (NOT added to `$fillable`).

---

## File Structure

**Create:**
- `database/migrations/2026_06_26_000001_add_two_factor_columns_to_users_table.php` — two nullable columns.
- `Modules/Security/app/Services/TwoFactorService.php` — TOTP secret generate/confirm/verify/disable + QR.
- `Modules/Auth/app/Services/AdminPasswordResetService.php` — initiate (route by enrollment) + verify (issue reset token).
- `Modules/Security/app/Http/Controllers/TwoFactorController.php` — self-service enrollment endpoints.
- `Modules/Security/resources/views/users/two-factor.blade.php` — enrollment UI.
- Tests:
  - `Modules/Security/tests/Feature/TwoFactorServiceTest.php`
  - `Modules/Auth/tests/Feature/AdminPasswordResetServiceTest.php`
  - `Modules/Auth/tests/Feature/AdminPasswordResetFlowTest.php`
  - `Modules/Security/tests/Feature/TwoFactorEnrollmentTest.php`

**Modify:**
- `app/Models/User.php` — casts + `hasTwoFactorEnabled()`.
- `Modules/Auth/app/Http/Controllers/AuthController.php` — `requestReset` / `verifyReset`; remove email.
- `Modules/Auth/routes/web.php` — add `password.verify`; remap `password.send-otp`.
- `Modules/Auth/resources/views/forgot-password.blade.php` — method-aware AJAX (TOTP vs SMS vs unavailable).
- `Modules/Security/routes/web.php` — `two-factor.*` + `users.reset-two-factor`.
- `Modules/Security/app/Http/Controllers/UserController.php` — `resetTwoFactor()`.
- `Modules/Security/resources/views/users/create.blade.php` — "Reset / disable Two-Factor" control on edit.
- `Modules/Core/resources/views/partials/header.blade.php` — "Two-Factor Authentication" menu item.
- `public/css/style.css` — new `bp-` classes + dark-mode overrides.

**Delete (cleanup, after confirming no other references):**
- `Modules/Auth/app/Mail/OtpMail.php`
- `Modules/Auth/resources/views/emails/otp.blade.php`

---

## Task 1: Add Composer dependencies

**Files:**
- Modify: `composer.json` (via `composer require`, do not hand-edit)

**Interfaces:**
- Produces: classes `PragmaRX\Google2FAQRCode\Google2FA` (TOTP + inline QR) available to the container.

- [ ] **Step 1: Install the packages**

Run:
```bash
composer require pragmarx/google2fa-qrcode bacon/bacon-qr-code
```
Expected: both added to `composer.json` `require`, `composer.lock` updated, no errors.

- [ ] **Step 2: Verify the class resolves**

Run:
```bash
php artisan tinker --execute="echo get_class(app(\PragmaRX\Google2FAQRCode\Google2FA::class));"
```
Expected output: `PragmaRX\Google2FAQRCode\Google2FA`

- [ ] **Step 3: Commit**

```bash
git add composer.json composer.lock
git commit -m "build(security): add google2fa-qrcode for TOTP 2FA"
```

---

## Task 2: Add `two_factor` columns + User model support

**Files:**
- Create: `database/migrations/2026_06_26_000001_add_two_factor_columns_to_users_table.php`
- Modify: `app/Models/User.php:55-62` (the `casts()` method) and add a helper method
- Test: `Modules/Security/tests/Feature/TwoFactorServiceTest.php` (model behavior portion)

**Interfaces:**
- Produces:
  - `users.two_factor_secret` (`text`, nullable, `encrypted` cast)
  - `users.two_factor_confirmed_at` (`timestamp`, nullable, `datetime` cast)
  - `User::hasTwoFactorEnabled(): bool` — true iff `two_factor_confirmed_at` is non-null.

- [ ] **Step 1: Write the failing test**

Create `Modules/Security/tests/Feature/TwoFactorServiceTest.php`:
```php
<?php

namespace Modules\Security\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TwoFactorServiceTest extends TestCase
{
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
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=TwoFactorServiceTest::test_secret_is_stored_encrypted_and_enabled_flag_tracks_confirmation`
Expected: FAIL — column `two_factor_secret` does not exist / `hasTwoFactorEnabled` undefined.

- [ ] **Step 3: Create the migration**

Create `database/migrations/2026_06_26_000001_add_two_factor_columns_to_users_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_confirmed_at']);
        });
    }
};
```

- [ ] **Step 4: Update the User model**

In `app/Models/User.php`, change the `casts()` method body to:
```php
    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'last_login_at'           => 'datetime',
            'password'                => 'hashed',
            'two_factor_secret'       => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
```
And add this method (e.g. just after `isSuperAdmin()`):
```php
    /**
     * Whether the user has completed Google Authenticator (TOTP) enrollment.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_confirmed_at);
    }
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --filter=TwoFactorServiceTest::test_secret_is_stored_encrypted_and_enabled_flag_tracks_confirmation`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_06_26_000001_add_two_factor_columns_to_users_table.php app/Models/User.php Modules/Security/tests/Feature/TwoFactorServiceTest.php
git commit -m "feat(security): add two_factor columns and User helper"
```

---

## Task 3: `TwoFactorService`

**Files:**
- Create: `Modules/Security/app/Services/TwoFactorService.php`
- Test: `Modules/Security/tests/Feature/TwoFactorServiceTest.php` (add methods)

**Interfaces:**
- Consumes: `PragmaRX\Google2FAQRCode\Google2FA` (from Task 1); `User` columns (from Task 2).
- Produces:
  - `generateSecret(User $user): array` → `['secret' => string, 'qr' => string]` (`qr` = inline SVG/data-URI). Stores the secret unconfirmed (also nulls any prior `two_factor_confirmed_at`).
  - `confirm(User $user, string $code): bool` — verifies `$code`; on success sets `two_factor_confirmed_at = now()`.
  - `verify(User $user, string $code): bool` — verifies against the stored secret with a ±1 time-step window; false if no secret.
  - `disable(User $user): void` — nulls both columns.

- [ ] **Step 1: Write the failing tests**

Add to `Modules/Security/tests/Feature/TwoFactorServiceTest.php` (and add the imports `use Modules\Security\Services\TwoFactorService;` and `use PragmaRX\Google2FAQRCode\Google2FA;` at the top):
```php
    private function service(): TwoFactorService
    {
        return app(TwoFactorService::class);
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
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=TwoFactorServiceTest`
Expected: the three new tests FAIL — `TwoFactorService` does not exist.

- [ ] **Step 3: Implement the service**

Create `Modules/Security/app/Services/TwoFactorService.php`:
```php
<?php

namespace Modules\Security\Services;

use App\Models\User;
use PragmaRX\Google2FAQRCode\Google2FA;

class TwoFactorService
{
    /** Accept the previous/next 30s window to tolerate clock drift. */
    private const WINDOW = 1;

    public function __construct(private Google2FA $google2fa) {}

    /**
     * Generate a fresh secret, store it UNCONFIRMED, and return the secret +
     * an inline QR image for the authenticator app.
     *
     * @return array{secret:string, qr:string}
     */
    public function generateSecret(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey();

        $user->forceFill([
            'two_factor_secret'       => $secret,
            'two_factor_confirmed_at' => null,
        ])->save();

        $qr = $this->google2fa->getQRCodeInline(
            (string) config('app.name'),
            $user->email,
            $secret
        );

        return ['secret' => $secret, 'qr' => $qr];
    }

    /**
     * Verify a code and, on success, mark enrollment as confirmed.
     */
    public function confirm(User $user, string $code): bool
    {
        if (! $this->verify($user, $code)) {
            return false;
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return true;
    }

    /**
     * Verify a TOTP code against the user's stored secret (±1 window).
     */
    public function verify(User $user, string $code): bool
    {
        if (empty($user->two_factor_secret)) {
            return false;
        }

        return (bool) $this->google2fa->verifyKey($user->two_factor_secret, $code, self::WINDOW);
    }

    /**
     * Remove 2FA entirely from the user.
     */
    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret'       => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=TwoFactorServiceTest`
Expected: PASS (all four tests).

- [ ] **Step 5: Commit**

```bash
git add Modules/Security/app/Services/TwoFactorService.php Modules/Security/tests/Feature/TwoFactorServiceTest.php
git commit -m "feat(security): TwoFactorService for TOTP enrollment lifecycle"
```

---

## Task 4: `AdminPasswordResetService`

**Files:**
- Create: `Modules/Auth/app/Services/AdminPasswordResetService.php`
- Test: `Modules/Auth/tests/Feature/AdminPasswordResetServiceTest.php`

**Interfaces:**
- Consumes: `TwoFactorService` (Task 3); `Modules\Marketing\Contracts\SmsGatewayInterface`; `password_reset_tokens` table.
- Produces:
  - `initiate(string $email): array` → one of:
    - `['method' => 'generic']` (unknown email; nothing sent)
    - `['method' => 'totp']` (enrolled; nothing sent)
    - `['method' => 'sms', 'maskedPhone' => string]` (sent OK)
    - `['method' => 'sms', 'sent' => false, 'reason' => 'cooldown'|'send_failed', 'retry_after'? => int]`
    - `['method' => 'unavailable']` (not enrolled, no phone)
  - `verify(string $email, string $code): ?string` → plaintext reset token on success, else null.

- [ ] **Step 1: Write the failing tests**

Create `Modules/Auth/tests/Feature/AdminPasswordResetServiceTest.php`:
```php
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
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=AdminPasswordResetServiceTest`
Expected: FAIL — `AdminPasswordResetService` does not exist.

- [ ] **Step 3: Implement the service**

Create `Modules/Auth/app/Services/AdminPasswordResetService.php`:
```php
<?php

namespace Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Marketing\Contracts\SmsGatewayInterface;
use Modules\Security\Services\TwoFactorService;

class AdminPasswordResetService
{
    private const OTP_TTL_MINUTES = 10;
    private const COOLDOWN_SECONDS = 60;

    public function __construct(
        private SmsGatewayInterface $sms,
        private TwoFactorService $twoFactor,
    ) {}

    /**
     * Decide how this email proves identity, and (for SMS) send the code.
     *
     * @return array{method:string, maskedPhone?:string, sent?:bool, reason?:string, retry_after?:int}
     */
    public function initiate(string $email): array
    {
        $user = User::where('email', $email)->first();

        // Never reveal whether an account exists.
        if (! $user) {
            return ['method' => 'generic'];
        }

        if ($user->hasTwoFactorEnabled()) {
            return ['method' => 'totp'];
        }

        if (empty($user->phone)) {
            return ['method' => 'unavailable'];
        }

        if (Cache::has($this->cooldownKey($email))) {
            return [
                'method'      => 'sms',
                'sent'        => false,
                'reason'      => 'cooldown',
                'retry_after' => self::COOLDOWN_SECONDS,
            ];
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Persist the hashed OTP exactly like the legacy flow (keyed by email).
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($otp), 'created_at' => now()],
        );
        // Keep the plaintext briefly so local QA can read it; never logged.
        Cache::put($this->otpCacheKey($email), $otp, now()->addMinutes(self::OTP_TTL_MINUTES));

        $store   = config('app.name');
        $message = "Your {$store} password reset code is {$otp}. Valid for " . self::OTP_TTL_MINUTES . " minutes.";

        if (! $this->sms->send($user->phone, $message)) {
            Cache::forget($this->otpCacheKey($email));
            Log::warning('Admin password reset SMS failed', ['email' => $email]);

            return ['method' => 'sms', 'sent' => false, 'reason' => 'send_failed'];
        }

        Cache::put($this->cooldownKey($email), 1, now()->addSeconds(self::COOLDOWN_SECONDS));

        return ['method' => 'sms', 'maskedPhone' => $this->maskPhone($user->phone)];
    }

    /**
     * Verify a code (TOTP if enrolled, else the SMS OTP). On success, issue a
     * one-time reset token (stored hashed) and return its plaintext value.
     */
    public function verify(string $email, string $code): ?string
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            return null;
        }

        $ok = $user->hasTwoFactorEnabled()
            ? $this->twoFactor->verify($user, $code)
            : $this->verifySmsOtp($email, $code);

        if (! $ok) {
            return null;
        }

        $token = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($token), 'created_at' => now()],
        );
        Cache::forget($this->otpCacheKey($email));

        return $token;
    }

    private function verifySmsOtp(string $email, string $code): bool
    {
        $row = DB::table('password_reset_tokens')->where('email', $email)->first();
        if (! $row) {
            return false;
        }
        if (now()->diffInMinutes($row->created_at) > self::OTP_TTL_MINUTES) {
            return false;
        }

        return Hash::check($code, $row->token);
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if (strlen($digits) <= 4) {
            return $digits;
        }

        return substr($digits, 0, 2) . str_repeat('X', strlen($digits) - 4) . substr($digits, -2);
    }

    private function otpCacheKey(string $email): string
    {
        return 'admin_pwd_otp_' . $email;
    }

    private function cooldownKey(string $email): string
    {
        return 'admin_pwd_otp_cooldown_' . $email;
    }
}
```

> Note: `verify()` for a TOTP account intentionally does not require a prior `initiate()` send — TOTP codes come from the app, not from us. The reset token is still written to `password_reset_tokens` so the existing `resetPassword` step works unchanged.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=AdminPasswordResetServiceTest`
Expected: PASS (all eight tests).

- [ ] **Step 5: Commit**

```bash
git add Modules/Auth/app/Services/AdminPasswordResetService.php Modules/Auth/tests/Feature/AdminPasswordResetServiceTest.php
git commit -m "feat(auth): AdminPasswordResetService (TOTP/SMS routing, no email)"
```

---

## Task 5: Rewire `AuthController` + routes (remove email)

**Files:**
- Modify: `Modules/Auth/app/Http/Controllers/AuthController.php` (replace `sendOtp` with `requestReset` + `verifyReset`; drop `OtpMail`/`Mail` use)
- Modify: `Modules/Auth/routes/web.php`
- Delete: `Modules/Auth/app/Mail/OtpMail.php`, `Modules/Auth/resources/views/emails/otp.blade.php`
- Test: `Modules/Auth/tests/Feature/AdminPasswordResetFlowTest.php`

**Interfaces:**
- Consumes: `AdminPasswordResetService` (Task 4).
- Produces (routes):
  - `POST /forgot-password` → `requestReset` → name `password.send-otp` (kept for back-compat).
  - `POST /forgot-password/verify` → `verifyReset` → name `password.verify`.
  - `resetPassword` / `password.update` unchanged.
- JSON contract:
  - `requestReset` → 200 `{ method, maskedPhone?, sent?, reason? }`; 422 only on validation (`email` required/email).
  - `verifyReset` → 200 `{ ok: true, token }` on success; 422 `{ ok: false, message }` on bad code.

- [ ] **Step 1: Write the failing tests**

Create `Modules/Auth/tests/Feature/AdminPasswordResetFlowTest.php`:
```php
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
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=AdminPasswordResetFlowTest`
Expected: FAIL — `password.verify` route undefined / `requestReset` missing.

- [ ] **Step 3: Replace the controller's reset actions**

In `Modules/Auth/app/Http/Controllers/AuthController.php`:

Remove the now-unused imports `use Illuminate\Support\Facades\Mail;` and `use Modules\Auth\Mail\OtpMail;`. Add `use Modules\Auth\Services\AdminPasswordResetService;`.

Delete the entire `sendOtp(SendOtpRequest $request)` method and replace it with these two methods:
```php
    /**
     * Step 1 — decide the verification method for an email and (for SMS) send
     * the code. Never sends email; never reveals whether an account exists.
     */
    public function requestReset(Request $request, AdminPasswordResetService $service)
    {
        $validated = $request->validate(['email' => ['required', 'email']]);

        return response()->json($service->initiate($validated['email']));
    }

    /**
     * Step 2 — verify the submitted code (TOTP or SMS) and return a one-time
     * reset token for the password form.
     */
    public function verifyReset(Request $request, AdminPasswordResetService $service)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code'  => ['required', 'string'],
        ]);

        $token = $service->verify($validated['email'], $validated['code']);

        if (! $token) {
            return response()->json([
                'ok'      => false,
                'message' => __('Invalid or expired code. Please try again.'),
            ], 422);
        }

        return response()->json(['ok' => true, 'token' => $token]);
    }
```
Leave `showForgotPassword`, `showResetPassword`, `resetPassword`, `login`, `logout` as-is. (You may also delete the unused `use Modules\Auth\Http\Requests\SendOtpRequest;` import if present.)

- [ ] **Step 4: Update the routes**

In `Modules/Auth/routes/web.php`, replace the two forgot-password POST lines with:
```php
    Route::post('/forgot-password', [AuthController::class, 'requestReset'])->middleware('throttle:login')->name('password.send-otp');
    Route::post('/forgot-password/verify', [AuthController::class, 'verifyReset'])->middleware('throttle:login')->name('password.verify');
```
(Keep the `GET /forgot-password`, `GET /reset-password`, and `POST /reset-password` lines unchanged.)

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --filter=AdminPasswordResetFlowTest`
Expected: PASS (all six tests). Also re-run `php artisan test --filter=AuthTest` — `test_forgot_password_page_renders` must still pass.

- [ ] **Step 6: Delete the dead email artifacts**

Confirm nothing else references them, then delete:
```bash
grep -rn "OtpMail" Modules/ app/ || echo "no references"
```
Expected: no references outside the files being deleted. Then:
```bash
git rm Modules/Auth/app/Mail/OtpMail.php Modules/Auth/resources/views/emails/otp.blade.php
```
If `grep` shows other references, stop and fix them instead of deleting.

- [ ] **Step 7: Commit**

```bash
git add Modules/Auth/app/Http/Controllers/AuthController.php Modules/Auth/routes/web.php Modules/Auth/tests/Feature/AdminPasswordResetFlowTest.php
git commit -m "feat(auth): TOTP/SMS password reset endpoints; drop email OTP"
```

---

## Task 6: Method-aware forgot-password view

**Files:**
- Modify: `Modules/Auth/resources/views/forgot-password.blade.php`
- Modify: `public/css/style.css` (any new states for this view)

**Interfaces:**
- Consumes: routes `password.send-otp` (Step 1), `password.verify` (Step 2), `password.reset` (Step 3 page).
- Produces: a 3-state AJAX page. Step 1 posts `{ email }`; branches on `response.method`:
  - `totp` → show code entry titled "Open your Authenticator app"; no "resend".
  - `sms` → show code entry titled "Enter the SMS code", subtitle shows `response.maskedPhone`; enable 60s resend.
  - `unavailable` → inline message "Password reset isn't available for this account — contact your administrator." Stay on Step 1.
  - `generic` → to avoid enumeration, behave like SMS sent (advance to the code step with a neutral "If an account exists, a code has been sent" subtitle and no masked phone). Wrong codes simply fail at Step 2.
  Step 2 posts `{ email, code }` to `password.verify`; on `{ ok:true }` redirect to `password.reset?email=…&token=…`.

This task has no unit test (view/JS). Verify via a render assertion + manual QA.

- [ ] **Step 1: Add a render assertion to the flow test**

Append to `Modules/Auth/tests/Feature/AdminPasswordResetFlowTest.php`:
```php
    public function test_forgot_password_page_renders_method_aware_markup(): void
    {
        $res = $this->get(route('password.request'));
        $res->assertOk();
        $res->assertSee('id="stepIdentify"', false);
        $res->assertSee('id="stepCode"', false);
    }
```

- [ ] **Step 2: Run it to verify it fails**

Run: `php artisan test --filter=AdminPasswordResetFlowTest::test_forgot_password_page_renders_method_aware_markup`
Expected: FAIL — current view uses `id="stepEmail"` / `id="stepOtp"`.

- [ ] **Step 3: Rewrite the view**

Replace the entire contents of `Modules/Auth/resources/views/forgot-password.blade.php` with:
```blade
@extends('core::layouts.auth')

@section('title', __("Forgot Password"))

@section('left-tagline')
Account recovery<br>made simple
@endsection

@section('left-tagline-sub')
Verify your identity with your Authenticator app or a one-time SMS code — no email required.
@endsection

@section('left-features')
        <li>
          <div class="bp-auth-feat-icon"><i class="fa-solid fa-mobile-screen-button"></i></div>
          <div class="bp-auth-feat-text">
            <strong>Authenticator App</strong>
            <span>Use your Google Authenticator code</span>
          </div>
        </li>
        <li>
          <div class="bp-auth-feat-icon"><i class="fa-solid fa-comment-sms"></i></div>
          <div class="bp-auth-feat-text">
            <strong>SMS Fallback</strong>
            <span>6-digit code to your phone</span>
          </div>
        </li>
        <li>
          <div class="bp-auth-feat-icon"><i class="fa-solid fa-shield-halved"></i></div>
          <div class="bp-auth-feat-text">
            <strong>Secure Reset</strong>
            <span>Codes expire quickly</span>
          </div>
        </li>
@endsection

@section('content')

      <!-- ── STEP 1: Identify ── -->
      <div id="stepIdentify">

        <a href="{{ route('login') }}" class="bp-auth-back">
          <i class="fa-solid fa-arrow-left"></i> Back to Login
        </a>

        <div class="bp-auth-form-header">
          <div class="bp-auth-form-title">Forgot Password?</div>
          <div class="bp-auth-form-sub">Enter your registered email. We'll verify you with your Authenticator app, or text a code to your phone.</div>
        </div>

        <div class="bp-auth-alert bp-auth-alert-error d-none" id="identifyError">
          <i class="fa-solid fa-circle-exclamation bp-auth-alert-icon"></i>
          <span id="identifyErrorMsg"></span>
        </div>

        <form id="identifyForm" novalidate>
          @csrf
          <div class="bp-af">
            <label for="forgotEmail">Email Address</label>
            <div class="bp-af-wrap">
              <i class="fa-solid fa-envelope af-icon"></i>
              <input type="email" id="forgotEmail" name="email" value="{{ old('email') }}" placeholder="admin@bizpospro.com" autocomplete="username" required>
            </div>
          </div>

          <button type="submit" class="bp-auth-submit" id="identifyBtn">
            <i class="fa-solid fa-arrow-right-to-bracket"></i>
            Continue
          </button>
        </form>

        <div class="bp-auth-version">Remembered your password? <a href="{{ route('login') }}" class="bp-auth-link">Sign in</a></div>
      </div>

      <!-- ── STEP 2: Code ── -->
      <div id="stepCode" class="d-none">

        <a href="#" class="bp-auth-back" id="backToIdentify">
          <i class="fa-solid fa-arrow-left"></i> Change Email
        </a>

        <div class="bp-auth-form-header">
          <div class="bp-auth-form-title" id="codeTitle">Enter Code</div>
          <div class="bp-auth-form-sub" id="codeSub"></div>
        </div>

        <div class="bp-auth-alert bp-auth-alert-error d-none" id="codeError">
          <i class="fa-solid fa-circle-exclamation bp-auth-alert-icon"></i>
          <span id="codeErrorMsg"></span>
        </div>

        <form id="codeForm">
          @csrf
          <div class="bp-otp-row" id="otpWrap">
            <input class="bp-otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="0">
            <input class="bp-otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="1">
            <input class="bp-otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="2">
            <input class="bp-otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="3">
            <input class="bp-otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="4">
            <input class="bp-otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="5">
          </div>

          <button type="button" class="bp-auth-submit" id="verifyBtn">
            <i class="fa-solid fa-shield-halved"></i>
            Verify &amp; Continue
          </button>
        </form>

        <div class="bp-auth-resend d-none" id="resendWrap">
          Didn't get the code?&nbsp;
          <button type="button" class="bp-auth-resend-btn" id="resendBtn" disabled>
            Resend (<span id="resendTimer">60</span>s)
          </button>
        </div>
      </div>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
  var SEND_URL   = '{{ route("password.send-otp") }}';
  var VERIFY_URL = '{{ route("password.verify") }}';
  var RESET_URL  = '{{ route("password.reset") }}';
  var currentEmail = '';
  var allowResend  = false;

  var $boxes = $('.bp-otp-box');

  function showErr($box, $msg, text) { $msg.text(text); $box.removeClass('d-none'); }

  /* ── Step 1: Identify ── */
  $('#identifyForm').on('submit', function (e) {
    e.preventDefault();
    var email = $('#forgotEmail').val().trim();
    if (!email) { showErr($('#identifyError'), $('#identifyErrorMsg'), 'Please enter your email address.'); return; }

    var $btn = $('#identifyBtn');
    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Checking…');

    $.ajax({
      url: SEND_URL, method: 'POST',
      data: { _token: '{{ csrf_token() }}', email: email },
      success: function (res) {
        $btn.prop('disabled', false).html('<i class="fa-solid fa-arrow-right-to-bracket"></i> Continue');
        if (res.method === 'unavailable') {
          showErr($('#identifyError'), $('#identifyErrorMsg'),
            "Password reset isn't available for this account — please contact your administrator.");
          return;
        }
        currentEmail = email;
        goToCodeStep(res);
      },
      error: function (xhr) {
        $btn.prop('disabled', false).html('<i class="fa-solid fa-arrow-right-to-bracket"></i> Continue');
        var msg = 'Something went wrong. Please try again.';
        if (xhr.responseJSON && xhr.responseJSON.message) { msg = xhr.responseJSON.message; }
        showErr($('#identifyError'), $('#identifyErrorMsg'), msg);
      }
    });
  });
  $('#forgotEmail').on('input', function () { $('#identifyError').addClass('d-none'); });

  function goToCodeStep(res) {
    if (res.method === 'totp') {
      $('#codeTitle').text('Open your Authenticator app');
      $('#codeSub').text('Enter the current 6-digit code from Google Authenticator.');
      $('#resendWrap').addClass('d-none');
      allowResend = false;
    } else { /* sms or generic */
      $('#codeTitle').text('Enter the SMS code');
      if (res.maskedPhone) {
        $('#codeSub').text('We sent a 6-digit code to ' + res.maskedPhone + '. It expires in 10 minutes.');
      } else {
        $('#codeSub').text('If an account exists, a 6-digit code has been sent. It expires in 10 minutes.');
      }
      $('#resendWrap').removeClass('d-none');
      allowResend = true;
      startCountdown();
    }
    $('#stepIdentify').addClass('d-none');
    $('#stepCode').removeClass('d-none');
    $boxes.val('').first().focus();
  }

  /* ── Back ── */
  $('#backToIdentify').on('click', function (e) {
    e.preventDefault();
    $('#stepCode').addClass('d-none');
    $('#stepIdentify').removeClass('d-none');
  });

  /* ── OTP box behaviour ── */
  $boxes.on('input', function () {
    var v = $(this).val().replace(/\D/g, ''); $(this).val(v);
    if (v) { var n = +$(this).data('index') + 1; if (n < 6) $boxes.eq(n).focus(); }
    $('#codeError').addClass('d-none');
  });
  $boxes.on('keydown', function (e) {
    if (e.key === 'Backspace' && !$(this).val()) {
      var p = +$(this).data('index') - 1; if (p >= 0) $boxes.eq(p).focus().val('');
    }
  });
  $boxes.on('paste', function (e) {
    e.preventDefault();
    var t = (e.originalEvent.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
    t.split('').forEach(function (ch, i) { $boxes.eq(i).val(ch); });
    $boxes.eq(Math.min(t.length, 5)).focus();
  });

  /* ── Step 2: Verify ── */
  $('#verifyBtn').on('click', function () {
    var code = $boxes.map(function () { return $(this).val(); }).get().join('');
    if (code.length < 6) { showErr($('#codeError'), $('#codeErrorMsg'), 'Please enter the complete 6-digit code.'); return; }

    var $btn = $(this);
    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Verifying…');

    $.ajax({
      url: VERIFY_URL, method: 'POST',
      data: { _token: '{{ csrf_token() }}', email: currentEmail, code: code },
      success: function (res) {
        window.location.href = RESET_URL + '?email=' + encodeURIComponent(currentEmail) + '&token=' + (res.token || '');
      },
      error: function (xhr) {
        var msg = 'Invalid or expired code. Please try again.';
        if (xhr.responseJSON && xhr.responseJSON.message) { msg = xhr.responseJSON.message; }
        showErr($('#codeError'), $('#codeErrorMsg'), msg);
        $boxes.val('').first().focus();
        $btn.prop('disabled', false).html('<i class="fa-solid fa-shield-halved"></i> Verify &amp; Continue');
      }
    });
  });

  /* ── Resend (SMS only) ── */
  var timer;
  function startCountdown() {
    var s = 60;
    $('#resendBtn').prop('disabled', true).html('Resend (<span id="resendTimer">' + s + '</span>s)');
    clearInterval(timer);
    timer = setInterval(function () {
      s--; $('#resendTimer').text(s);
      if (s <= 0) { clearInterval(timer); $('#resendBtn').prop('disabled', false).text('Resend code'); }
    }, 1000);
  }
  $('#resendBtn').on('click', function () {
    if (!allowResend) return;
    $boxes.val('').first().focus(); $('#codeError').addClass('d-none');
    $.ajax({
      url: SEND_URL, method: 'POST',
      data: { _token: '{{ csrf_token() }}', email: currentEmail },
      success: function () { startCountdown(); },
      error: function () { showErr($('#codeError'), $('#codeErrorMsg'), 'Failed to resend. Please try again.'); }
    });
  });
});
</script>
@endpush
```

- [ ] **Step 4: Add any needed CSS**

The view reuses existing `bp-auth-*` and `bp-otp-*` classes (already styled, with dark-mode rules). No new classes are required. If during manual QA you find an unstyled element, add a `bp-`-prefixed rule at the bottom of `public/css/style.css` with a matching `[data-theme="dark"]` override — do not use inline styles.

- [ ] **Step 5: Run the render test**

Run: `php artisan test --filter=AdminPasswordResetFlowTest`
Expected: PASS (including the new render test).

- [ ] **Step 6: Manual QA**

Start the server (`php artisan serve`), open `/forgot-password`. With an enrolled user → "Open your Authenticator app", no resend. With an un-enrolled user that has a phone → "Enter the SMS code" + masked phone + 60s resend (read the code from `cache` / `password_reset_tokens` via tinker if the gateway isn't live). Check dark mode and 768/992/1200 widths.

- [ ] **Step 7: Commit**

```bash
git add Modules/Auth/resources/views/forgot-password.blade.php public/css/style.css Modules/Auth/tests/Feature/AdminPasswordResetFlowTest.php
git commit -m "feat(auth): method-aware forgot-password page (Authenticator/SMS)"
```

---

## Task 7: Self-service enrollment (controller + routes + view + menu)

**Files:**
- Create: `Modules/Security/app/Http/Controllers/TwoFactorController.php`
- Create: `Modules/Security/resources/views/users/two-factor.blade.php`
- Modify: `Modules/Security/routes/web.php`
- Modify: `Modules/Core/resources/views/partials/header.blade.php:126` (user dropdown)
- Modify: `public/css/style.css` (QR/section states if needed)
- Test: `Modules/Security/tests/Feature/TwoFactorEnrollmentTest.php`

**Interfaces:**
- Consumes: `TwoFactorService` (Task 3).
- Produces (routes, all in the `security.` `auth` group; act on `auth()->user()` only — no `users.edit` gate):
  - `GET /security/two-factor` → `show` → name `security.two-factor`.
  - `POST /security/two-factor/enable` → `enable` → name `security.two-factor.enable` → JSON `{ secret, qr }`.
  - `POST /security/two-factor/confirm` → `confirm` → name `security.two-factor.confirm` → JSON `{ ok }` (422 on bad code).
  - `DELETE /security/two-factor` → `disable` → name `security.two-factor.disable` → redirect; requires `current_password`.

- [ ] **Step 1: Write the failing tests**

Create `Modules/Security/tests/Feature/TwoFactorEnrollmentTest.php`:
```php
<?php

namespace Modules\Security\Tests\Feature;

use App\Models\User;
use Modules\Security\Services\TwoFactorService;
use PragmaRX\Google2FAQRCode\Google2FA;
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
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=TwoFactorEnrollmentTest`
Expected: FAIL — routes/controller missing.

- [ ] **Step 3: Implement the controller**

Create `Modules/Security/app/Http/Controllers/TwoFactorController.php`:
```php
<?php

namespace Modules\Security\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Security\Services\TwoFactorService;

class TwoFactorController extends Controller
{
    public function __construct(private TwoFactorService $twoFactor) {}

    public function show()
    {
        return view('security::users.two-factor', ['user' => auth()->user()]);
    }

    public function enable()
    {
        return response()->json($this->twoFactor->generateSecret(auth()->user()));
    }

    public function confirm(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);

        if (! $this->twoFactor->confirm(auth()->user(), $request->input('code'))) {
            return response()->json([
                'ok'      => false,
                'message' => __('That code is incorrect. Please try again.'),
            ], 422);
        }

        return response()->json(['ok' => true]);
    }

    public function disable(Request $request)
    {
        $request->validate(
            ['current_password' => ['required', 'current_password']],
            ['current_password.current_password' => __('The password is incorrect.')]
        );

        $this->twoFactor->disable(auth()->user());

        return redirect()->route('security.two-factor')
            ->with('success', __('Two-Factor Authentication disabled.'));
    }
}
```

- [ ] **Step 4: Add the routes**

In `Modules/Security/routes/web.php`, add `use Modules\Security\Http\Controllers\TwoFactorController;` at the top, and inside the `security.` group (next to the self-service profile/password routes) add:
```php
    // Self-service Two-Factor (Google Authenticator) — acts on the current user only.
    Route::get('/two-factor',           [TwoFactorController::class, 'show'])->name('two-factor');
    Route::post('/two-factor/enable',   [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::post('/two-factor/confirm',  [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
    Route::delete('/two-factor',        [TwoFactorController::class, 'disable'])->name('two-factor.disable');
```

- [ ] **Step 5: Create the enrollment view**

Create `Modules/Security/resources/views/users/two-factor.blade.php`:
```blade
@extends('core::layouts.master')

@section('title', __('Two-Factor Authentication'))
@section('page-title', __('Two-Factor Authentication'))
@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>{{ __('Two-Factor Authentication') }}</span>
@endsection

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="bp-card">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-mobile-screen-button me-2"></i>{{ __('Authenticator App') }}</h5>
      </div>
      <div class="bp-card-body">

        @if($user->hasTwoFactorEnabled())
          <div class="bp-2fa-status bp-2fa-on">
            <i class="fa-solid fa-circle-check me-1"></i>{{ __('Two-Factor Authentication is enabled.') }}
          </div>
          <p class="fs-13 text-muted mt-3">{{ __('You will use your Authenticator code to reset your password. To turn this off, confirm your password below.') }}</p>

          <form method="POST" action="{{ route('security.two-factor.disable') }}" class="mt-3">
            @csrf
            @method('DELETE')
            <div class="row g-3 align-items-end">
              <div class="col-md-8">
                <label class="bp-form-label">{{ __('Current Password') }}</label>
                <input type="password" name="current_password" class="bp-form-control" autocomplete="current-password" required>
                @error('current_password')<div class="bp-af-error">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-4">
                <button type="submit" class="bp-btn bp-btn-danger w-100"><i class="fa-solid fa-shield-halved me-1"></i>{{ __('Disable') }}</button>
              </div>
            </div>
          </form>
        @else
          <p class="fs-13 text-muted">{{ __('Protect password resets with Google Authenticator. Scan the QR code with your app, then enter the 6-digit code to confirm.') }}</p>

          <button type="button" class="bp-btn bp-btn-primary" id="enable2faBtn"><i class="fa-solid fa-qrcode me-1"></i>{{ __('Set up Authenticator') }}</button>

          <div id="setup2fa" class="d-none mt-4">
            <div class="bp-2fa-qr" id="qrHolder"></div>
            <p class="fs-12 text-muted mt-2">{{ __('Or enter this key manually:') }} <code id="manualKey"></code></p>

            <div class="bp-auth-alert bp-auth-alert-error d-none" id="confirm2faError">
              <i class="fa-solid fa-circle-exclamation bp-auth-alert-icon"></i>
              <span id="confirm2faErrorMsg"></span>
            </div>

            <div class="row g-3 align-items-end mt-1">
              <div class="col-md-8">
                <label class="bp-form-label">{{ __('6-Digit Code') }}</label>
                <input type="text" id="confirm2faCode" class="bp-form-control" inputmode="numeric" maxlength="6" placeholder="123456">
              </div>
              <div class="col-md-4">
                <button type="button" class="bp-btn bp-btn-success w-100" id="confirm2faBtn"><i class="fa-solid fa-check me-1"></i>{{ __('Confirm') }}</button>
              </div>
            </div>
          </div>
        @endif

      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
'use strict';
$(function () {
  var ENABLE_URL  = '{{ route("security.two-factor.enable") }}';
  var CONFIRM_URL = '{{ route("security.two-factor.confirm") }}';

  $('#enable2faBtn').on('click', function () {
    var $btn = $(this);
    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> {{ __("Loading…") }}');
    $.ajax({
      url: ENABLE_URL, method: 'POST',
      data: { _token: '{{ csrf_token() }}' },
      success: function (res) {
        // res.qr is a trusted, server-generated inline SVG/data-URI (no user input).
        $('#qrHolder').html(res.qr);
        $('#manualKey').text(res.secret);
        $('#setup2fa').removeClass('d-none');
        $btn.addClass('d-none');
      },
      error: function () {
        $btn.prop('disabled', false).html('<i class="fa-solid fa-qrcode me-1"></i> {{ __("Set up Authenticator") }}');
      }
    });
  });

  $('#confirm2faBtn').on('click', function () {
    var code = $('#confirm2faCode').val().replace(/\D/g, '');
    if (code.length < 6) {
      $('#confirm2faErrorMsg').text('{{ __("Enter the 6-digit code.") }}');
      $('#confirm2faError').removeClass('d-none');
      return;
    }
    var $btn = $(this);
    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');
    $.ajax({
      url: CONFIRM_URL, method: 'POST',
      data: { _token: '{{ csrf_token() }}', code: code },
      success: function () { window.location.reload(); },
      error: function (xhr) {
        var msg = '{{ __("That code is incorrect. Please try again.") }}';
        if (xhr.responseJSON && xhr.responseJSON.message) { msg = xhr.responseJSON.message; }
        $('#confirm2faErrorMsg').text(msg);
        $('#confirm2faError').removeClass('d-none');
        $btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> {{ __("Confirm") }}');
      }
    });
  });
});
</script>
@endpush
```

> The master layout include path (`core::layouts.master`) and the page-section names (`page-title`, `breadcrumb`, `content`) must match what the other Security views use. Open `Modules/Security/resources/views/users/change-password.blade.php` and copy its exact `@extends`/`@section` header if it differs from the above.

- [ ] **Step 6: Add the CSS**

Append to the bottom of `public/css/style.css`:
```css
/* ===== Two-Factor Authentication ===== */
.bp-2fa-status { display:inline-flex; align-items:center; padding:8px 14px; border-radius:8px; font-size:13px; font-weight:600; }
.bp-2fa-on { color:var(--bp-success); background:rgba(30,132,73,.10); }
.bp-2fa-qr { display:inline-block; padding:12px; background:#fff; border:1px solid var(--bp-border, #E0E0E0); border-radius:10px; }
.bp-2fa-qr svg { display:block; width:180px; height:180px; }
[data-theme="dark"] .bp-2fa-on { color:#7CC8A0; background:rgba(30,132,73,.18); }
[data-theme="dark"] .bp-2fa-qr { background:#fff; border-color:var(--bp-border-dark, #3A3F4B); }
```
(The QR stays on a white background in dark mode so authenticator apps can read it.)

- [ ] **Step 7: Add the menu link**

In `Modules/Core/resources/views/partials/header.blade.php`, immediately after the "Change Password" `<li>` (line ~126), add:
```blade
        <li><a class="dropdown-item" href="{{ route('security.two-factor') }}"><i class="fa-solid fa-mobile-screen-button"></i> {{ __('Two-Factor Authentication') }}</a></li>
```

- [ ] **Step 8: Run the tests to verify they pass**

Run: `php artisan test --filter=TwoFactorEnrollmentTest`
Expected: PASS (all five tests).

- [ ] **Step 9: Manual QA**

Log in, open the user menu → "Two-Factor Authentication". Click "Set up Authenticator", scan the QR with Google Authenticator on a real phone, enter the code → page shows "enabled". Verify "Disable" requires the correct password. Check dark mode + responsive widths.

- [ ] **Step 10: Commit**

```bash
git add Modules/Security/app/Http/Controllers/TwoFactorController.php Modules/Security/resources/views/users/two-factor.blade.php Modules/Security/routes/web.php Modules/Core/resources/views/partials/header.blade.php public/css/style.css Modules/Security/tests/Feature/TwoFactorEnrollmentTest.php
git commit -m "feat(security): self-service Google Authenticator enrollment"
```

---

## Task 8: Admin "reset/clear 2FA for others"

**Files:**
- Modify: `Modules/Security/app/Http/Controllers/UserController.php` (add `resetTwoFactor`)
- Modify: `Modules/Security/routes/web.php` (add `users.reset-two-factor`)
- Modify: `Modules/Security/resources/views/users/create.blade.php` (control shown when editing an enrolled user)
- Test: `Modules/Security/tests/Feature/TwoFactorEnrollmentTest.php` (add admin cases)

**Interfaces:**
- Consumes: `TwoFactorService::disable` (Task 3); existing `bpAuthorize('users.edit')` + `guardSuperAdmin()`.
- Produces: `PATCH /security/users/{user}/reset-two-factor` → `resetTwoFactor` → name `security.users.reset-two-factor`. Clears the target user's 2FA; 404 on a super-admin target; redirects back with a success flash.

- [ ] **Step 1: Write the failing tests**

Add to `Modules/Security/tests/Feature/TwoFactorEnrollmentTest.php` (add imports `use Spatie\Permission\Models\Role;` and `use Spatie\Permission\Models\Permission;`):
```php
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
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=TwoFactorEnrollmentTest::test_admin_can_reset_another_users_two_factor`
Expected: FAIL — route/method missing.

- [ ] **Step 3: Add the controller method**

In `Modules/Security/app/Http/Controllers/UserController.php`, add `use Modules\Security\Services\TwoFactorService;` at the top, and add this method (e.g. after `toggleStatus`):
```php
    /**
     * Clear a user's Two-Factor enrollment (e.g. they lost their device).
     * The user falls back to SMS reset and may re-enroll themselves.
     */
    public function resetTwoFactor($id, TwoFactorService $twoFactor)
    {
        bpAuthorize('users.edit');
        $user = User::findOrFail($id);
        $this->guardSuperAdmin($user);

        $twoFactor->disable($user);

        return redirect()->route('security.users.edit', $user->id)
            ->with('success', __('Two-Factor Authentication has been reset for this user.'));
    }
```

- [ ] **Step 4: Add the route**

In `Modules/Security/routes/web.php`, in the Users block (after the `toggle-status` line) add:
```php
    Route::patch('/users/{user}/reset-two-factor', [UserController::class, 'resetTwoFactor'])->name('users.reset-two-factor');
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --filter=TwoFactorEnrollmentTest`
Expected: PASS (all seven tests now).

- [ ] **Step 6: Add the UI control**

In `Modules/Security/resources/views/users/create.blade.php`, inside the edit-only area (the view is reused for edit when `$user` is set), add a block that renders only when editing a user who has 2FA enabled. Place it near the other account controls:
```blade
@isset($user)
  @if($user->hasTwoFactorEnabled())
  <div class="bp-card mt-3">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-mobile-screen-button me-2"></i>{{ __('Two-Factor Authentication') }}</h5>
    </div>
    <div class="bp-card-body">
      <p class="fs-13 text-muted">{{ __('This user has Authenticator enabled. If they lost their device, reset it so they can recover via SMS and re-enroll.') }}</p>
      <form method="POST" action="{{ route('security.users.reset-two-factor', $user->id) }}"
            onsubmit="return confirm('{{ __('Reset Two-Factor for this user?') }}');">
        @csrf
        @method('PATCH')
        <button type="submit" class="bp-btn bp-btn-danger bp-btn-sm"><i class="fa-solid fa-rotate-left me-1"></i>{{ __('Reset Two-Factor') }}</button>
      </form>
    </div>
  </div>
  @endif
@endisset
```
> Confirm the surrounding form structure: this block must NOT be nested inside the main `<form>` for the user edit (it is its own form). If the edit form wraps the whole body, place this block after the closing `</form>` of the edit form.

- [ ] **Step 7: Manual QA**

As an admin with `users.edit`, edit a user who has 2FA enabled → the "Reset Two-Factor" card appears → click → confirm → user's 2FA cleared, success flash shown. Editing a user without 2FA shows no card.

- [ ] **Step 8: Commit**

```bash
git add Modules/Security/app/Http/Controllers/UserController.php Modules/Security/routes/web.php Modules/Security/resources/views/users/create.blade.php Modules/Security/tests/Feature/TwoFactorEnrollmentTest.php
git commit -m "feat(security): admin can reset a user's Two-Factor enrollment"
```

---

## Task 9: Full regression sweep

**Files:** none (verification only)

- [ ] **Step 1: Run the full auth + security suites**

Run:
```bash
php artisan test --filter=AuthTest
php artisan test --filter=AdminPasswordResetServiceTest
php artisan test --filter=AdminPasswordResetFlowTest
php artisan test --filter=TwoFactorServiceTest
php artisan test --filter=TwoFactorEnrollmentTest
```
Expected: all PASS.

- [ ] **Step 2: Confirm email is gone from the reset path**

Run:
```bash
grep -rn "OtpMail\|Mail::to" Modules/Auth/ || echo "no email in Auth module"
```
Expected: `no email in Auth module` (the storefront customer flow in the Ecommerce module is separate and untouched).

- [ ] **Step 3: Run the entire test suite once**

Run: `php artisan test`
Expected: green (or only failures that pre-existed and are unrelated to this work — note any such failures rather than "fixing" them blindly).

- [ ] **Step 4: Commit (if any incidental fixes were needed)**

```bash
git add -A
git commit -m "test(security): regression sweep for 2FA password reset"
```

---

## Self-Review (completed during authoring)

- **Spec coverage:** data model (Task 2), `TwoFactorService` (Task 3), `AdminPasswordResetService` incl. all `initiate` branches + `verify` (Task 4), controller/routes + email removal (Task 5), method-aware view incl. enumeration handling & masked phone (Task 6), self-service enrollment + menu (Task 7), admin reset-for-others + `guardSuperAdmin` (Task 8), lockout escape hatch is the pre-existing `security.users.update` (documented, no task needed), testing across all tasks + sweep (Task 9). Out-of-scope items (login 2FA, recovery codes, mandatory enrollment, storefront) are not implemented. ✔
- **Placeholder scan:** none — every code/test step contains full code and exact commands. ✔
- **Type consistency:** `generateSecret → {secret, qr}`, `confirm/verify → bool`, `disable → void`, `initiate → array{method,...}`, `verify(email,code) → ?string`, route names (`password.send-otp`, `password.verify`, `security.two-factor*`, `security.users.reset-two-factor`) are used identically across tasks. Cache key `admin_pwd_otp_{email}` matches between service and tests. ✔
