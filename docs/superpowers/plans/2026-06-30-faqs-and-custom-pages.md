# FAQs CRUD + Custom Pages Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add admin-managed FAQs (flat, ordered, plain-text) and Custom Pages (rich-text, clean `/{slug}` URLs) to the Ecommerce module, surfaced on the storefront and linkable via the Menu Builder.

**Architecture:** Two new Eloquent models (`Faq`, `Page`) in the Ecommerce module, each with admin CRUD controllers under the `ecommerce.` route prefix (mirroring the existing blog-post / combo CRUD) and storefront display controllers. FAQs render as an accordion at `/faq`; pages render via a catch-all `/{slug}` route registered last. A new `page` Menu Builder item type links pages into nav.

**Tech Stack:** Laravel 12, Blade, Bootstrap 5.3, jQuery 3.7, TinyMCE (via existing `public/js/bp-richtext.js`), SortableJS (vendored), PHPUnit.

## Global Constraints

- Currency `BDT X,XX,XXX`; dates `DD MMM YYYY` (not used here, but standard).
- No inline CSS; all styles in `public/css/style.css` with `bp-` prefixed classes; every new element needs a `[data-theme="dark"]` override.
- No hardcoded routes — always `route('name')` / `{{ route('name') }}`.
- Every `<script>` block and JS file starts with `'use strict';`.
- All admin forms use `@csrf`; non-POST forms use `@method`.
- Controllers thin; validation in Form Requests; `bpAuthorize('ecommerce.<ability>')` gates each admin action (pattern: `view`/`create`/`edit`/`delete`).
- Mass assignment: define `$fillable` on every model.
- Auto-escape Blade `{{ }}`; rich HTML rendered only via `strip_tags($html, '<whitelist>')`.
- Tests use `Tests\TestCase` (provides `$this->admin`); `$this->actingAs($this->admin)` for admin routes.

---

## File Structure

**Feature 1 — FAQs**
- Create: `Modules/Ecommerce/database/migrations/2026_06_30_000010_create_faqs_table.php`
- Create: `Modules/Ecommerce/app/Models/Faq.php`
- Create: `Modules/Ecommerce/app/Http/Controllers/FaqController.php` (admin)
- Create: `Modules/Ecommerce/app/Http/Requests/StoreFaqRequest.php`
- Create: `Modules/Ecommerce/app/Http/Controllers/Storefront/FaqController.php`
- Create: `Modules/Ecommerce/resources/views/faqs/index.blade.php`, `create.blade.php`, `edit.blade.php`
- Create: `Modules/Ecommerce/resources/views/storefront/pages/faq/index.blade.php`
- Modify: `Modules/Ecommerce/routes/web.php` (admin routes)
- Modify: `Modules/Ecommerce/routes/storefront.php` (storefront `/faq`)
- Modify: `Modules/Ecommerce/app/Models/Concerns/HasMenuRoutes.php` (whitelist FAQ)
- Modify: `Modules/Ecommerce/resources/views/storefront/partials/footer.blade.php` (FAQ link)
- Modify: `Modules/Core/resources/views/partials/sidebar.blade.php` (admin nav)

**Feature 2 — Pages**
- Create: `Modules/Ecommerce/database/migrations/2026_06_30_000011_create_pages_table.php`
- Create: `Modules/Ecommerce/app/Models/Page.php`
- Create: `Modules/Ecommerce/app/Http/Controllers/PageController.php` (admin)
- Create: `Modules/Ecommerce/app/Http/Requests/StorePageRequest.php`
- Create: `Modules/Ecommerce/app/Http/Controllers/Storefront/PageController.php`
- Create: `Modules/Ecommerce/resources/views/pages/index.blade.php`, `create.blade.php`, `edit.blade.php`
- Create: `Modules/Ecommerce/resources/views/storefront/pages/page/show.blade.php`
- Modify: `Modules/Ecommerce/routes/web.php` (admin routes)
- Modify: `Modules/Ecommerce/routes/storefront.php` (catch-all, registered LAST)
- Modify: `Modules/Ecommerce/app/Models/MenuItem.php` (add `page` type + resolveUrl)
- Modify: `Modules/Ecommerce/app/Http/Requests/StoreMenuItemRequest.php` (validate `page`)
- Modify: `Modules/Ecommerce/app/Http/Controllers/MenuController.php` (pass `$pages` to view)
- Modify: `Modules/Ecommerce/resources/views/menus/builder.blade.php` (page picker)
- Modify: `Modules/Core/resources/views/partials/sidebar.blade.php` (admin nav)

**Tests**
- Create: `Modules/Ecommerce/tests/Feature/FaqTest.php`
- Create: `Modules/Ecommerce/tests/Feature/PageTest.php`

---

## Task 1: FAQ migration + model

**Files:**
- Create: `Modules/Ecommerce/database/migrations/2026_06_30_000010_create_faqs_table.php`
- Create: `Modules/Ecommerce/app/Models/Faq.php`
- Test: `Modules/Ecommerce/tests/Feature/FaqTest.php`

**Interfaces:**
- Produces: `Modules\Ecommerce\Models\Faq` with `$fillable = ['question','answer','position','is_active']`, scopes `active()` and `ordered()`.

- [ ] **Step 1: Write the failing test**

Create `Modules/Ecommerce/tests/Feature/FaqTest.php`:

```php
<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Faq;
use Tests\TestCase;

class FaqTest extends TestCase
{
    public function test_active_and_ordered_scopes(): void
    {
        Faq::create(['question' => 'Q3', 'answer' => 'A3', 'position' => 3, 'is_active' => true]);
        Faq::create(['question' => 'Q1', 'answer' => 'A1', 'position' => 1, 'is_active' => true]);
        Faq::create(['question' => 'Q hidden', 'answer' => 'A', 'position' => 2, 'is_active' => false]);

        $result = Faq::active()->ordered()->pluck('question')->all();

        $this->assertSame(['Q1', 'Q3'], $result);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FaqTest`
Expected: FAIL — `Class "Modules\Ecommerce\Models\Faq" not found`.

- [ ] **Step 3: Create the migration**

Create `Modules/Ecommerce/database/migrations/2026_06_30_000010_create_faqs_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question', 500);
            $table->text('answer');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
```

- [ ] **Step 4: Create the model**

Create `Modules/Ecommerce/app/Models/Faq.php`:

```php
<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = ['question', 'answer', 'position', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'position'  => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=FaqTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add Modules/Ecommerce/database/migrations/2026_06_30_000010_create_faqs_table.php Modules/Ecommerce/app/Models/Faq.php Modules/Ecommerce/tests/Feature/FaqTest.php
git commit -m "feat(ecommerce): add faqs table and Faq model"
```

---

## Task 2: FAQ admin CRUD + reorder + toggle

**Files:**
- Create: `Modules/Ecommerce/app/Http/Requests/StoreFaqRequest.php`
- Create: `Modules/Ecommerce/app/Http/Controllers/FaqController.php`
- Create: `Modules/Ecommerce/resources/views/faqs/index.blade.php`, `create.blade.php`, `edit.blade.php`
- Modify: `Modules/Ecommerce/routes/web.php`
- Test: `Modules/Ecommerce/tests/Feature/FaqTest.php`

**Interfaces:**
- Consumes: `Faq` model from Task 1.
- Produces: named routes `ecommerce.faqs.index|create|store|edit|update|destroy|toggle-status|reorder`.

- [ ] **Step 1: Write the failing test (append to FaqTest)**

Add these methods to `Modules/Ecommerce/tests/Feature/FaqTest.php`:

```php
    public function test_admin_can_create_faq(): void
    {
        $response = $this->actingAs($this->admin)->post(route('ecommerce.faqs.store'), [
            'question' => 'How do I track my order?',
            'answer'   => 'Visit My Account → Orders.',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('ecommerce.faqs.index'));
        $this->assertDatabaseHas('faqs', ['question' => 'How do I track my order?']);
    }

    public function test_create_requires_question_and_answer(): void
    {
        $this->actingAs($this->admin)->post(route('ecommerce.faqs.store'), [])
            ->assertSessionHasErrors(['question', 'answer']);
    }

    public function test_admin_can_reorder_faqs(): void
    {
        $a = Faq::create(['question' => 'A', 'answer' => 'a', 'position' => 1]);
        $b = Faq::create(['question' => 'B', 'answer' => 'b', 'position' => 2]);

        $this->actingAs($this->admin)->post(route('ecommerce.faqs.reorder'), [
            'ordered_ids' => [$b->id, $a->id],
        ])->assertOk();

        $this->assertSame(1, $b->fresh()->position);
        $this->assertSame(2, $a->fresh()->position);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FaqTest`
Expected: FAIL — route `ecommerce.faqs.store` not defined.

- [ ] **Step 3: Create the Form Request**

Create `Modules/Ecommerce/app/Http/Requests/StoreFaqRequest.php`:

```php
<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question'  => 'required|string|max:500',
            'answer'    => 'required|string',
            'is_active' => 'boolean',
            'position'  => 'nullable|integer|min:0',
        ];
    }
}
```

- [ ] **Step 4: Create the controller**

Create `Modules/Ecommerce/app/Http/Controllers/FaqController.php`:

```php
<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\StoreFaqRequest;
use Modules\Ecommerce\Models\Faq;

class FaqController extends Controller
{
    public function index(): View
    {
        bpAuthorize('ecommerce.view');
        $faqs = Faq::ordered()->get();

        return view('ecommerce::faqs.index', compact('faqs'));
    }

    public function create(): View
    {
        bpAuthorize('ecommerce.create');

        return view('ecommerce::faqs.create');
    }

    public function store(StoreFaqRequest $request): RedirectResponse
    {
        bpAuthorize('ecommerce.create');
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['position'] = $data['position'] ?? ((int) Faq::max('position') + 1);

        Faq::create($data);

        return redirect()->route('ecommerce.faqs.index')->with('success', __('FAQ created successfully.'));
    }

    public function edit(Faq $faq): View
    {
        bpAuthorize('ecommerce.edit');

        return view('ecommerce::faqs.edit', compact('faq'));
    }

    public function update(StoreFaqRequest $request, Faq $faq): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $faq->update($data);

        return redirect()->route('ecommerce.faqs.index')->with('success', __('FAQ updated successfully.'));
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        bpAuthorize('ecommerce.delete');
        $faq->delete();

        return redirect()->route('ecommerce.faqs.index')->with('success', __('FAQ deleted successfully.'));
    }

    public function toggleStatus(Faq $faq, Request $request): JsonResponse|RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        $faq->update(['is_active' => ! $faq->is_active]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['is_active' => (bool) $faq->is_active, 'message' => __('FAQ status updated.')]);
        }

        return back()->with('success', __('FAQ status updated.'));
    }

    public function reorder(Request $request): JsonResponse
    {
        bpAuthorize('ecommerce.edit');
        $validated = $request->validate([
            'ordered_ids'   => ['required', 'array'],
            'ordered_ids.*' => ['integer', 'exists:faqs,id'],
        ]);

        foreach ($validated['ordered_ids'] as $index => $id) {
            Faq::where('id', $id)->update(['position' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }
}
```

- [ ] **Step 5: Register admin routes**

In `Modules/Ecommerce/routes/web.php`, add `use Modules\Ecommerce\Http\Controllers\FaqController;` to the imports, and inside the `Route::middleware('auth')->prefix('ecommerce')->name('ecommerce.')->group(...)` block (e.g. right after the Blog routes) add:

```php
    // FAQs
    Route::get('/faqs', [FaqController::class, 'index'])->name('faqs.index');
    Route::get('/faqs/create', [FaqController::class, 'create'])->name('faqs.create');
    Route::post('/faqs', [FaqController::class, 'store'])->name('faqs.store');
    Route::post('/faqs/reorder', [FaqController::class, 'reorder'])->name('faqs.reorder');
    Route::get('/faqs/{faq}/edit', [FaqController::class, 'edit'])->name('faqs.edit');
    Route::put('/faqs/{faq}', [FaqController::class, 'update'])->name('faqs.update');
    Route::delete('/faqs/{faq}', [FaqController::class, 'destroy'])->name('faqs.destroy');
    Route::patch('/faqs/{faq}/toggle-status', [FaqController::class, 'toggleStatus'])->name('faqs.toggle-status');
```

Note: declare `/faqs/create` and `/faqs/reorder` before `/faqs/{faq}/edit` so the literal segments are not captured as a `{faq}` binding.

- [ ] **Step 6: Create the index view**

Create `Modules/Ecommerce/resources/views/faqs/index.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'FAQs')
@section('page-title', 'FAQs')
@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>FAQs</span>
@endsection

@section('page-actions')
  <a href="{{ route('ecommerce.faqs.create') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-plus me-1"></i>Add FAQ</a>
@endsection

@section('content')
<div class="bp-card">
  <div class="bp-card-body p-0">
    <div class="bp-table-wrapper">
      <table class="bp-table">
        <thead>
          <tr>
            <th class="bp-reorder-col" title="{{ __('Drag to reorder') }}">&nbsp;</th>
            <th>Question</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody class="bp-reorderable" data-reorder-url="{{ route('ecommerce.faqs.reorder') }}">
          @forelse($faqs as $faq)
            <tr data-id="{{ $faq->id }}">
              <td class="bp-drag-handle" title="{{ __('Drag to reorder') }}"><i class="fa-solid fa-grip-vertical"></i></td>
              <td>{{ $faq->question }}</td>
              <td>
                <button type="button" class="bp-badge {{ $faq->is_active ? 'bp-badge-success' : 'bp-badge-danger' }} bp-faq-toggle"
                  data-url="{{ route('ecommerce.faqs.toggle-status', $faq) }}">
                  {{ $faq->is_active ? 'Active' : 'Inactive' }}
                </button>
              </td>
              <td class="text-end">
                <a href="{{ route('ecommerce.faqs.edit', $faq) }}" class="bp-btn bp-btn-sm bp-btn-outline"><i class="fa-solid fa-pen"></i></a>
                <form action="{{ route('ecommerce.faqs.destroy', $faq) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this FAQ?');">
                  @csrf @method('DELETE')
                  <button type="submit" class="bp-btn bp-btn-sm bp-btn-danger"><i class="fa-solid fa-trash"></i></button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="text-center p-4">No FAQs yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/sortablejs/Sortable.min.js') }}"></script>
<script>
'use strict';
$(function () {
  var $body = $('.bp-reorderable');
  if ($body.length && window.Sortable) {
    new Sortable($body[0], {
      handle: '.bp-drag-handle',
      animation: 150,
      onEnd: function () {
        var ids = $body.find('tr[data-id]').map(function () { return $(this).data('id'); }).get();
        $.post($body.data('reorder-url'), { ordered_ids: ids });
      }
    });
  }
  $('.bp-faq-toggle').on('click', function () {
    var $btn = $(this);
    $.ajax({ url: $btn.data('url'), type: 'PATCH' }).done(function (res) {
      $btn.toggleClass('bp-badge-success', res.is_active).toggleClass('bp-badge-danger', !res.is_active)
          .text(res.is_active ? 'Active' : 'Inactive');
    });
  });
});
</script>
@endpush
```

Note: confirm the SortableJS filename — `ls public/vendor/sortablejs/`. Use whatever the combo index view references (copy that exact `<script src>` line). If combos init Sortable from `public/js/app.js` instead of a per-page script, follow that pattern instead.

- [ ] **Step 7: Create the create/edit views**

Create `Modules/Ecommerce/resources/views/faqs/create.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Add FAQ')
@section('page-title', 'Add FAQ')
@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('ecommerce.faqs.index') }}">FAQs</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Add</span>
@endsection

@section('content')
<form action="{{ route('ecommerce.faqs.store') }}" method="POST">
  @csrf
  @include('ecommerce::faqs._form', ['faq' => null])
</form>
@endsection
```

Create `Modules/Ecommerce/resources/views/faqs/edit.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Edit FAQ')
@section('page-title', 'Edit FAQ')
@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('ecommerce.faqs.index') }}">FAQs</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Edit</span>
@endsection

@section('content')
<form action="{{ route('ecommerce.faqs.update', $faq) }}" method="POST">
  @csrf @method('PUT')
  @include('ecommerce::faqs._form', ['faq' => $faq])
</form>
@endsection
```

Create `Modules/Ecommerce/resources/views/faqs/_form.blade.php`:

```blade
<div class="bp-card">
  <div class="bp-card-body">
    <div class="row g-3">
      <div class="col-12">
        <label class="bp-form-label">Question *</label>
        <input type="text" name="question" class="bp-form-control @error('question') is-invalid @enderror"
               value="{{ old('question', $faq->question ?? '') }}" required>
        @error('question')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-12">
        <label class="bp-form-label">Answer *</label>
        <textarea name="answer" rows="6" class="bp-form-control @error('answer') is-invalid @enderror" required>{{ old('answer', $faq->answer ?? '') }}</textarea>
        @error('answer')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-12">
        <div class="form-check form-switch">
          <input type="hidden" name="is_active" value="0">
          <input type="checkbox" name="is_active" value="1" class="form-check-input"
                 {{ old('is_active', $faq->is_active ?? true) ? 'checked' : '' }}>
          <label class="form-check-label">Active</label>
        </div>
      </div>
    </div>
  </div>
  <div class="bp-card-footer text-end">
    <a href="{{ route('ecommerce.faqs.index') }}" class="bp-btn bp-btn-outline">Cancel</a>
    <button type="submit" class="bp-btn bp-btn-primary"><i class="fa-solid fa-save me-1"></i>Save</button>
  </div>
</div>
```

- [ ] **Step 8: Run the tests**

Run: `php artisan test --filter=FaqTest`
Expected: PASS (all 5 methods — scopes, create, validation, reorder).

- [ ] **Step 9: Commit**

```bash
git add Modules/Ecommerce/app/Http/Controllers/FaqController.php Modules/Ecommerce/app/Http/Requests/StoreFaqRequest.php Modules/Ecommerce/resources/views/faqs Modules/Ecommerce/routes/web.php Modules/Ecommerce/tests/Feature/FaqTest.php
git commit -m "feat(ecommerce): FAQ admin CRUD with drag-to-reorder and status toggle"
```

---

## Task 3: FAQ storefront page + menu/footer wiring

**Files:**
- Create: `Modules/Ecommerce/app/Http/Controllers/Storefront/FaqController.php`
- Create: `Modules/Ecommerce/resources/views/storefront/pages/faq/index.blade.php`
- Modify: `Modules/Ecommerce/routes/storefront.php`
- Modify: `Modules/Ecommerce/app/Models/Concerns/HasMenuRoutes.php`
- Modify: `Modules/Ecommerce/resources/views/storefront/partials/footer.blade.php`
- Test: `Modules/Ecommerce/tests/Feature/FaqTest.php`

**Interfaces:**
- Consumes: `Faq::active()->ordered()`.
- Produces: route `storefront.faq.index` at `/faq`; menu whitelist key `storefront.faq.index`.

- [ ] **Step 1: Write the failing test (append to FaqTest)**

```php
    public function test_storefront_faq_page_shows_active_faqs_in_order(): void
    {
        Faq::create(['question' => 'Second Q', 'answer' => 'A2', 'position' => 2, 'is_active' => true]);
        Faq::create(['question' => 'First Q', 'answer' => 'A1', 'position' => 1, 'is_active' => true]);
        Faq::create(['question' => 'Hidden Q', 'answer' => 'A', 'position' => 3, 'is_active' => false]);

        $response = $this->get(route('storefront.faq.index'));

        $response->assertOk()->assertSee('First Q')->assertSee('Second Q')->assertDontSee('Hidden Q');
        $response->assertSeeInOrder(['First Q', 'Second Q']);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FaqTest::test_storefront_faq_page_shows_active_faqs_in_order`
Expected: FAIL — route `storefront.faq.index` not defined.

- [ ] **Step 3: Create the storefront controller**

Create `Modules/Ecommerce/app/Http/Controllers/Storefront/FaqController.php`:

```php
<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Faq;
use Modules\Ecommerce\Support\BuildsSeo;

class FaqController extends Controller
{
    use BuildsSeo;

    public function index(): View
    {
        $faqs = Faq::active()->ordered()->get();
        $cartItems = session('cart', []);

        $seo = $this->staticPageSeo('faq', [
            'description' => 'Answers to frequently asked questions about ordering, shipping, returns, and payments.',
        ]);

        return view('ecommerce::storefront.pages.faq.index', compact('faqs', 'cartItems', 'seo'));
    }
}
```

Note: `staticPageSeo('faq', [...])` — confirm the `BuildsSeo::staticPageSeo` signature accepts a `(string $key, array $overrides)` like the shop usage (`$this->staticPageSeo('shop', ['description' => ...])`). It does (see `ShopController::index`). No SeoPage DB row is required; the override supplies the description.

- [ ] **Step 4: Register the storefront route**

In `Modules/Ecommerce/routes/storefront.php`, add the import
`use Modules\Ecommerce\Http\Controllers\Storefront\FaqController;` and, in the public
(no-auth) section near the Blog routes, add:

```php
// FAQ
Route::get('/faq', [FaqController::class, 'index'])->name('storefront.faq.index');
```

This MUST be above any future `/{slug}` catch-all (Task 6).

- [ ] **Step 5: Create the storefront view**

Create `Modules/Ecommerce/resources/views/storefront/pages/faq/index.blade.php`. Use the storefront layout the other storefront pages extend (open `storefront/pages/blog/index.blade.php` and copy its `@extends(...)` and section names exactly — do not guess the layout name):

```blade
@extends('ecommerce::storefront.layouts.app')

@section('content')
<section class="faq_section pt_70 pb_70">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <h1 class="faq_title mb_30">Frequently Asked Questions</h1>
        <div class="accordion bp-faq-accordion" id="faqAccordion">
          @forelse($faqs as $i => $faq)
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button {{ $i === 0 ? '' : 'collapsed' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#faq{{ $faq->id }}">
                  {{ $faq->question }}
                </button>
              </h2>
              <div id="faq{{ $faq->id }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}"
                   data-bs-parent="#faqAccordion">
                <div class="accordion-body">{{ $faq->answer }}</div>
              </div>
            </div>
          @empty
            <p>No FAQs available yet.</p>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
```

Note: the storefront uses Bootstrap, so the `.accordion` component works. Confirm the exact layout name from the blog view; replace `@extends(...)` accordingly. Add a `[data-theme="dark"]` block for `.bp-faq-accordion` in `public/css/style.css` only if the storefront has a dark theme (check whether other storefront pages carry dark overrides; the storefront may be light-only — match siblings).

- [ ] **Step 6: Add FAQ to the menu route whitelist**

In `Modules/Ecommerce/app/Models/Concerns/HasMenuRoutes.php`, add to the `menuRoutes()` array (after `'storefront.blog.index' => 'Blog',`):

```php
            'storefront.faq.index'         => 'FAQ',
```

- [ ] **Step 7: Fix the footer link**

In `Modules/Ecommerce/resources/views/storefront/partials/footer.blade.php`, replace:

```blade
<li><a href="#">FAQ's</a></li>
```

with:

```blade
<li><a href="{{ route('storefront.faq.index') }}">FAQ's</a></li>
```

- [ ] **Step 8: Run the full FAQ test file**

Run: `php artisan test --filter=FaqTest`
Expected: PASS (all methods).

- [ ] **Step 9: Commit**

```bash
git add Modules/Ecommerce/app/Http/Controllers/Storefront/FaqController.php Modules/Ecommerce/resources/views/storefront/pages/faq Modules/Ecommerce/routes/storefront.php Modules/Ecommerce/app/Models/Concerns/HasMenuRoutes.php Modules/Ecommerce/resources/views/storefront/partials/footer.blade.php Modules/Ecommerce/tests/Feature/FaqTest.php
git commit -m "feat(ecommerce): storefront FAQ accordion page + menu/footer link"
```

---

## Task 4: Page migration + model (auto-slug, reserved slugs)

**Files:**
- Create: `Modules/Ecommerce/database/migrations/2026_06_30_000011_create_pages_table.php`
- Create: `Modules/Ecommerce/app/Models/Page.php`
- Test: `Modules/Ecommerce/tests/Feature/PageTest.php`

**Interfaces:**
- Produces: `Modules\Ecommerce\Models\Page` with `$fillable = ['title','slug','content','is_published','seo_title','seo_description','seo_image']`, `SoftDeletes`, scope `published()`, const `RESERVED_SLUGS` (array), auto-slug-on-save.

- [ ] **Step 1: Write the failing test**

Create `Modules/Ecommerce/tests/Feature/PageTest.php`:

```php
<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Page;
use Tests\TestCase;

class PageTest extends TestCase
{
    public function test_slug_is_auto_generated_from_title_when_blank(): void
    {
        $page = Page::create(['title' => 'Privacy Policy', 'content' => '<p>Hi</p>']);

        $this->assertSame('privacy-policy', $page->slug);
    }

    public function test_published_scope_excludes_unpublished(): void
    {
        Page::create(['title' => 'Live', 'is_published' => true]);
        Page::create(['title' => 'Draft', 'is_published' => false]);

        $this->assertSame(['Live'], Page::published()->pluck('title')->all());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PageTest`
Expected: FAIL — `Class "Modules\Ecommerce\Models\Page" not found`.

- [ ] **Step 3: Create the migration**

Create `Modules/Ecommerce/database/migrations/2026_06_30_000011_create_pages_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->boolean('is_published')->default(true);
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 300)->nullable();
            $table->string('seo_image')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('is_published');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
```

- [ ] **Step 4: Create the model**

Create `Modules/Ecommerce/app/Models/Page.php`:

```php
<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Page extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'content', 'is_published',
        'seo_title', 'seo_description', 'seo_image',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    /**
     * Slugs that would collide with real storefront routes. A page may never
     * use one of these, so the /{slug} catch-all can only resolve to a
     * legitimately created page.
     */
    public const RESERVED_SLUGS = [
        'shop', 'cart', 'checkout', 'blog', 'category', 'categories',
        'flash-deals', 'combos', 'wishlist', 'compare', 'contact', 'customer',
        'search', 'faq', 'auth', 'page', 'pages', 'admin', 'ecommerce', 'api',
        'login', 'register', 'newsletter',
    ];

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            if (blank($page->slug)) {
                $page->slug = static::uniqueSlug(Str::slug($page->title));
            }
        });
    }

    protected static function uniqueSlug(string $base): string
    {
        $slug = $base ?: 'page';
        $i = 1;
        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=PageTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add Modules/Ecommerce/database/migrations/2026_06_30_000011_create_pages_table.php Modules/Ecommerce/app/Models/Page.php Modules/Ecommerce/tests/Feature/PageTest.php
git commit -m "feat(ecommerce): add pages table and Page model with auto-slug"
```

---

## Task 5: Page admin CRUD (rich text + SEO)

**Files:**
- Create: `Modules/Ecommerce/app/Http/Requests/StorePageRequest.php`
- Create: `Modules/Ecommerce/app/Http/Controllers/PageController.php`
- Create: `Modules/Ecommerce/resources/views/pages/index.blade.php`, `create.blade.php`, `edit.blade.php`, `_form.blade.php`
- Modify: `Modules/Ecommerce/routes/web.php`
- Test: `Modules/Ecommerce/tests/Feature/PageTest.php`

**Interfaces:**
- Consumes: `Page` model, `App\Helpers\Upload` (`Upload::store($file, $dir)`, `Upload::delete($path)`).
- Produces: routes `ecommerce.pages.index|create|store|edit|update|destroy|toggle-status`.

- [ ] **Step 1: Write the failing test (append to PageTest)**

```php
    public function test_admin_can_create_page(): void
    {
        $this->actingAs($this->admin)->post(route('ecommerce.pages.store'), [
            'title'   => 'About Us',
            'content' => '<p>Our story</p>',
            'is_published' => 1,
        ])->assertRedirect(route('ecommerce.pages.index'));

        $this->assertDatabaseHas('pages', ['slug' => 'about-us']);
    }

    public function test_reserved_slug_is_rejected(): void
    {
        $this->actingAs($this->admin)->post(route('ecommerce.pages.store'), [
            'title' => 'Hijack', 'slug' => 'shop', 'is_published' => 1,
        ])->assertSessionHasErrors('slug');

        $this->assertDatabaseMissing('pages', ['title' => 'Hijack']);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PageTest`
Expected: FAIL — route `ecommerce.pages.store` not defined.

- [ ] **Step 3: Create the Form Request**

Create `Modules/Ecommerce/app/Http/Requests/StorePageRequest.php`:

```php
<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Ecommerce\Models\Page;

class StorePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pageId = $this->route('page')?->id;

        return [
            'title'   => 'required|string|max:255',
            'slug'    => [
                'nullable', 'string', 'max:255',
                Rule::unique('pages', 'slug')->ignore($pageId),
                Rule::notIn(Page::RESERVED_SLUGS),
            ],
            'content' => 'nullable|string',
            'is_published'    => 'boolean',
            'seo_title'       => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:300',
            'seo_image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }
}
```

- [ ] **Step 4: Create the controller**

Create `Modules/Ecommerce/app/Http/Controllers/PageController.php`:

```php
<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Helpers\Upload;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\StorePageRequest;
use Modules\Ecommerce\Models\Page;

class PageController extends Controller
{
    public function index(): View
    {
        bpAuthorize('ecommerce.view');
        $pages = Page::orderBy('title')->get();

        return view('ecommerce::pages.index', compact('pages'));
    }

    public function create(): View
    {
        bpAuthorize('ecommerce.create');

        return view('ecommerce::pages.create');
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        bpAuthorize('ecommerce.create');
        $data = $request->validated();
        $data['is_published'] = $request->boolean('is_published', false);

        if ($request->hasFile('seo_image')) {
            $data['seo_image'] = Upload::store($request->file('seo_image'), 'pages/seo');
        }

        Page::create($data);

        return redirect()->route('ecommerce.pages.index')->with('success', __('Page created successfully.'));
    }

    public function edit(Page $page): View
    {
        bpAuthorize('ecommerce.edit');

        return view('ecommerce::pages.edit', compact('page'));
    }

    public function update(StorePageRequest $request, Page $page): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        $data = $request->validated();
        $data['is_published'] = $request->boolean('is_published', false);

        if ($request->hasFile('seo_image')) {
            Upload::delete($page->seo_image);
            $data['seo_image'] = Upload::store($request->file('seo_image'), 'pages/seo');
        } else {
            unset($data['seo_image']);
        }

        $page->update($data);

        return redirect()->route('ecommerce.pages.index')->with('success', __('Page updated successfully.'));
    }

    public function destroy(Page $page): RedirectResponse
    {
        bpAuthorize('ecommerce.delete');
        $page->delete();

        return redirect()->route('ecommerce.pages.index')->with('success', __('Page deleted successfully.'));
    }

    public function toggleStatus(Page $page, Request $request): JsonResponse|RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        $page->update(['is_published' => ! $page->is_published]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['is_published' => (bool) $page->is_published, 'message' => __('Page status updated.')]);
        }

        return back()->with('success', __('Page status updated.'));
    }
}
```

Note: confirm `App\Helpers\Upload::store(UploadedFile, string $dir): string` and `Upload::delete(?string): void` signatures by opening `app/Helpers/Upload.php` (the blog controller uses exactly `Upload::store($request->file('seo_image'), 'blog/seo')` and `Upload::delete($post->seo_image)`).

- [ ] **Step 5: Register admin routes**

In `Modules/Ecommerce/routes/web.php`, add `use Modules\Ecommerce\Http\Controllers\PageController;` and inside the `ecommerce.` group (after the FAQ routes) add:

```php
    // Custom Pages
    Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
    Route::get('/pages/create', [PageController::class, 'create'])->name('pages.create');
    Route::post('/pages', [PageController::class, 'store'])->name('pages.store');
    Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
    Route::put('/pages/{page}', [PageController::class, 'update'])->name('pages.update');
    Route::delete('/pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');
    Route::patch('/pages/{page}/toggle-status', [PageController::class, 'toggleStatus'])->name('pages.toggle-status');
```

- [ ] **Step 6: Create the index view**

Create `Modules/Ecommerce/resources/views/pages/index.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Pages')
@section('page-title', 'Pages')
@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Pages</span>
@endsection

@section('page-actions')
  <a href="{{ route('ecommerce.pages.create') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-plus me-1"></i>Add Page</a>
@endsection

@section('content')
<div class="bp-card">
  <div class="bp-card-body p-0">
    <div class="bp-table-wrapper">
      <table class="bp-table">
        <thead><tr><th>Title</th><th>URL</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
          @forelse($pages as $page)
            <tr>
              <td>{{ $page->title }}</td>
              <td><a href="{{ route('storefront.page.show', $page->slug) }}" target="_blank">/{{ $page->slug }}</a></td>
              <td><span class="bp-badge {{ $page->is_published ? 'bp-badge-success' : 'bp-badge-dark' }}">{{ $page->is_published ? 'Published' : 'Draft' }}</span></td>
              <td class="text-end">
                <a href="{{ route('ecommerce.pages.edit', $page) }}" class="bp-btn bp-btn-sm bp-btn-outline"><i class="fa-solid fa-pen"></i></a>
                <form action="{{ route('ecommerce.pages.destroy', $page) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this page?');">
                  @csrf @method('DELETE')
                  <button type="submit" class="bp-btn bp-btn-sm bp-btn-danger"><i class="fa-solid fa-trash"></i></button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="text-center p-4">No pages yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
```

- [ ] **Step 7: Create create/edit/_form views**

Create `Modules/Ecommerce/resources/views/pages/create.blade.php`:

```blade
@extends('layouts.app')
@section('title', 'Add Page')
@section('page-title', 'Add Page')
@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('ecommerce.pages.index') }}">Pages</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span><span>Add</span>
@endsection
@section('content')
<form action="{{ route('ecommerce.pages.store') }}" method="POST" enctype="multipart/form-data">
  @csrf
  @include('ecommerce::pages._form', ['page' => null])
</form>
@endsection
```

Create `Modules/Ecommerce/resources/views/pages/edit.blade.php`:

```blade
@extends('layouts.app')
@section('title', 'Edit Page')
@section('page-title', 'Edit Page')
@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('ecommerce.pages.index') }}">Pages</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span><span>Edit</span>
@endsection
@section('content')
<form action="{{ route('ecommerce.pages.update', $page) }}" method="POST" enctype="multipart/form-data">
  @csrf @method('PUT')
  @include('ecommerce::pages._form', ['page' => $page])
</form>
@endsection
```

Create `Modules/Ecommerce/resources/views/pages/_form.blade.php`:

```blade
<div class="bp-card">
  <div class="bp-card-body">
    <div class="row g-3">
      <div class="col-md-8">
        <label class="bp-form-label">Title *</label>
        <input type="text" name="title" class="bp-form-control @error('title') is-invalid @enderror"
               value="{{ old('title', $page->title ?? '') }}" required>
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-4">
        <label class="bp-form-label">Slug</label>
        <input type="text" name="slug" class="bp-form-control @error('slug') is-invalid @enderror"
               value="{{ old('slug', $page->slug ?? '') }}" placeholder="auto from title">
        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-12">
        <label class="bp-form-label">Content</label>
        <textarea name="content" class="bp-form-control bp-richtext" rows="12">{{ old('content', $page->content ?? '') }}</textarea>
      </div>
      <div class="col-12">
        <div class="form-check form-switch">
          <input type="hidden" name="is_published" value="0">
          <input type="checkbox" name="is_published" value="1" class="form-check-input"
                 {{ old('is_published', $page->is_published ?? true) ? 'checked' : '' }}>
          <label class="form-check-label">Published</label>
        </div>
      </div>
    </div>
  </div>
  <div class="bp-card-header"><h5 class="bp-card-title"><i class="fa-solid fa-magnifying-glass me-2"></i>SEO</h5></div>
  <div class="bp-card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="bp-form-label">SEO Title</label>
        <input type="text" name="seo_title" class="bp-form-control" value="{{ old('seo_title', $page->seo_title ?? '') }}">
      </div>
      <div class="col-md-6">
        <label class="bp-form-label">SEO Image</label>
        <input type="file" name="seo_image" class="bp-form-control" accept="image/*">
      </div>
      <div class="col-12">
        <label class="bp-form-label">SEO Description</label>
        <textarea name="seo_description" rows="2" class="bp-form-control" maxlength="300">{{ old('seo_description', $page->seo_description ?? '') }}</textarea>
      </div>
    </div>
  </div>
  <div class="bp-card-footer text-end">
    <a href="{{ route('ecommerce.pages.index') }}" class="bp-btn bp-btn-outline">Cancel</a>
    <button type="submit" class="bp-btn bp-btn-primary"><i class="fa-solid fa-save me-1"></i>Save</button>
  </div>
</div>
```

Note: `class="bp-richtext"` auto-loads TinyMCE via `public/js/bp-richtext.js` — confirm `bp-richtext.js` is included by the admin layout (`layouts.app`). The blog post create view relies on the same; grep `bp-richtext` in `Modules/Core/resources/views/layouts/*` or confirm blog-post-create renders an editor live. If the script tag isn't global, copy whatever `@push('scripts')`/asset include the blog-post-create view uses.

- [ ] **Step 8: Run the tests**

Run: `php artisan test --filter=PageTest`
Expected: PASS (auto-slug, published scope, create, reserved slug).

- [ ] **Step 9: Commit**

```bash
git add Modules/Ecommerce/app/Http/Controllers/PageController.php Modules/Ecommerce/app/Http/Requests/StorePageRequest.php Modules/Ecommerce/resources/views/pages Modules/Ecommerce/routes/web.php Modules/Ecommerce/tests/Feature/PageTest.php
git commit -m "feat(ecommerce): custom page admin CRUD with rich-text body and SEO"
```

---

## Task 6: Page storefront catch-all route + view

**Files:**
- Create: `Modules/Ecommerce/app/Http/Controllers/Storefront/PageController.php`
- Create: `Modules/Ecommerce/resources/views/storefront/pages/page/show.blade.php`
- Modify: `Modules/Ecommerce/routes/storefront.php` (catch-all LAST)
- Test: `Modules/Ecommerce/tests/Feature/PageTest.php`

**Interfaces:**
- Consumes: `Page::published()->where('slug', $slug)`.
- Produces: route `storefront.page.show` (param `slug`).

- [ ] **Step 1: Write the failing test (append to PageTest)**

```php
    public function test_published_page_renders_on_storefront(): void
    {
        Page::create(['title' => 'Privacy Policy', 'slug' => 'privacy-policy',
            'content' => '<p>We respect your privacy.</p>', 'is_published' => true]);

        $this->get('/privacy-policy')->assertOk()
            ->assertSee('Privacy Policy')->assertSee('We respect your privacy.');
    }

    public function test_unpublished_page_returns_404(): void
    {
        Page::create(['title' => 'Secret', 'slug' => 'secret-page', 'is_published' => false]);

        $this->get('/secret-page')->assertNotFound();
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->get('/no-such-page')->assertNotFound();
    }

    public function test_catch_all_does_not_shadow_real_routes(): void
    {
        $this->get('/shop')->assertOk(); // ShopController, not PageController
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PageTest::test_published_page_renders_on_storefront`
Expected: FAIL — 404 (route `storefront.page.show` not defined).

- [ ] **Step 3: Create the storefront controller**

Create `Modules/Ecommerce/app/Http/Controllers/Storefront/PageController.php`:

```php
<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Page;
use Modules\Ecommerce\Support\BuildsSeo;
use Modules\Ecommerce\Support\Seo;

class PageController extends Controller
{
    use BuildsSeo;

    public function show(string $slug): View
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();
        $cartItems = session('cart', []);

        $seo = Seo::make()
            ->title($page->seo_title ?: $page->title)
            ->description($page->seo_description
                ?: Str::limit(strip_tags($page->content ?? ''), 160))
            ->image($page->seo_image)
            ->breadcrumbs([
                ['name' => 'Home', 'url' => route('storefront.home')],
                ['name' => $page->title, 'url' => null],
            ]);
        $seo->canonical(route('storefront.page.show', $page->slug));

        return view('ecommerce::storefront.pages.page.show', compact('page', 'cartItems', 'seo'));
    }
}
```

Note: confirm `Seo::make()` chain methods (`title/description/image/breadcrumbs/canonical`) — all used verbatim in `ShopController::show` and `BlogController::show`. If `image(null)` errors on null, guard with `if ($page->seo_image) { $seo->image($page->seo_image); }` (mirror blog which always passes a value).

- [ ] **Step 4: Register the catch-all route (LAST in file)**

In `Modules/Ecommerce/routes/storefront.php`, add the import
`use Modules\Ecommerce\Http\Controllers\Storefront\PageController;` at the top, and add this as the **very last route in the file**, after the closing `});` of the authenticated customer group:

```php
// Custom CMS pages — MUST be the last storefront route so it never shadows a
// real route. Single path segment only (alnum + hyphen); reserved slugs can
// never be saved (StorePageRequest), so this only resolves to real pages.
Route::get('/{slug}', [PageController::class, 'show'])
    ->where('slug', '[A-Za-z0-9\-]+')
    ->name('storefront.page.show');
```

- [ ] **Step 5: Create the storefront view**

Create `Modules/Ecommerce/resources/views/storefront/pages/page/show.blade.php` (copy the `@extends(...)` line from the blog show view so the layout matches):

```blade
@extends('ecommerce::storefront.layouts.app')

@section('content')
<section class="cms_page_section pt_70 pb_70">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-9">
        <h1 class="cms_page_title mb_30">{{ $page->title }}</h1>
        <div class="cms_page_content">
          {!! strip_tags($page->content, '<p><br><strong><em><ul><ol><li><h2><h3><h4><h5><h6><a><img><table><thead><tbody><tr><td><th><blockquote><span><div>') !!}
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
```

Note: this `strip_tags` whitelist is identical to the blog show view (`storefront/pages/blog/show.blade.php`) — keep them in sync.

- [ ] **Step 6: Run the tests**

Run: `php artisan test --filter=PageTest`
Expected: PASS (all methods, including `test_catch_all_does_not_shadow_real_routes`).

- [ ] **Step 7: Run the full Ecommerce suite to check for route-order regressions**

Run: `php artisan test --filter=Ecommerce`
Expected: PASS — no existing storefront test breaks from the catch-all.

- [ ] **Step 8: Commit**

```bash
git add Modules/Ecommerce/app/Http/Controllers/Storefront/PageController.php Modules/Ecommerce/resources/views/storefront/pages/page Modules/Ecommerce/routes/storefront.php Modules/Ecommerce/tests/Feature/PageTest.php
git commit -m "feat(ecommerce): storefront custom page rendering via /{slug} catch-all"
```

---

## Task 7: Menu Builder `page` item type

**Files:**
- Modify: `Modules/Ecommerce/app/Models/MenuItem.php`
- Modify: `Modules/Ecommerce/app/Http/Requests/StoreMenuItemRequest.php`
- Modify: `Modules/Ecommerce/app/Http/Controllers/MenuController.php`
- Modify: `Modules/Ecommerce/resources/views/menus/builder.blade.php`
- Test: `Modules/Ecommerce/tests/Feature/PageTest.php`

**Interfaces:**
- Consumes: `Page` model; `MenuItem::TYPES`, `MenuItem::resolveUrl()`.
- Produces: a `page` menu item type resolving to `route('storefront.page.show', $slug)`.

- [ ] **Step 1: Write the failing test (append to PageTest)**

```php
    public function test_menu_item_page_type_resolves_to_page_url(): void
    {
        $page = Page::create(['title' => 'Terms', 'slug' => 'terms', 'is_published' => true]);

        $menu = \Modules\Ecommerce\Models\Menu::create(['name' => 'Footer', 'location' => 'footer-test', 'is_active' => true]);
        $item = \Modules\Ecommerce\Models\MenuItem::create([
            'menu_id' => $menu->id, 'label' => 'Terms', 'type' => 'page', 'value' => (string) $page->id,
        ]);

        $this->assertSame(route('storefront.page.show', 'terms'), $item->resolveUrl());
    }
```

Note: confirm the `menus` table columns (`name`, `location`, `is_active`) by checking the Menu model `$fillable` / its migration before running; adjust the `Menu::create([...])` payload to match. If a `location` value must be unique, the literal `'footer-test'` avoids colliding with seeded menus.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PageTest::test_menu_item_page_type_resolves_to_page_url`
Expected: FAIL — `resolveUrl()` returns `null` (type `page` not handled).

- [ ] **Step 3: Add `page` to MenuItem TYPES + resolveUrl + slug cache**

In `Modules/Ecommerce/app/Models/MenuItem.php`:

Add `'page'` to the `TYPES` const:

```php
    public const TYPES = ['route', 'category', 'page', 'url', 'heading', 'categories_dropdown', 'widget'];
```

Add a page-slug cache property near the existing `$categorySlugCache`:

```php
    /** In-request cache of page id → slug, to avoid N+1 in resolveUrl(). */
    protected static array $pageSlugCache = [];
```

Add a `page` arm to `resolveUrl()`'s `match`, right after the `category` arm:

```php
            'page' => ($slug = static::pageSlug((int) $this->value))
                ? route('storefront.page.show', $slug)
                : '#',
```

Add the resolver method next to `categorySlug()`:

```php
    protected static function pageSlug(int $id): ?string
    {
        if (! array_key_exists($id, static::$pageSlugCache)) {
            static::$pageSlugCache[$id] = \Modules\Ecommerce\Models\Page::published()->whereKey($id)->value('slug');
        }

        return static::$pageSlugCache[$id];
    }
```

- [ ] **Step 4: Validate the `page` type in StoreMenuItemRequest**

In `Modules/Ecommerce/app/Http/Requests/StoreMenuItemRequest.php`, add a `page` arm to the `match ($type)` inside `withValidator`'s closure (after the `category` arm):

```php
                'page' => \Modules\Ecommerce\Models\Page::whereKey($value)->exists()
                    ?: $v->errors()->add('value', 'Selected page does not exist.'),
```

- [ ] **Step 5: Run the menu test to verify it passes**

Run: `php artisan test --filter=PageTest::test_menu_item_page_type_resolves_to_page_url`
Expected: PASS.

- [ ] **Step 6: Pass pages to the builder view**

In `Modules/Ecommerce/app/Http/Controllers/MenuController.php`, find the `edit()` method that returns the `menus.builder` view (it currently passes `$routes`, `$categories`, `$widgets`). Add published pages to the payload:

```php
        $pages = \Modules\Ecommerce\Models\Page::published()->orderBy('title')->get(['id', 'title']);
```

and include `'pages' => $pages` (or `compact(..., 'pages')`) in the `view('ecommerce::menus.builder', [...])` call. Confirm the exact variable assembly by reading the method first.

- [ ] **Step 7: Add the page picker to the builder view**

In `Modules/Ecommerce/resources/views/menus/builder.blade.php`:

Add an option to the type `<select name="type" id="addItemType">` (after the `category` option):

```blade
                            <option value="page">Custom Page</option>
```

Add a type-specific value field (mirror the existing `value_category` block), inside the type-specific fields area:

```blade
                    <div class="add-field" data-for="page">
                        <label class="bp-form-label">Page</label>
                        <select class="bp-form-select w-100" name="value_page">
                            @foreach($pages as $p)<option value="{{ $p->id }}">{{ $p->title }}</option>@endforeach
                        </select>
                    </div>
```

In the builder's `<script>` block, extend the value-copy logic (the `if (t === 'route') {...} else if (t === 'category') {...}` chain around line 209) to handle `page`:

```javascript
        else if (t === 'page') { val = $('select[name="value_page"]').val(); }
```

Also ensure `syncAddFields()` shows the `data-for="page"` field when type is `page` — it follows the same `data-for` convention as the other fields, so if that function toggles by `[data-for="<type>"]` it works automatically. Read `syncAddFields` to confirm; if it uses an explicit per-type list, add `'page'` to it.

- [ ] **Step 8: Run the full suite**

Run: `php artisan test --filter=Ecommerce`
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add Modules/Ecommerce/app/Models/MenuItem.php Modules/Ecommerce/app/Http/Requests/StoreMenuItemRequest.php Modules/Ecommerce/app/Http/Controllers/MenuController.php Modules/Ecommerce/resources/views/menus/builder.blade.php Modules/Ecommerce/tests/Feature/PageTest.php
git commit -m "feat(ecommerce): add 'page' Menu Builder item type linking custom pages"
```

---

## Task 8: Admin sidebar links for FAQs + Pages

**Files:**
- Modify: `Modules/Core/resources/views/partials/sidebar.blade.php`

**Interfaces:**
- Consumes: routes `ecommerce.faqs.index`, `ecommerce.pages.index`.

- [ ] **Step 1: Add the nav links**

In `Modules/Core/resources/views/partials/sidebar.blade.php`, in the Ecommerce content group (right after the Blog-related `<li>` items near line 350), add:

```blade
        <li><a href="{{ route('ecommerce.faqs.index') }}" class="menu-link {{ request()->routeIs('ecommerce.faqs.*') ? 'active' : '' }}">FAQs</a></li>
        <li><a href="{{ route('ecommerce.pages.index') }}" class="menu-link {{ request()->routeIs('ecommerce.pages.*') ? 'active' : '' }}">Pages</a></li>
```

- [ ] **Step 2: Verify the links resolve (no test; manual route check)**

Run: `php artisan route:list --name=ecommerce.faqs && php artisan route:list --name=ecommerce.pages`
Expected: both route groups listed (index/create/store/edit/update/destroy/toggle-status).

- [ ] **Step 3: Commit**

```bash
git add Modules/Core/resources/views/partials/sidebar.blade.php
git commit -m "feat(ecommerce): add FAQs and Pages links to admin sidebar"
```

---

## Task 9: Full verification sweep

- [ ] **Step 1: Run the complete Ecommerce test suite**

Run: `php artisan test --filter=Ecommerce`
Expected: all green, including `FaqTest` and `PageTest`.

- [ ] **Step 2: Manual smoke test (dev server)**

```bash
php artisan serve --host=127.0.0.1 --port=8000 &
```

Verify (after admin login at `/admin/login`):
- `/ecommerce/faqs` — add, reorder (drag), toggle, edit, delete.
- `/faq` — accordion shows active FAQs in order.
- `/ecommerce/pages` — create "Privacy Policy" with rich-text body; TinyMCE loads.
- `/privacy-policy` — renders the body; `/no-such-page` → 404; `/shop` still works.
- Menu Builder — add a "Custom Page" item; confirm it links to `/{slug}`.

Stop the server when done.

- [ ] **Step 3: Static-content / convention scan (per CLAUDE.md)**

```bash
grep -rn "style=" Modules/Ecommerce/resources/views/faqs Modules/Ecommerce/resources/views/pages Modules/Ecommerce/resources/views/storefront/pages/faq Modules/Ecommerce/resources/views/storefront/pages/page
```
Expected: no inline `style="..."` (move any to `public/css/style.css`).

- [ ] **Step 4: Final commit (if any cleanup)**

```bash
git add -A
git commit -m "chore(ecommerce): FAQ + custom pages polish and convention fixes"
```

---

## Self-Review Notes (coverage check)

- Spec §FAQ data/model/admin/storefront → Tasks 1–3. ✅
- Spec §Pages data/model/admin/storefront/catch-all → Tasks 4–6. ✅
- Spec §Menu `page` type → Task 7. ✅
- Spec §Cross-cutting sidebar → Task 8; dark-mode/responsive folded into each view step; icons noted. ✅
- Spec §Security: reserved-slug validation (Task 5), catch-all last + single-segment constraint (Task 6), strip_tags render (Task 6), seo_image validated+Upload::store (Task 5), auth/CSRF (all admin tasks). ✅
- Spec §Testing matrix items 1–7 → covered across FaqTest/PageTest. ✅
- Out-of-scope items (FAQ categories, slug history, page templates, FAQ WYSIWYG) correctly omitted. ✅

**Assumptions flagged for the implementer to confirm against live code (cheap reads, noted inline):** storefront layout name (`ecommerce::storefront.layouts.app`), `bp-richtext.js` inclusion in `layouts.app`, SortableJS init pattern (per-page vs app.js), `Upload::store/delete` signatures, `Seo::make()->image(null)` tolerance, `menus` table columns, and `MenuController::edit` view payload assembly. Each note says exactly which existing file to copy the answer from.
