# Design: Admin Password Reset via Google Authenticator + SMS (No Email)

**Date:** 2026-06-26
**Module:** Auth (admin password reset) + Security (2FA enrollment)
**Status:** Approved design — pending implementation plan

## Problem

Admin password reset currently depends entirely on **email**. `AuthController`
generates a 6-digit OTP, emails it via `OtpMail`, stores a hashed copy in
`password_reset_tokens`, then verifies and resets. We want to remove the email
dependency for the admin side and prove identity another way.

The system has **no 2FA / Google Authenticator anywhere today** (verified: no
`pragmarx/google2fa`, no TOTP, no MFA in code or `docs/`). This is a new
feature, not a modification of an existing plan.

## Goal

Replace email in the admin password-reset flow with:

1. **Google Authenticator (TOTP)** — when the admin has enrolled.
2. **SMS OTP** to the admin's stored phone — when they have not enrolled.

Add opt-in Authenticator enrollment (self-service, plus an admin can
reset/clear another user's 2FA). Reuse the already-wired BulkSMSBD SMS gateway
and the existing `password_reset_tokens` reset-token mechanics.

## Decisions (locked)

- **Reset channels:** Authenticator TOTP if enrolled; otherwise SMS OTP to the
  stored phone. **Email is removed from the admin reset path entirely.**
- **Identifier:** Admin enters their **email** (same as login) on the
  forgot-password page; the flow auto-routes by enrollment state. Email stays
  the account key — nothing is emailed.
- **Lockout recovery:** A super-admin / user-manager resets a locked-out user's
  password directly via the **existing** `security.users.update`
  (`users.edit`-gated) — no new build. **No recovery-codes table** (opted out).
  Phone is **not** made mandatory.
- **Enrollment:** Opt-in **self-service** (scan QR, confirm one code), **plus**
  an admin can **reset/clear** another user's 2FA from the Users edit screen.
  Admin-generated secrets handed to users are explicitly **out of scope**
  (weaker security; an admin cannot scan another person's phone).
- **Phone masking:** At Step 1, for an SMS-routed account, show the masked
  phone (`01XXXXXX89`). Accepted because this is an admin-only surface, not the
  public storefront.
- **Approach:** Dedicated services (`TwoFactorService`,
  `AdminPasswordResetService`) with a thin `AuthController`; reuse existing
  reset-token issuance. (Chosen over inlining in the controller or adopting
  Laravel Fortify.)

## Current State (facts)

- Reset flow: `Modules/Auth/app/Http/Controllers/AuthController.php`
  (`sendOtp`, `showResetPassword`, `resetPassword`); routes in
  `Modules/Auth/routes/web.php` — `password.request`, `password.send-otp`,
  `password.reset`, `password.update`, all `throttle:login`, all `guest`.
- Email send: `Modules\Auth\Mail\OtpMail` via `Mail::to($email)->send(...)`.
- `password_reset_tokens` table: PK `email`, columns `token` (hashed),
  `created_at`. SMS OTP (10-min) and the issued reset token (60-min) both live
  here today and continue to.
- `AuthService` has unused cache-based `sendOtp`/`verifyOtp` placeholders — the
  real logic is in the controller. Not reused.
- `users` table (`database/migrations/0001_01_01_000000_create_users_table.php`):
  has `phone` (`varchar(20)`, **nullable**), `email` (unique), soft deletes.
- SMS: `Modules\Marketing\Contracts\SmsGatewayInterface` bound to
  `BulkSmsBdGateway`; `send(string $number, string $message): bool`. Same
  gateway used by storefront registration OTP
  (`Modules/Ecommerce/app/Services/CustomerOtpService.php`).
- User management: `Modules\Security\Http\Controllers\UserController`.
  - `update()` (`security.users.update`, `users.edit`) already lets an admin
    set another user's password (nullable `password` field) — the lockout
    escape hatch.
  - Self-service: `security.profile`, `security.change-password` (work for any
    user incl. super-admin; no `users.edit` gate — only act on self).
  - `guardSuperAdmin()` aborts 404 on actions against a super-admin target
    (except self where allowed).
- Rate limiter `login` defined in `app/Providers/AppServiceProvider.php`.
- CSRF for AJAX globally configured via `$.ajaxSetup` in the layout.

## Architecture

### Data model — one migration, two nullable columns on `users`

| Column | Type | Purpose |
|---|---|---|
| `two_factor_secret` | `text` nullable, **`encrypted` cast** | TOTP shared secret, encrypted at rest. |
| `two_factor_confirmed_at` | `timestamp` nullable | Set only after a valid code confirms setup. **"Enrolled" = non-null.** |

A secret with no `two_factor_confirmed_at` = enrollment started but not
finished → treated as **not enrolled** everywhere (routes to SMS; never locks
anyone out). No recovery-codes table.

`User` model: add both to `$fillable` is **not** required for these (set
explicitly); add `'two_factor_secret' => 'encrypted'` and
`'two_factor_confirmed_at' => 'datetime'` to `$casts`. Add a helper
`hasTwoFactorEnabled(): bool` (`!is_null($two_factor_confirmed_at)`).

### Dependencies (Composer, served locally — no CDN)

- `pragmarx/google2fa` — secret generation + TOTP verification.
- `pragmarx/google2fa-qrcode` + `bacon/bacon-qr-code` — render enrollment QR as
  an inline **SVG** (no external QR service, no client JS lib).

### 1. `TwoFactorService` (new)

Location: `Modules/Security/app/Services/TwoFactorService.php`.
Single responsibility: manage a user's TOTP secret lifecycle. Depends on
`PragmaRX\Google2FA\Google2FA` (resolved from the container).

- `generateSecret(User $user): array` — create a base32 secret, store it on the
  user **unconfirmed** (clears any prior `two_factor_confirmed_at`), return
  `['secret' => ..., 'qrSvg' => ...]`. QR otpauth label uses the app/store name
  + user email.
- `confirm(User $user, string $code): bool` — verify `$code` against the stored
  secret; on success set `two_factor_confirmed_at = now()`, return true.
- `verify(User $user, string $code): bool` — verify a code against the
  confirmed secret with a **±1 time-step** window (clock drift).
- `disable(User $user): void` — null both columns.

Never log secrets or codes.

### 2. `AdminPasswordResetService` (new)

Location: `Modules/Auth/app/Services/AdminPasswordResetService.php`.
Single responsibility: drive the reset handshake. Depends on
`SmsGatewayInterface` and `TwoFactorService` (constructor injection).

- `initiate(string $email): array`
  - Unknown email → `['method' => 'generic']` (no enumeration; nothing sent).
  - Confirmed 2FA → `['method' => 'totp']` (nothing sent).
  - Not enrolled + has phone → send SMS OTP (6-digit, hashed into
    `password_reset_tokens`, 10-min), 60s per-email resend cooldown →
    `['method' => 'sms', 'maskedPhone' => '01XXXXXX89']`. On gateway failure →
    `['method' => 'sms', 'sent' => false, 'reason' => 'send_failed']` and **no
    cooldown set**.
  - Not enrolled + no phone → `['method' => 'unavailable']`.
- `verify(string $email, string $code): ?string`
  - TOTP accounts → `TwoFactorService::verify`.
  - SMS accounts → `Hash::check($code, token)` + 10-min expiry.
  - On success → write a fresh 64-char reset token (hashed) to
    `password_reset_tokens`, return the plaintext token; else null.

The final password update (Step 3) reuses the **existing** `resetPassword`
logic unchanged (validate token, 60-min expiry, update password, delete token).

### 3. Controller actions (`AuthController`, thin)

- `requestReset(Request $request)` — validate `email`; delegate to
  `initiate`; return JSON describing the method (and masked phone / send
  status). Replaces `sendOtp`'s send mode.
- `verifyReset(Request $request)` — validate `email` + `code`; delegate to
  `verify`; return `{ ok, token }` or an invalid-code error. Replaces
  `sendOtp`'s verify mode.
- `resetPassword(...)` — unchanged.
- Remove `OtpMail` usage / the email send entirely from this module's reset
  path.

### 4. Routes

**Auth** (`Modules/Auth/routes/web.php`, `guest`, `throttle:login`) — keep the
same paths; map to the new actions:

```php
Route::get('/forgot-password',  [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'requestReset'])->middleware('throttle:login')->name('password.send-otp');
Route::post('/forgot-password/verify', [AuthController::class, 'verifyReset'])->middleware('throttle:login')->name('password.verify');
Route::get('/reset-password',   [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password',  [AuthController::class, 'resetPassword'])->middleware('throttle:login')->name('password.update');
```

**Security** (`Modules/Security/routes/web.php`, `auth` group) — self-service
2FA (no `users.edit` gate; act on self only):

```php
Route::get('/two-factor',           [TwoFactorController::class, 'show'])->name('two-factor');
Route::post('/two-factor/enable',   [TwoFactorController::class, 'enable'])->name('two-factor.enable');
Route::post('/two-factor/confirm',  [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
Route::delete('/two-factor',        [TwoFactorController::class, 'disable'])->name('two-factor.disable');
```

Admin reset-for-others reuses the **existing** Users edit screen/route
(`security.users.update`, `users.edit`) with a new "Reset / disable
Two-Factor" control that calls `TwoFactorService::disable` on the target
(respecting `guardSuperAdmin`).

### 5. Views

- **Forgot-password** (`Modules/Auth/resources/views/forgot-password.blade.php`)
  — 3-step AJAX sequence (email → code → handled by reset-password page),
  mirroring the storefront OTP pattern. Method-aware messaging (TOTP prompt vs.
  "code sent to 01XXXXXX89" vs. "contact your administrator"). `'use strict';`,
  jQuery `$.ajax`, server text inserted via `.text()`, no inline CSS, `bp-`
  classes with `[data-theme="dark"]` overrides, responsive.
- **Two-Factor** (`Modules/Security/resources/views/users/two-factor.blade.php`)
  — status + Enable (shows inline QR SVG + manual key) + Confirm + Disable
  (password required). Same styling rules.
- **Users edit** (`Modules/Security/resources/views/users/create.blade.php`,
  reused for edit) — add the "Reset / disable Two-Factor" control when editing
  a user who has it enabled.
- **Menu:** add "Two-Factor Authentication" to the user/profile dropdown (where
  "Change Password" lives), `@auth`-gated.

## Flow Summary

```
Step 1  email        → requestReset → initiate(email)
                         ├ generic | totp | sms(+maskedPhone) | unavailable
Step 2  code         → verifyReset  → verify(email, code)
                         └ ok → reset token issued (password_reset_tokens)
Step 3  new password → resetPassword (existing) → update + invalidate token
```

## Security & Edge Cases

| Case | Behavior |
|---|---|
| Unknown email (Step 1) | Generic response; no enumeration; nothing sent. |
| Enrolled, lost device | No SMS by design → super-admin clears their 2FA → SMS path. |
| Not enrolled, no phone | `unavailable` → admin sets password via Users edit. |
| SMS gateway fail | `send_failed`, retriable, no cooldown set. |
| Wrong TOTP / SMS code | Invalid-code message; `throttle:login`. |
| Expired SMS OTP | Invalid; resend (60s cooldown). |
| TOTP clock drift | ±1 time-step accepted. |
| Disable 2FA | Requires account password re-entry. |
| Half-finished enrollment | Unconfirmed secret = not enrolled → SMS. |
| Optional hardening | `two_factor_last_used_at` to reject immediate TOTP reuse (not core). |

## Out of Scope (YAGNI)

- 2FA on **login** (login stays email + password). This feature is reset-only.
- Recovery codes (opted out; super-admin reset is the recovery path).
- Admin-generated TOTP secrets handed to other users.
- Mandatory enrollment / forced rollout.
- Email reset as a fallback (removed on the admin side).
- Changes to storefront customer auth.

## Testing

- **Unit:** `TwoFactorService` (generate/confirm/verify/disable, ±1 drift);
  `AdminPasswordResetService` (`initiate` routing for totp/sms/unavailable/
  generic, cooldown, `send_failed` no-cooldown, `verify` issues token) with a
  fake `SmsGatewayInterface`.
- **Feature:** Step-1 routing per enrollment state; no enumeration on unknown
  email; `Mail::fake()` asserts **no email sent**; Step-2 verify (TOTP + SMS)
  issues token; Step-3 resets + invalidates; enrollment enable→confirm→disable
  (password required); admin reset-2FA clears columns + respects
  `guardSuperAdmin` (404 on super-admin); `throttle:login` holds.
- **Manual/QA (local):** SMS code via cache/`password_reset_tokens` in tinker
  when gateway offline; real QR scan end-to-end with Google Authenticator; dark
  mode + 768/992/1200 breakpoints on both new pages.
