---
name: laravel-test-and-fix
description: >
  Use this skill whenever the user says "test my app", "run the tests", "fix failing tests",
  "test and fix", "run gates", "check if everything works", "test continuously", or anything
  implying they want Claude to autonomously run tests, find failures, fix code, and repeat
  until the Laravel application is fully green. Also trigger when the user says "something
  is broken", "tests are failing", or "make sure my app works". This skill runs PHPUnit
  gates (Unit, Feature, API) and Laravel Dusk browser tests with screenshot validation,
  then autonomously reads error output, locates the broken code, applies fixes, and
  re-runs until all gates pass — no manual intervention needed.
stack:
  - PHP / Laravel
  - React TypeScript
  - TailwindCSS
  - MySQL
  - Laravel Dusk (ChromeDriver)
---

# Laravel Autonomous Test-and-Fix Skill

**Announce at start:** `"I'm using the laravel-test-and-fix skill. I will run all gates, fix failures automatically, and loop until everything passes."`

---

## Overview

This skill mirrors the `claude-mobile-ios-testing` approach but for Laravel web applications.

| iOS Skill         | This Skill                        |
|-------------------|-----------------------------------|
| expo-mcp          | PHPUnit + Laravel Dusk            |
| xc-mcp            | `php artisan` + `run-gates.sh`    |
| testID selectors  | `dusk=""` / `data-testid=""`      |
| AI screenshot check | AI reads screenshots + error logs |
| Validation Gates  | Gate 1 → 2 → 3 → 4 (Dusk)        |

---

## Phase 0 — Pre-Flight Check

Before running any test, verify the environment is ready.

```bash
# 1. Confirm we are in a Laravel project
ls artisan || echo "NOT A LARAVEL PROJECT — STOP"

# 2. Check .env.testing exists
ls .env.testing || cp .env.example .env.testing

# 3. Check test DB connection
php artisan migrate:fresh --env=testing --force

# 4. Check ChromeDriver (for Dusk)
php artisan dusk:chrome-driver --detect

# 5. Confirm run-gates.sh exists
ls run-gates.sh || echo "MISSING: copy run-gates.sh from the testing kit"
```

**If any check fails:** Fix the environment issue first before running gates.
**Do NOT proceed** if `artisan` is missing or DB connection fails.

---

## Phase 1 — Run All Gates

Run the master gate script. Always start here.

```bash
bash run-gates.sh 2>&1 | tee storage/logs/test-gates/latest.log
```

Or without browser (faster, no Chrome needed):

```bash
bash run-gates.sh --no-dusk 2>&1 | tee storage/logs/test-gates/latest.log
```

**Parse the result:**
- `✅ GATE N PASSED` → move to next gate
- `❌ GATE N FAILED` → enter the Fix Loop for that gate
- `ALL GATES PASSED` → done, report success

---

## Phase 2 — Fix Loop

When a gate fails, follow this loop **until it passes**. Max iterations: **10**.

```
┌─────────────────────────────────────────────┐
│  1. Read the failure output carefully        │
│  2. Identify the file + line number          │
│  3. Understand WHY it failed                 │
│  4. Apply the minimal fix                    │
│  5. Re-run only the failing gate             │
│  6. If pass → run all gates to confirm       │
│  7. If still failing → repeat from step 1   │
└─────────────────────────────────────────────┘
```

### Reading Failure Output

For PHPUnit failures, look for:
```
FAILED  Tests\Feature\AuthFeatureTest > user_can_login_with_correct_credentials
Expected status code 302 but received 200.
```
→ This tells you: **file**, **test method**, **assertion that failed**.

For Dusk failures, look for:
```
Facebook\WebDriver\Exception\NoSuchElementException
Did not find element with selector [dusk="login-btn"]
```
→ This tells you: **the Blade view is missing the `dusk="login-btn"` attribute**.

### Re-run a Single Gate (faster feedback loop)

```bash
# Gate 1 only
php artisan test --testsuite=Gate1-Unit --env=testing

# Gate 2 only
php artisan test --testsuite=Gate2-Feature --env=testing

# Gate 3 only
php artisan test --testsuite=Gate3-API --env=testing

# Single Dusk gate
php artisan dusk --filter=Gate4A --env=testing
php artisan dusk --filter=Gate4B --env=testing
php artisan dusk --filter=Gate6C --env=testing
```

### Re-run a Single Test Method

```bash
php artisan test --filter="user_can_login_with_correct_credentials" --env=testing
```

---

## Phase 3 — Fix Decision Tree

Use this to decide what to fix based on the error type.

### 3A — HTTP Status Mismatch
```
Expected: 302   Got: 200    → Route not redirecting. Check controller return.
Expected: 200   Got: 302    → Auth middleware blocking. Check route/middleware.
Expected: 200   Got: 404    → Route missing. Check routes/web.php or api.php.
Expected: 200   Got: 500    → Server error. Check logs: storage/logs/laravel.log
Expected: 201   Got: 422    → Validation failing. Check FormRequest rules.
Expected: 401   Got: 200    → Auth middleware missing on route.
```

### 3B — Validation Error (`assertJsonValidationErrors`)
- Open the FormRequest class for that endpoint
- Check `rules()` method — field may be missing or rule is wrong
- Check `messages()` if custom messages are expected

### 3C — Database Assertion Failure (`assertDatabaseHas` / `assertDatabaseMissing`)
- Check the controller saves data correctly
- Check `$fillable` in the Model includes the field
- Check factory generates correct data

### 3D — Dusk Element Not Found (`dusk="..."`)
```
NoSuchElementException: dusk="login-btn"
```
→ Open the Blade view → add `dusk="login-btn"` to the button element.

### 3E — React/AJAX Timeout (Dusk `waitFor`)
```
TimeoutException: waiting for [data-testid="data-table"]
```
→ The React component or AJAX call is slow or failing.
- Check browser console errors in the screenshot
- Check the API endpoint the component calls
- Add `data-testid="data-table"` if it's missing from the component

### 3F — Migration / DB Error
```
SQLSTATE[42S02]: Table 'laravel_testing.posts' doesn't exist
```
→ Run: `php artisan migrate:fresh --env=testing --force`
→ If still failing: check `database/migrations/` for the missing table migration

### 3G — Class/Method Not Found
```
Error: Call to undefined method App\Models\User::createToken()
```
→ Add `HasApiTokens` trait to the User model (for Sanctum)

---

## Phase 4 — Screenshot Validation (Dusk Gates)

After Dusk gates run, screenshots are saved to `tests/Browser/screenshots/`.

**Validate each screenshot by asking:**
- ✅ Does the page look correct? No blank white screen?
- ✅ Is the form/button/element visible?
- ✅ Is the layout responsive (Gate 6A mobile)?
- ✅ Does the success message appear (Gate 6B)?
- ✅ Is the React component rendered, not a loading spinner? (Gate 6C)

**If a screenshot shows a broken layout:**
1. Identify which gate produced the screenshot (filename prefix: `gate4a-`, `gate6a-`, etc.)
2. Check the corresponding Blade/React view
3. Fix the TailwindCSS classes or component logic
4. Re-run that Dusk gate

```bash
# List all screenshots with timestamps
ls -lt tests/Browser/screenshots/*.png
```

---

## Phase 5 — Common Fixes Reference

### Fix: Missing Route
```php
// routes/web.php
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');
```

### Fix: Missing API Route
```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'update']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('posts', PostController::class);
});

Route::post('/login', [AuthController::class, 'login']);
```

### Fix: API Login Controller (Sanctum)
```php
// app/Http/Controllers/API/AuthController.php
public function login(Request $request)
{
    $credentials = $request->validate([
        'email'    => 'required|email',
        'password' => 'required',
    ]);

    if (!Auth::attempt($credentials)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $user  = Auth::user();
    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'message' => 'Login successful',
        'data'    => ['token' => $token, 'user' => $user],
    ]);
}

public function logout(Request $request)
{
    $request->user()->currentAccessToken()->delete();
    return response()->json(['message' => 'Logged out successfully']);
}
```

### Fix: User Model (Sanctum + API Token)
```php
// app/Models/User.php
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password'];
    protected $hidden   = ['password', 'remember_token'];
    protected $casts    = ['email_verified_at' => 'datetime', 'password' => 'hashed'];
}
```

### Fix: Missing dusk="" attributes (Blade)
```html
<!-- login.blade.php -->
<input dusk="email" type="email" name="email" value="{{ old('email') }}" />
<input dusk="password" type="password" name="password" />
<button dusk="login-btn" type="submit">Login</button>

<!-- register.blade.php -->
<input dusk="name" type="text" name="name" />
<input dusk="email" type="email" name="email" />
<input dusk="password" type="password" name="password" />
<input dusk="password_confirmation" type="password" name="password_confirmation" />
<button dusk="register-btn" type="submit">Register</button>

<!-- profile/edit.blade.php -->
<input dusk="name" type="text" name="name" value="{{ $user->name }}" />
<input dusk="email" type="email" name="email" value="{{ $user->email }}" />
<button dusk="save-btn" type="submit">Save</button>
```

### Fix: Missing data-testid in React (TypeScript)
```tsx
// resources/js/app.tsx
ReactDOM.createRoot(document.getElementById('app')!).render(
  <div data-testid="react-root">
    <App />
  </div>
);

// resources/js/components/DataTable.tsx
export default function DataTable({ rows }: Props) {
  return (
    <div data-testid="data-table">
      {rows.map(row => <div key={row.id}>{row.title}</div>)}
    </div>
  );
}
```

### Fix: Sanctum CSRF for API (if 419 errors)
```php
// config/sanctum.php — ensure your frontend domain is listed
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1')),
```

---

## Phase 6 — Confirm All Gates Green

After all individual fixes, run the full suite to confirm nothing regressed:

```bash
bash run-gates.sh --no-dusk && echo "✅ PHPUnit CLEAN"
bash run-gates.sh           && echo "✅ ALL GATES CLEAN"
```

**Expected final output:**
```
✅ GATE 1 - Unit Tests: PASS
✅ GATE 2 - Feature Tests: PASS
✅ GATE 3 - API Tests: PASS
✅ GATE 4A - Login Flow + Screenshots: PASS
✅ GATE 4B - Registration Flow: PASS
✅ GATE 4C - Dashboard Navigation: PASS
✅ GATE 6A - Responsive / Mobile Layout: PASS
✅ GATE 6B - Form Submission: PASS
✅ GATE 6C - AJAX / React Component: PASS

🎉 ALL GATES PASSED — Application is healthy!
```

---

## Rules (Never Break These)

| ❌ WRONG | ✅ RIGHT |
|---|---|
| Skip a gate because "it probably passes" | Always run all gates after a fix |
| Fix a test to make it pass without fixing the real code | Fix the application code, not the test assertion |
| Guess why a test fails | Read the full error output first |
| Run `php artisan test` without `--env=testing` | Always use `--env=testing` |
| Delete a failing test | Fix the code the test is checking |
| Give up after 3 attempts | Try up to 10 iterations, try a different approach |
| Run Dusk without checking ChromeDriver | Always verify ChromeDriver matches Chrome version |

---

## Adding Tests for New Features

When the user adds a new controller, model, or route, automatically create matching tests:

1. **New Model** → add test in `tests/Unit/YourModelTest.php`
2. **New Route/Controller** → add test in `tests/Feature/YourFeatureTest.php`
3. **New API endpoint** → add test in `tests/API/ApiTest.php`
4. **New Blade page** → add Dusk test class in `tests/Browser/DuskGatesTest.php`
5. **Re-run all gates** to confirm new tests pass

---

## Useful Commands Cheatsheet

```bash
# Environment
php artisan migrate:fresh --env=testing --force
php artisan key:generate --env=testing
php artisan dusk:chrome-driver --detect

# Run gates
bash run-gates.sh                    # All gates
bash run-gates.sh --no-dusk          # PHPUnit only
bash run-gates.sh --gate=3           # API only

# PHPUnit
php artisan test --env=testing                          # All PHPUnit
php artisan test --testsuite=Gate2-Feature --env=testing
php artisan test --filter="method_name" --env=testing

# Dusk
php artisan dusk --env=testing
php artisan dusk --filter=Gate4A --env=testing

# Logs
tail -f storage/logs/laravel.log
cat storage/logs/test-gates/latest.log
ls tests/Browser/screenshots/
```
