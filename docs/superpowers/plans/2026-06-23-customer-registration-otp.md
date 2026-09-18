# OTP-Gated Customer Registration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Gate storefront customer registration behind a phone SMS OTP so the existing signup fields only appear after the number is verified, cutting spam signups.

**Architecture:** A single `/register` page runs three AJAX steps (phone → OTP code → existing fields). A new `CustomerOtpService` (cache-based, sends via the existing `SmsGatewayInterface`/BulkSMSBD) handles generation/verification. Two thin JSON controller actions back the AJAX calls; the existing `register()` action gains a server-side "verified phone in session" check. An admin setting (`customer_otp_required`, default on) can disable the gate and restore today's flat form.

**Tech Stack:** Laravel 12, Blade, jQuery (storefront already loads it + global `$.ajaxSetup` CSRF), MySQL, PHPUnit (`Tests\TestCase` with `RefreshDatabase`).

## Global Constraints

- **'use strict';** at the top of every `<script>` block — no exceptions.
- **No inline CSS** (`style="..."`); runtime show/hide via jQuery `.show()/.hide()` is the only allowed dynamic exception. Static hidden state uses a CSS class.
- **No hardcoded routes** — always `{{ route('...') }}` in Blade, `route('...')` in PHP, and pass route URLs into JS via Blade `route()`.
- **XSS:** insert server messages with jQuery `.text()`, never `.html()`.
- **Phone format:** validate with `App\Rules\PhoneNumber` (single source of truth; BD pattern `01[3-9]\d{8}`). Never hardcode a phone regex.
- **Money/dates** unaffected. Currency BDT, dates `DD MMM YYYY` (not relevant here).
- **SOLID/DRY:** controllers thin (delegate to `CustomerOtpService`); reuse the existing `SmsGatewayInterface` — do not add a new SMS path.
- **Customer model:** `Modules\Ecommerce\Models\StorefrontCustomer` (`$table='customers'`, `password` cast `hashed`, `phone` is the login identifier).
- **Setting reads:** `EcommerceSetting::get(string $key, $default=null): ?string` returns a string; compare with `=== '1'`.

## File Structure

- **Create** `Modules/Ecommerce/app/Services/CustomerOtpService.php` — OTP generate/verify/cooldown + SMS dispatch.
- **Create** `Modules/Ecommerce/tests/Unit/CustomerOtpServiceTest.php` — unit tests with a fake gateway.
- **Modify** `Modules/Ecommerce/app/Http/Controllers/Storefront/CustomerAuthController.php` — add `sendRegisterOtp`, `verifyRegisterOtp`, `otpRequired()` helper; modify `showRegistrationForm` and `register`.
- **Modify** `Modules/Ecommerce/routes/storefront.php` — two new routes in the `guest:customer` group.
- **Create** `Modules/Ecommerce/tests/Feature/CustomerRegistrationOtpTest.php` — endpoint + flow tests.
- **Modify** `Modules/Ecommerce/resources/views/storefront/pages/customer/register.blade.php` — three-step markup + `@push('scripts')` JS.
- **Modify** `Modules/Ecommerce/resources/views/settings.blade.php` — `customer_otp_required` select (saved automatically by existing `settingsUpdate`).
- **Modify** `public/website/assets/css/style.css` — `.bp-hidden` + small OTP/countdown styles.

No migration and no seeder change: like `customer_auth_enabled`, the setting relies on the `?? '1'` default until an admin saves it.

---

### Task 1: `CustomerOtpService`

**Files:**
- Create: `Modules/Ecommerce/app/Services/CustomerOtpService.php`
- Test: `Modules/Ecommerce/tests/Unit/CustomerOtpServiceTest.php`

**Interfaces:**
- Consumes: `Modules\Marketing\Contracts\SmsGatewayInterface` (`send(string $number, string $message): bool`).
- Produces:
  - `__construct(SmsGatewayInterface $gateway)`
  - `normalize(string $phone): string` — digits only.
  - `send(string $phone): array` — `['ok'=>true]` or `['ok'=>false,'reason'=>'cooldown','retry_after'=>int]` or `['ok'=>false,'reason'=>'send_failed']`.
  - `verify(string $phone, string $code): bool`
  - Cache keys: `customer_reg_otp_{normalized}`, `customer_reg_otp_attempts_{normalized}`, `customer_reg_otp_cooldown_{normalized}`.

- [ ] **Step 1: Write the failing test**

Create `Modules/Ecommerce/tests/Unit/CustomerOtpServiceTest.php`:

```php
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
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Unit/CustomerOtpServiceTest.php`
Expected: FAIL — `Class "Modules\Ecommerce\Services\CustomerOtpService" not found`.

- [ ] **Step 3: Write minimal implementation**

Create `Modules/Ecommerce/app/Services/CustomerOtpService.php`:

```php
<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Marketing\Contracts\SmsGatewayInterface;

class CustomerOtpService
{
    private const TTL_MINUTES = 10;
    private const COOLDOWN_SECONDS = 60;
    private const MAX_ATTEMPTS = 5;

    public function __construct(private SmsGatewayInterface $gateway) {}

    /**
     * Reduce a phone to digits only so one number maps to one cache key.
     */
    public function normalize(string $phone): string
    {
        return preg_replace('/\D/', '', trim($phone)) ?? '';
    }

    /**
     * Generate, store, and SMS a 6-digit OTP. Honors a per-phone resend cooldown.
     *
     * @return array{ok:bool,reason?:string,retry_after?:int}
     */
    public function send(string $phone): array
    {
        $key = $this->normalize($phone);

        if (Cache::has($this->cooldownKey($key))) {
            return ['ok' => false, 'reason' => 'cooldown', 'retry_after' => self::COOLDOWN_SECONDS];
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put($this->otpKey($key), $otp, now()->addMinutes(self::TTL_MINUTES));
        Cache::put($this->attemptsKey($key), 0, now()->addMinutes(self::TTL_MINUTES));

        $store = config('app.name');
        $message = "Your {$store} verification code is {$otp}. Valid for " . self::TTL_MINUTES . " minutes.";

        if (! $this->gateway->send($phone, $message)) {
            // Failure: drop the code and skip the cooldown so the user can retry.
            Cache::forget($this->otpKey($key));
            Log::warning('Customer registration OTP send failed', ['phone' => $key]);

            return ['ok' => false, 'reason' => 'send_failed'];
        }

        Cache::put($this->cooldownKey($key), 1, now()->addSeconds(self::COOLDOWN_SECONDS));

        return ['ok' => true];
    }

    /**
     * Verify a submitted code; caps attempts and consumes the code on success.
     */
    public function verify(string $phone, string $code): bool
    {
        $key = $this->normalize($phone);
        $stored = Cache::get($this->otpKey($key));

        if (! $stored) {
            return false;
        }

        $attempts = (int) Cache::get($this->attemptsKey($key), 0) + 1;
        Cache::put($this->attemptsKey($key), $attempts, now()->addMinutes(self::TTL_MINUTES));

        if ($attempts > self::MAX_ATTEMPTS) {
            Cache::forget($this->otpKey($key));

            return false;
        }

        if (hash_equals((string) $stored, $code)) {
            Cache::forget($this->otpKey($key));
            Cache::forget($this->attemptsKey($key));

            return true;
        }

        return false;
    }

    private function otpKey(string $key): string
    {
        return "customer_reg_otp_{$key}";
    }

    private function attemptsKey(string $key): string
    {
        return "customer_reg_otp_attempts_{$key}";
    }

    private function cooldownKey(string $key): string
    {
        return "customer_reg_otp_cooldown_{$key}";
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Unit/CustomerOtpServiceTest.php`
Expected: PASS (7 tests).

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Services/CustomerOtpService.php Modules/Ecommerce/tests/Unit/CustomerOtpServiceTest.php
git commit -m "feat(ecommerce): add CustomerOtpService for phone registration OTP

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 2: Controller actions, routes, and `register()` gate

**Files:**
- Modify: `Modules/Ecommerce/app/Http/Controllers/Storefront/CustomerAuthController.php`
- Modify: `Modules/Ecommerce/routes/storefront.php`
- Test: `Modules/Ecommerce/tests/Feature/CustomerRegistrationOtpTest.php`

**Interfaces:**
- Consumes: `CustomerOtpService` (Task 1) via method injection; `App\Rules\PhoneNumber`; `EcommerceSetting`; `StorefrontCustomer`.
- Produces:
  - Routes `storefront.customer.register.send-otp` (POST `/register/send-otp`) and `storefront.customer.register.verify-otp` (POST `/register/verify-otp`).
  - `sendRegisterOtp(Request, CustomerOtpService): JsonResponse`
  - `verifyRegisterOtp(Request, CustomerOtpService): JsonResponse` — sets `session('register_verified_phone')` (normalized).
  - `register(Request, CustomerOtpService)` — rejects when session phone ≠ submitted phone (gate on); forgets the session key on success.
  - `showRegistrationForm` view data: `otpRequired` (bool), `verifiedPhone` (?string).

- [ ] **Step 1: Write the failing test**

Create `Modules/Ecommerce/tests/Feature/CustomerRegistrationOtpTest.php`:

```php
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
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/CustomerRegistrationOtpTest.php`
Expected: FAIL — route `storefront.customer.register.send-otp` not defined (and/or method missing).

- [ ] **Step 3a: Add the routes**

In `Modules/Ecommerce/routes/storefront.php`, inside the `guest:customer` group, immediately after the existing `storefront.customer.register.post` line, add:

```php
    Route::post('/register/send-otp', [CustomerAuthController::class, 'sendRegisterOtp'])
        ->middleware('throttle:login')->name('storefront.customer.register.send-otp');
    Route::post('/register/verify-otp', [CustomerAuthController::class, 'verifyRegisterOtp'])
        ->middleware('throttle:login')->name('storefront.customer.register.verify-otp');
```

- [ ] **Step 3b: Add imports + helper to the controller**

In `CustomerAuthController.php`, add these `use` statements near the existing imports:

```php
use App\Rules\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Services\CustomerOtpService;
```

Add this private helper (next to `ensureAuthEnabled`):

```php
    /**
     * Whether phone OTP verification is required before registration.
     */
    private function otpRequired(): bool
    {
        return EcommerceSetting::get('customer_otp_required', '1') === '1';
    }
```

- [ ] **Step 3c: Add the two JSON actions**

In `CustomerAuthController.php`, add after the existing `register()` method:

```php
    /**
     * Step 1 (AJAX): validate phone, block duplicates, send an SMS OTP.
     */
    public function sendRegisterOtp(Request $request, CustomerOtpService $otp): JsonResponse
    {
        if (EcommerceSetting::get('customer_auth_enabled', '1') !== '1' || ! $this->otpRequired()) {
            return response()->json(['ok' => false, 'message' => __('Registration is unavailable.')]);
        }

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30', new PhoneNumber],
        ]);

        if (StorefrontCustomer::where('phone', $validated['phone'])->exists()) {
            return response()->json([
                'ok'      => false,
                'code'    => 'exists',
                'message' => __('This number is already registered — please sign in.'),
            ]);
        }

        $result = $otp->send($validated['phone']);

        if (($result['ok'] ?? false) === true) {
            return response()->json(['ok' => true, 'message' => __('A verification code has been sent to your phone.')]);
        }

        $messages = [
            'cooldown'    => __('Please wait a moment before requesting another code.'),
            'send_failed' => __("Couldn't send the code. Please try again."),
        ];

        return response()->json([
            'ok'      => false,
            'message' => $messages[$result['reason'] ?? ''] ?? __('Unable to send code.'),
        ]);
    }

    /**
     * Step 2 (AJAX): verify the OTP and remember the verified phone in session.
     */
    public function verifyRegisterOtp(Request $request, CustomerOtpService $otp): JsonResponse
    {
        if (EcommerceSetting::get('customer_auth_enabled', '1') !== '1' || ! $this->otpRequired()) {
            return response()->json(['ok' => false, 'message' => __('Registration is unavailable.')]);
        }

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30', new PhoneNumber],
            'code'  => ['required', 'digits:6'],
        ]);

        if (! $otp->verify($validated['phone'], $validated['code'])) {
            return response()->json(['ok' => false, 'message' => __('Invalid or expired code.')]);
        }

        session(['register_verified_phone' => $otp->normalize($validated['phone'])]);

        return response()->json(['ok' => true]);
    }
```

- [ ] **Step 3d: Gate `register()` and update `showRegistrationForm()`**

Change the `register()` signature to inject the service:

```php
    public function register(Request $request, CustomerOtpService $otp): RedirectResponse
```

Immediately **after** the existing `$validated = $request->validate([...]);` block in `register()`, insert:

```php
        if ($this->otpRequired() && session('register_verified_phone') !== $otp->normalize($validated['phone'])) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['phone' => __('Please verify your phone number before registering.')]);
        }
```

**After** the existing `$request->session()->regenerate();` line in `register()` (just before the flash/redirect), insert:

```php
        session()->forget('register_verified_phone');
```

Replace the body of `showRegistrationForm()` return with:

```php
        return view('ecommerce::storefront.pages.customer.register', [
            'otpRequired'   => $this->otpRequired(),
            'verifiedPhone' => session('register_verified_phone'),
            'seo'           => \Modules\Ecommerce\Support\Seo::make()->robots('noindex,nofollow'),
        ]);
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test Modules/Ecommerce/tests/Feature/CustomerRegistrationOtpTest.php`
Expected: PASS (7 tests).

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Http/Controllers/Storefront/CustomerAuthController.php Modules/Ecommerce/routes/storefront.php Modules/Ecommerce/tests/Feature/CustomerRegistrationOtpTest.php
git commit -m "feat(ecommerce): OTP-gate customer registration (routes + controller)

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 3: Admin setting toggle

**Files:**
- Modify: `Modules/Ecommerce/resources/views/settings.blade.php` (the `#customerAuth` card, around lines 160–182)

**Interfaces:**
- Consumes: `$settings` array from `EcommerceController@settings`; persisted by existing `settingsUpdate` (`$request->except([...])` → `updateSettings`). No controller change.
- Produces: a `customer_otp_required` select (`'1'`/`'0'`, default `'1'`).

- [ ] **Step 1: Add the select to the Customer Authentication card**

In `settings.blade.php`, inside the `.row.g-3` of the `#customerAuth` card (immediately after the closing `</div>` of the existing `col-md-6` that holds `customer_auth_enabled`), add a second column:

```blade
                            <div class="col-md-6">
                                <label class="bp-form-label">Registration OTP Verification</label>
                                <select class="bp-form-select w-100" name="customer_otp_required">
                                    <option value="1"
                                        {{ old('customer_otp_required', $settings['customer_otp_required'] ?? '1') == '1' ? 'selected' : '' }}>
                                        Enabled — Require phone OTP before registration</option>
                                    <option value="0"
                                        {{ old('customer_otp_required', $settings['customer_otp_required'] ?? '1') == '0' ? 'selected' : '' }}>
                                        Disabled — Allow registration without OTP</option>
                                </select>
                                <div class="fs-11 text-muted mt-1">When enabled, new customers must verify their
                                    phone via an SMS code before the signup form appears. Reduces spam signups.</div>
                            </div>
```

- [ ] **Step 2: Verify the settings page renders**

Run (dev server must be up per CLAUDE.md §20; admin session via the curl login flow):
```bash
curl -s -b /tmp/bz.txt "http://localhost:8000/admin/ecommerce/settings" -o /dev/null -w "%{http_code}\n"
```
Expected: `200`. Manually confirm the new "Registration OTP Verification" dropdown appears in the Customer Authentication card and that saving with it set to Disabled persists (re-open settings → still Disabled).

- [ ] **Step 3: Commit**

```bash
git add Modules/Ecommerce/resources/views/settings.blade.php
git commit -m "feat(ecommerce): admin toggle for registration OTP requirement

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 4: Storefront register page — three AJAX steps

**Files:**
- Modify: `Modules/Ecommerce/resources/views/storefront/pages/customer/register.blade.php`
- Modify: `public/website/assets/css/style.css`
- Test: extend `Modules/Ecommerce/tests/Feature/CustomerRegistrationOtpTest.php`

**Interfaces:**
- Consumes: view data `otpRequired` (bool), `verifiedPhone` (?string) from Task 2; routes `storefront.customer.register.send-otp`, `storefront.customer.register.verify-otp`, `storefront.customer.register.post`.
- Produces: page markup with `#regStep1`/`#regStep2`/`#regStep3` (when `otpRequired`) and a `@push('scripts')` block driving the AJAX flow.

- [ ] **Step 1: Write the failing test (page render markers)**

Add these two methods to `Modules/Ecommerce/tests/Feature/CustomerRegistrationOtpTest.php`:

```php
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
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/CustomerRegistrationOtpTest.php --filter register_page`
Expected: FAIL — markers not present (view not updated yet).

- [ ] **Step 3a: Replace the register form markup**

Replace the entire `<form ...> ... </form>` block in `register.blade.php` (currently lines ~22–117) with the following. The "rest of fields" (name, email, division, district, address, password) are rendered once inside `#regStep3`; the phone input lives in `#regStep1` so it always posts with the form.

```blade
                        <form action="{{ route('storefront.customer.register.post') }}" method="POST" id="registerForm">
                            @csrf

                            {{-- STEP 1: phone (OTP gate) --}}
                            <div id="regStep1" class="reg-step {{ ($otpRequired && $verifiedPhone) ? 'bp-hidden' : '' }}">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="single_input">
                                            <label>Phone Number *</label>
                                            <input type="tel" name="phone" id="regPhone" placeholder="01XXXXXXXXX"
                                                value="{{ old('phone', $verifiedPhone) }}" required
                                                @if($otpRequired && $verifiedPhone) readonly @endif
                                                class="@error('phone') is-invalid @enderror">
                                            @error('phone')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    @if($otpRequired)
                                        <div class="col-md-12">
                                            <div id="regSendMsg" class="reg-otp-msg"></div>
                                            <button type="button" id="regContinueBtn" class="common_btn">
                                                Continue <i class="fas fa-long-arrow-right"></i>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if($otpRequired)
                                {{-- STEP 2: OTP code --}}
                                <div id="regStep2" class="reg-step bp-hidden">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="single_input">
                                                <label>Verification Code *</label>
                                                <input type="text" id="regCode" inputmode="numeric" maxlength="6"
                                                    placeholder="6-digit code" class="reg-otp-input">
                                            </div>
                                            <div id="regVerifyMsg" class="reg-otp-msg"></div>
                                        </div>
                                        <div class="col-md-12 d-flex align-items-center gap-3 flex-wrap">
                                            <button type="button" id="regVerifyBtn" class="common_btn">
                                                Verify <i class="fas fa-check"></i>
                                            </button>
                                            <a href="#" id="regResend" class="reg-otp-link">Resend code</a>
                                            <span id="regCountdown" class="reg-otp-muted"></span>
                                            <a href="#" id="regChangeNumber" class="reg-otp-link">Change number</a>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- STEP 3: remaining fields --}}
                            <div id="regStep3" class="reg-step {{ ($otpRequired && ! $verifiedPhone) ? 'bp-hidden' : '' }}">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="single_input">
                                            <label>Full Name *</label>
                                            <input type="text" name="name" placeholder="Enter your full name"
                                                value="{{ old('name') }}" class="@error('name') is-invalid @enderror">
                                            @error('name')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="single_input">
                                            <label>Email Address</label>
                                            <input type="email" name="email" placeholder="your@email.com (optional)"
                                                value="{{ old('email') }}" class="@error('email') is-invalid @enderror">
                                            @error('email')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="single_input">
                                            <label>Division</label>
                                            <select name="division" class="select_2">
                                                <option value="">Select Division</option>
                                                @php
                                                    $divisions = ['Dhaka','Chattogram','Rajshahi','Khulna','Barishal','Sylhet','Rangpur','Mymensingh'];
                                                @endphp
                                                @foreach ($divisions as $div)
                                                    <option value="{{ $div }}" {{ old('division') == $div ? 'selected' : '' }}>{{ $div }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="single_input">
                                            <label>District</label>
                                            <input type="text" name="district" placeholder="Your district" value="{{ old('district') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="single_input">
                                            <label>Address</label>
                                            <input type="text" name="address" placeholder="Your address" value="{{ old('address') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="single_input">
                                            <label>Password *</label>
                                            <input type="password" name="password" placeholder="********"
                                                class="@error('password') is-invalid @enderror">
                                            @error('password')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="single_input">
                                            <label>Confirm Password *</label>
                                            <input type="password" name="password_confirmation" placeholder="********">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <button type="submit" class="common_btn">Sign Up <i class="fas fa-long-arrow-right"></i></button>
                                    </div>
                                </div>
                            </div>
                        </form>
```

Note: the `required` attributes on name/password are intentionally dropped (the fields may be hidden during steps 1–2; server-side validation still enforces them). Phone keeps `required`.

- [ ] **Step 3b: Add the AJAX script block**

Append this at the end of `register.blade.php` (after the closing `@endsection` of content is wrong — put it before `@endsection` is also wrong; add a new stack push at the very end of the file, after the existing `@endsection`):

```blade

@push('scripts')
<script>
'use strict';
$(function () {
    @if($otpRequired && ! $verifiedPhone)
    var sendOtpUrl   = '{{ route('storefront.customer.register.send-otp') }}';
    var verifyOtpUrl = '{{ route('storefront.customer.register.verify-otp') }}';
    var cooldownTimer = null;

    function showMsg($el, text, isError) {
        $el.text(text).toggleClass('error', !!isError);
    }

    function startCountdown(seconds) {
        var remaining = seconds;
        var $btn = $('#regResend');
        var $cd = $('#regCountdown');
        $btn.addClass('bp-hidden');
        $cd.text('Resend in ' + remaining + 's');
        clearInterval(cooldownTimer);
        cooldownTimer = setInterval(function () {
            remaining -= 1;
            if (remaining <= 0) {
                clearInterval(cooldownTimer);
                $cd.text('');
                $btn.removeClass('bp-hidden');
            } else {
                $cd.text('Resend in ' + remaining + 's');
            }
        }, 1000);
    }

    function sendOtp() {
        var phone = $.trim($('#regPhone').val());
        if (!phone) { showMsg($('#regSendMsg'), 'Please enter your phone number.', true); return; }
        $('#regContinueBtn, #regResend').prop('disabled', true);
        $.ajax({ url: sendOtpUrl, method: 'POST', data: { phone: phone } })
            .done(function (res) {
                if (res.ok) {
                    showMsg($('#regSendMsg'), res.message || 'Code sent.', false);
                    $('#regStep2').removeClass('bp-hidden');
                    $('#regCode').trigger('focus');
                    startCountdown(60);
                } else {
                    showMsg($('#regSendMsg'), res.message || 'Unable to send code.', true);
                }
            })
            .fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.phone)
                    ? xhr.responseJSON.errors.phone[0] : 'Please enter a valid phone number.';
                showMsg($('#regSendMsg'), msg, true);
            })
            .always(function () { $('#regContinueBtn, #regResend').prop('disabled', false); });
    }

    function verifyOtp() {
        var phone = $.trim($('#regPhone').val());
        var code = $.trim($('#regCode').val());
        if (code.length !== 6) { showMsg($('#regVerifyMsg'), 'Enter the 6-digit code.', true); return; }
        $('#regVerifyBtn').prop('disabled', true);
        $.ajax({ url: verifyOtpUrl, method: 'POST', data: { phone: phone, code: code } })
            .done(function (res) {
                if (res.ok) {
                    clearInterval(cooldownTimer);
                    $('#regPhone').prop('readonly', true);
                    $('#regStep2').addClass('bp-hidden');
                    $('#regContinueBtn').addClass('bp-hidden');
                    $('#regStep3').removeClass('bp-hidden');
                } else {
                    showMsg($('#regVerifyMsg'), res.message || 'Invalid code.', true);
                }
            })
            .fail(function () { showMsg($('#regVerifyMsg'), 'Verification failed. Try again.', true); })
            .always(function () { $('#regVerifyBtn').prop('disabled', false); });
    }

    $('#regContinueBtn').on('click', sendOtp);
    $('#regVerifyBtn').on('click', verifyOtp);
    $('#regResend').on('click', function (e) { e.preventDefault(); sendOtp(); });
    $('#regChangeNumber').on('click', function (e) {
        e.preventDefault();
        clearInterval(cooldownTimer);
        $('#regPhone').prop('readonly', false).trigger('focus');
        $('#regStep2').addClass('bp-hidden');
        $('#regStep3').addClass('bp-hidden');
        $('#regCode').val('');
        showMsg($('#regVerifyMsg'), '', false);
        showMsg($('#regSendMsg'), '', false);
    });
    $('#regCode').on('keypress', function (e) { if (e.which === 13) { e.preventDefault(); verifyOtp(); } });
    @endif
});
</script>
@endpush
```

- [ ] **Step 3c: Add CSS for hidden state + OTP elements**

Append to `public/website/assets/css/style.css`:

```css
/* ===== Registration OTP steps ===== */
.bp-hidden { display: none !important; }
.reg-otp-msg { font-size: 13px; margin: 6px 0; }
.reg-otp-msg.error { color: #C0392B; }
.reg-otp-input { letter-spacing: 4px; }
.reg-otp-link { font-size: 13px; text-decoration: underline; }
.reg-otp-muted { font-size: 13px; color: #7F8C8D; }
```

- [ ] **Step 4: Run the render tests**

Run: `php artisan test Modules/Ecommerce/tests/Feature/CustomerRegistrationOtpTest.php`
Expected: PASS (9 tests total — 7 from Task 2 plus the 2 render tests).

- [ ] **Step 5: Manual QA (dev server)**

Per CLAUDE.md §20 start the server and verify on `http://127.0.0.1:8000/register`:
- Page loads showing only the phone field + Continue (step 1).
- With the BulkSMSBD gateway configured (Settings → SMS Gateway: api_key + sender_id), enter a new number → Continue → step 2 appears with a 60s countdown. Without live SMS, read the code in tinker: `Cache::get('customer_reg_otp_<digits>')`.
- Enter the code → Verify → the rest of the fields appear and the phone becomes read-only.
- Complete the form → account created, redirected to the customer dashboard.
- Enter an already-registered number at step 1 → "already registered — please sign in" message, no step 2.
- Settings → set "Registration OTP Verification" = Disabled → `/register` shows the full flat form immediately and submits without OTP.
- Check 768/992/1200 widths render cleanly.

- [ ] **Step 6: Commit**

```bash
git add Modules/Ecommerce/resources/views/storefront/pages/customer/register.blade.php public/website/assets/css/style.css Modules/Ecommerce/tests/Feature/CustomerRegistrationOtpTest.php
git commit -m "feat(ecommerce): three-step OTP-gated registration UI

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Self-Review

**Spec coverage:**
- Phone-only SMS OTP via `SmsGatewayInterface` → Task 1. ✓
- Single-page AJAX 3-step flow, current theme → Task 4. ✓
- Existing-phone block, no SMS → Task 2 (`sendRegisterOtp`) + feature test. ✓
- Admin toggle `customer_otp_required` (default on) → Task 3; honored in Tasks 2 & 4. ✓
- Spam controls (format check, cooldown 60s, 10-min expiry, 5-attempt cap, throttle, final session re-check) → Tasks 1 & 2. ✓
- Edge cases (direct POST, change number, send failure, gating off, expired code) → Tasks 1/2/4. ✓
- Out-of-scope items (email OTP, login/reset OTP, `phone_verified_at` column) → not implemented. ✓
- No migration / seeder (mirrors `customer_auth_enabled` default) → confirmed in File Structure. ✓

**Placeholder scan:** No TBD/TODO; every code step contains full code. ✓

**Type consistency:** `CustomerOtpService::send` returns `array{ok,reason?,retry_after?}` used identically in `sendRegisterOtp`. `normalize()` used for the session value (verify) and the comparison (register). Session key `register_verified_phone` consistent across verify/register/view. Route names consistent between routes, controller, view, and tests. ✓

**Testing reality:** Tests use `Tests\TestCase` (`RefreshDatabase`, array cache/session), bind a fake `SmsGatewayInterface` via `$this->app->instance(...)`, and rely on Laravel skipping CSRF in the testing environment. The DB is MySQL `bizpos_test` (per `phpunit.xml`); the runner must have it available.
