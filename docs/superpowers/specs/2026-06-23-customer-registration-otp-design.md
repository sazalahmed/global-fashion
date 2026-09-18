# Design: OTP-Gated Customer Registration (Storefront)

**Date:** 2026-06-23
**Module:** Ecommerce (storefront customer auth)
**Status:** Approved design — pending implementation plan

## Problem

The storefront registration page (`/register`,
`storefront.customer.register`) presents the full signup form immediately.
Bots can submit it freely, creating spam customer accounts. We want to gate
the form behind a phone-number OTP check so only people who control a real
Bangladeshi mobile number can complete registration.

## Goal

Turn `/register` into a single-page, AJAX-driven, three-step flow:

1. Collect phone number, send an SMS OTP.
2. Verify the OTP.
3. Only then reveal the existing registration fields and allow submission.

Keep the current storefront theme. Reuse the BulkSMSBD SMS gateway already
wired through `Modules\Marketing\Contracts\SmsGatewayInterface`.

## Decisions (locked)

- **OTP channel:** Phone only, SMS via `SmsGatewayInterface` (BulkSMSBD).
- **Flow:** Single `/register` page with AJAX steps (no extra page loads, no
  modals).
- **Visuals:** Keep the existing storefront sign-up layout/styles; add the
  step-1 phone+OTP gate before the existing fields.
- **Existing phone:** If the phone is already registered, block at step 1 with
  "This number is already registered — please sign in" (link to login). **No
  SMS is sent.**
- **Admin toggle:** New `customer_otp_required` Ecommerce setting (default
  `'1'`). When `'0'`, the page renders the full form directly and the
  server-side verified-phone check is skipped.

## Current State (facts)

- Route file: `Modules/Ecommerce/routes/storefront.php`, `guest:customer`
  group. `POST /register` → `CustomerAuthController@register`
  (`throttle:login`).
- Controller: `Modules\Ecommerce\Http\Controllers\Storefront\CustomerAuthController`.
- View: `Modules/Ecommerce/resources/views/storefront/pages/customer/register.blade.php`.
- Model: `Modules\Ecommerce\Models\StorefrontCustomer` (`$table = 'customers'`,
  `password` cast to `hashed`, `phone` is the login identifier).
- Existing register validation: `name` (required), `phone` (required, unique
  `customers,phone`, `App\Rules\PhoneNumber`), `email` (nullable, unique),
  `password` (min 6, confirmed), `division`/`district`/`address` (nullable).
- Existing OTP helper (admin only): `Modules\Auth\Services\AuthService::sendOtp/verifyOtp`
  — cache-based, 6-digit, 10-min, keyed by email, no real send. **Not reused**
  (different concern/identifier); the storefront gets its own service.
- SMS: `Modules\Marketing\Contracts\SmsGatewayInterface` bound to
  `BulkSmsBdGateway`; `send(string $number, string $message): bool`.
- Settings: `EcommerceSetting::get(key, default): ?string` /
  `EcommerceSetting::set(key, value): void`. The settings form
  (`settings.blade.php`) posts to `EcommerceController@settingsUpdate`, which
  does `$request->except([...])` then `service->updateSettings($data)` — so a
  new `<select name="customer_otp_required">` is persisted automatically with
  no whitelist change.
- Rate limiter `login` is defined in `app/Providers/AppServiceProvider.php`.
- CSRF for AJAX is already globally configured via `$.ajaxSetup` in the layout.

## Architecture

```
/register page (Blade + jQuery AJAX)
  Step 1: phone  ──POST /register/send-otp──▶ CustomerAuthController@sendRegisterOtp
                                                  └─▶ CustomerOtpService::send(phone)
                                                          └─▶ SmsGatewayInterface::send()
  Step 2: code   ──POST /register/verify-otp─▶ CustomerAuthController@verifyRegisterOtp
                                                  └─▶ CustomerOtpService::verify(phone, code)
                                                          └─▶ session('register_verified_phone' = phone)
  Step 3: fields ──POST /register───────────▶ CustomerAuthController@register
                                                  └─▶ asserts phone === session('register_verified_phone')
                                                  └─▶ StorefrontCustomer::create(...) (unchanged)
```

### 1. `CustomerOtpService` (new)

Location: `Modules/Ecommerce/app/Services/CustomerOtpService.php`.

Single responsibility: generate / store / verify phone OTPs and dispatch the
SMS. Depends on `SmsGatewayInterface` (constructor injection — Dependency
Inversion; resolved from the container).

Cache keys (per normalized phone):
- `customer_reg_otp_{phone}` → the 6-digit code (TTL 10 min).
- `customer_reg_otp_attempts_{phone}` → verify-attempt counter (TTL 10 min).
- `customer_reg_otp_cooldown_{phone}` → resend lock (TTL 60 s).

Methods:
- `send(string $phone): array`
  - If a cooldown key exists → return
    `['ok' => false, 'reason' => 'cooldown', 'retry_after' => <seconds>]`.
  - Generate `str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT)`.
  - Store code (10 min), reset attempts to 0, set cooldown (60 s).
  - Build message: `"Your {store} verification code is {otp}. Valid for 10 minutes."`
    (store name from settings/config; fall back to app name).
  - `$gateway->send($phone, $message)`. On `false` → log a warning and return
    `['ok' => false, 'reason' => 'send_failed']` (and clear the code so the
    user can retry without waiting the full cooldown — i.e. do **not** set the
    cooldown when the send itself failed).
  - On success → `['ok' => true]`.
- `verify(string $phone, string $code): bool`
  - If no cached code → false.
  - Increment attempts; if attempts > 5 → forget the code, return false.
  - On exact match → forget code + attempts, return true. Else false.
- Helper `normalizePhone(string $phone): string` — trim/normalize so the same
  number maps to one cache key (mirror whatever `PhoneNumber` accepts; store
  digits as entered after trimming spaces). The same normalization is applied
  before the SMS send and before the final `register()` comparison.

Never log the OTP value (production safety). For local QA the code is readable
via `Cache::get("customer_reg_otp_{phone}")` in `tinker`.

### 2. Controller actions (new, thin)

In `CustomerAuthController`:

- `sendRegisterOtp(Request $request): JsonResponse`
  1. `ensureAuthEnabled()` guard.
  2. If OTP gating is off (`customer_otp_required !== '1'`) → 422 JSON (the
     gate should not be hit; the page won't call it). Defensive only.
  3. Validate `phone`: `['required','string','max:30', new PhoneNumber]`.
  4. If `StorefrontCustomer::where('phone', $phone)->exists()` → JSON
     `{ ok:false, code:'exists', message:'This number is already registered — please sign in.' }`
     with **HTTP 200** (business outcome, not a validation error); **no SMS
     sent**.

  **JSON status convention:** validation failures use HTTP 422 (Laravel's
  default for `validate()`); all business outcomes (`exists`, `cooldown`,
  `send_failed`, `ok`) return HTTP 200 with an `ok` boolean the JS branches on.
  5. `$result = $otpService->send($phone)`; map to JSON
     (`ok:true` / cooldown with `retry_after` / `send_failed`).
- `verifyRegisterOtp(Request $request): JsonResponse`
  1. `ensureAuthEnabled()` guard.
  2. Validate `phone` (same rule) + `code` (`required|digits:6`).
  3. `$otpService->verify($phone, $code)` → on true set
     `session(['register_verified_phone' => $normalizedPhone])` and return
     `{ ok:true }`; else `{ ok:false, message:'Invalid or expired code.' }`.
- `register(Request $request)` (modified)
  - Existing validation unchanged.
  - **After validation, if OTP gating is on:** require
    `session('register_verified_phone')` to equal the normalized submitted
    `phone`. If not → redirect back with an error
    (`'Please verify your phone number before registering.'`) and the form
    re-collapsed to step 1.
  - On success, `session()->forget('register_verified_phone')` (alongside the
    existing login + flash behavior, which is unchanged).

### 3. Routes (new)

In the `guest:customer` group of `storefront.php`, near the existing register
routes — using named routes only:

```php
Route::post('/register/send-otp',   [CustomerAuthController::class, 'sendRegisterOtp'])
    ->middleware('throttle:login')->name('storefront.customer.register.send-otp');
Route::post('/register/verify-otp', [CustomerAuthController::class, 'verifyRegisterOtp'])
    ->middleware('throttle:login')->name('storefront.customer.register.verify-otp');
```

Rate limiting: reuse the existing `login` limiter on both endpoints; the 60s
per-phone resend cooldown lives in the service.

### 4. View (modified `register.blade.php`)

Keep the existing layout/illustration column and the existing fields. Wrap the
body in three JS-controlled sections:

- **Step 1 (`#regStep1`):** phone input (`name="phone"`, the real field) +
  "Continue" button + inline error container + a "Already registered? Sign in"
  hint.
- **Step 2 (`#regStep2`, hidden):** 6-digit code input, "Verify" button,
  "Resend code" link with a 60s countdown, a "change number" link back to
  step 1, inline error container.
- **Step 3 (`#regStep3`, hidden):** the existing fields (name, email,
  division, district, address, password, confirm) + the "Sign Up" submit. The
  phone shown here is read-only and mirrors the verified number.

When the setting is **off**, render all steps visible / flattened to the
current behavior (the existing single form), so disabling the gate restores
today's UX exactly.

The `<form action="{{ route('storefront.customer.register.post') }}">` and its
`@csrf` stay. The phone field lives inside the form so it posts normally; steps
1–2 are an overlay/sequence on the same form.

### 5. Frontend JS (new `@push('scripts')` block in the view)

- Starts with `'use strict';`.
- Reads route URLs from Blade (`route('...send-otp')`, `route('...verify-otp')`)
  into JS consts — no hardcoded paths.
- Uses jQuery `$.ajax` (CSRF header already set globally).
- Step 1 handler: validate non-empty, POST send-otp, show spinner; on `ok`
  → reveal step 2 + start 60s countdown; on `code:'exists'` → show the
  sign-in message; on cooldown → show countdown; on `send_failed` → show
  "Couldn't send the code, please try again."
- Step 2 handler: POST verify-otp; on `ok` → reveal step 3, mark phone
  read-only; else show inline error.
- Resend: disabled during countdown; re-POSTs send-otp when allowed.
- All server messages inserted with `.text()` (never `.html()`) per the XSS
  rule.
- No inline CSS. Any new visual states (hidden steps, countdown text, OTP
  input styling) use `bp-`-prefixed classes added to `public/css/style.css`,
  each with a `[data-theme="dark"]` override. Step show/hide uses jQuery
  `.show()/.hide()` / class toggles (the allowed runtime-state exception).

### 6. Admin setting (modified `settings.blade.php` + nothing else)

Add a second control to the existing "Customer Authentication" card
(`#customerAuth`):

```blade
<select class="bp-form-select w-100" name="customer_otp_required">
  <option value="1" ...>Enabled — Require phone OTP before registration</option>
  <option value="0" ...>Disabled — Allow registration without OTP</option>
</select>
```

Default `'1'`, read via
`old('customer_otp_required', $settings['customer_otp_required'] ?? '1')`.
Persisted automatically by `settingsUpdate` (no controller change). Add a
matching default row to the Ecommerce settings seeder if one exists for these
keys (otherwise the `?? '1'` fallback covers it).

## Spam-Control Summary

| Control | Where | Value |
|---|---|---|
| Phone format check before SMS | `sendRegisterOtp` | `PhoneNumber` rule |
| Existing-phone short-circuit (no SMS) | `sendRegisterOtp` | unique check |
| Resend cooldown (per phone) | `CustomerOtpService` | 60 s |
| OTP expiry | cache TTL | 10 min |
| Max verify attempts (per code) | `CustomerOtpService` | 5 |
| Endpoint rate limit | routes | `throttle:login` |
| Final submit re-check | `register()` | session === phone |

## Edge Cases

- **Direct `POST /register` (skipping steps):** blocked by the session
  verified-phone check when gating is on.
- **User changes phone after verifying:** the verified session value won't
  match the new phone → submit blocked, must re-verify. (JS keeps the verified
  phone read-only to avoid surprising the user.)
- **SMS gateway not configured / send fails:** JSON `send_failed`; user sees a
  retriable error; no cooldown is set so they can retry immediately.
- **Gating disabled mid-session:** `register()` skips the check; page renders
  the flat form. No verified session needed.
- **OTP expired at verify time:** treated as invalid code; user resends.

## Out of Scope (YAGNI)

- Email OTP / combined email-or-phone identifier.
- OTP on login or password reset (login stays phone+password; reset stays
  email link).
- Voice OTP, WhatsApp, or alternative channels.
- Per-account verified-phone persistence column (`phone_verified_at`) — the
  session gate is sufficient for the anti-spam goal; not adding a migration.

## Testing

- **Unit:** `CustomerOtpService` — send sets code + cooldown; cooldown blocks
  resend; verify matches/rejects; attempts cap invalidates; send_failed path
  doesn't set cooldown. Use a fake/mock `SmsGatewayInterface`.
- **Feature:** `sendRegisterOtp` blocks existing phone (no gateway call),
  validates format, respects cooldown; `verifyRegisterOtp` sets session;
  `register` rejects unverified phone and accepts verified phone; setting `'0'`
  bypasses the gate.
- **Manual/QA (local):** gateway needs api_key + sender_id configured for real
  delivery; otherwise read the code from `Cache::get("customer_reg_otp_{phone}")`
  in `tinker`. Verify dark mode and 768/992/1200 breakpoints on the new steps.
