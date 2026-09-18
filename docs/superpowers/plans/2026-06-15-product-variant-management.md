# Product Variant Management on Edit — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let admins remove variant attribute values (block if sold), add existing values, globally enable/disable a value, and have per-variant Active/Default/price/stock persist — fixing the additive-only generation bug.

**Architecture:** Variants are managed by dedicated AJAX endpoints (not the product form). We add a **surgical removal** path (`removeProductValues`) + a **sales guard** on deletes, a global **`is_active`** flag on `variant_attribute_values` with a dedicated toggle endpoint, and wire the edit-page JS to call removal on deselect/group-× and to handle the block-if-sold 422. We do NOT touch `ProductController::update()`.

**Tech Stack:** Laravel 12, Eloquent, Blade, jQuery, MySQL. Modules: `Product`, `Variant`, `Inventory`.

**Spec:** `docs/superpowers/specs/2026-06-15-product-variant-management-design.md`

---

## File Structure

- **Create** `Modules/Variant/database/migrations/2026_06_15_000010_add_is_active_to_variant_attribute_values_table.php` — global value on/off column.
- **Create** `Modules/Variant/app/Exceptions/VariantHasSalesException.php` — domain exception for block-if-sold.
- **Modify** `Modules/Variant/app/Models/VariantAttributeValue.php` — `is_active` fillable/cast/scope.
- **Modify** `Modules/Variant/app/Services/VariantService.php` — `variantHasSales`, `removeProductValues`, hardened `deleteVariant`, `reassignDefault`.
- **Modify** `Modules/Product/app/Http/Controllers/ProductController.php` — `removeVariantValues`, `destroyVariant` 422, `getVariantAttributes` active-only.
- **Modify** `Modules/Product/routes/web.php` — `remove-variant-values` route.
- **Modify** `Modules/Variant/app/Http/Controllers/VariantController.php` — `setValueActive`.
- **Modify** `Modules/Variant/routes/web.php` — value-active route.
- **Modify** `Modules/Variant/resources/views/edit.blade.php` — per-value Active switch + JS.
- **Modify** `Modules/Product/resources/views/edit.blade.php` — removal-on-deselect, group-× removal, per-row 422 handling, row `data-value-ids`.
- **Create** `Modules/Variant/tests/Feature/VariantRemovalTest.php` — service + endpoint tests.

---

## Task 1: Add `is_active` to attribute values (global on/off)

**Files:**
- Create: `Modules/Variant/database/migrations/2026_06_15_000010_add_is_active_to_variant_attribute_values_table.php`
- Modify: `Modules/Variant/app/Models/VariantAttributeValue.php`

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variant_attribute_values', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->index()->after('color_code');
        });
    }

    public function down(): void
    {
        Schema::table('variant_attribute_values', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
```

- [ ] **Step 2: Update the model** — add `is_active` to fillable, cast, and an `active` scope.

In `Modules/Variant/app/Models/VariantAttributeValue.php`:

```php
    protected $fillable = ['variant_attribute_id', 'value', 'color_code', 'is_active', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active'  => 'boolean',
    ];
```

And add below `scopeOrdered`:

```php
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
```

- [ ] **Step 3: Run the migration**

Run: `php artisan migrate --force`
Expected: `...add_is_active_to_variant_attribute_values_table ... DONE`

- [ ] **Step 4: Verify column + default**

Run: `php artisan tinker --execute="echo Schema::hasColumn('variant_attribute_values','is_active') ? 'yes '.\Modules\Variant\Models\VariantAttributeValue::where('is_active',true)->count().' active' : 'no';"`
Expected: `yes <N> active` (all existing values default active)

- [ ] **Step 5: Commit**

```bash
git add Modules/Variant/database/migrations/2026_06_15_000010_add_is_active_to_variant_attribute_values_table.php Modules/Variant/app/Models/VariantAttributeValue.php
git commit -m "feat(variant): add is_active flag to attribute values"
```

---

## Task 2: `VariantHasSalesException`

**Files:**
- Create: `Modules/Variant/app/Exceptions/VariantHasSalesException.php`

- [ ] **Step 1: Create the exception**

```php
<?php

namespace Modules\Variant\Exceptions;

use RuntimeException;

class VariantHasSalesException extends RuntimeException
{
}
```

- [ ] **Step 2: Commit**

```bash
git add Modules/Variant/app/Exceptions/VariantHasSalesException.php
git commit -m "feat(variant): add VariantHasSalesException"
```

---

## Task 3: VariantService — removal, sales guard, default reassignment (TDD)

**Files:**
- Modify: `Modules/Variant/app/Services/VariantService.php`
- Test: `Modules/Variant/tests/Feature/VariantRemovalTest.php`

- [ ] **Step 1: Write the failing test**

Create `Modules/Variant/tests/Feature/VariantRemovalTest.php`:

```php
<?php

namespace Modules\Variant\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Category\Models\Category;
use Modules\Inventory\Models\WarehouseStock;
use Modules\Product\Models\Product;
use Modules\Sale\Models\SaleItem;
use Modules\Unit\Models\Unit;
use Modules\Variant\Exceptions\VariantHasSalesException;
use Modules\Variant\Models\ProductVariant;
use Modules\Variant\Models\VariantAttribute;
use Modules\Variant\Models\VariantAttributeValue;
use Modules\Variant\Services\VariantService;
use Tests\TestCase;

class VariantRemovalTest extends TestCase
{
    use RefreshDatabase;

    private VariantService $service;
    private Product $product;
    private VariantAttributeValue $red;
    private VariantAttributeValue $gold;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VariantService::class);

        $category = Category::create(['name' => 'Gen', 'slug' => 'gen', 'status' => 'active', 'sort_order' => 0]);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc', 'status' => 'active']);
        $this->product = Product::create([
            'name' => 'Shirt', 'slug' => 'shirt', 'sku' => 'SH-1',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'cost_price' => 100, 'sell_price' => 200, 'product_type' => 'variable',
            'status' => 'active', 'discount_type' => 'none', 'track_stock' => true,
        ]);

        $color = VariantAttribute::create(['name' => 'Color', 'display_name' => 'Color', 'display_type' => 'button', 'sort_order' => 0, 'status' => 'active']);
        $this->red  = $color->values()->create(['value' => 'Red',  'sort_order' => 0]);
        $this->gold = $color->values()->create(['value' => 'Gold', 'sort_order' => 1]);

        foreach ([$this->red, $this->gold] as $i => $val) {
            $v = ProductVariant::create([
                'product_id' => $this->product->id, 'sku' => 'SH-1-' . $val->value,
                'cost_price' => 100, 'sell_price' => 200, 'is_active' => true, 'is_default' => $i === 0,
            ]);
            $v->attributeValues()->attach($val->id);
            WarehouseStock::create(['product_id' => $this->product->id, 'variant_id' => $v->id, 'quantity' => 5]);
        }
    }

    public function test_removing_an_unused_value_deletes_its_variants_and_stock(): void
    {
        $this->service->removeProductValues($this->product, [$this->gold->id]);

        $this->assertSame(1, $this->product->variants()->count());
        $this->assertDatabaseMissing('product_variants', ['sku' => 'SH-1-Gold']);
        $this->assertSame(0, WarehouseStock::where('product_id', $this->product->id)
            ->whereNotIn('variant_id', $this->product->variants()->pluck('id'))->count());
        // Red (unrelated) survives
        $this->assertDatabaseHas('product_variants', ['sku' => 'SH-1-Red']);
    }

    public function test_removing_a_sold_value_throws_and_deletes_nothing(): void
    {
        $goldVariant = $this->product->variants()->where('sku', 'SH-1-Gold')->first();
        SaleItem::create([
            'sale_id' => 1, 'product_id' => $this->product->id, 'variant_id' => $goldVariant->id,
            'product_name' => 'Shirt', 'quantity' => 1, 'unit_price' => 200, 'subtotal' => 200,
        ]);

        $this->expectException(VariantHasSalesException::class);

        try {
            $this->service->removeProductValues($this->product, [$this->gold->id]);
        } finally {
            $this->assertSame(2, $this->product->variants()->count()); // nothing deleted
        }
    }

    public function test_removing_default_variant_reassigns_default(): void
    {
        // Red is default; remove Red → Gold becomes default
        $this->service->removeProductValues($this->product, [$this->red->id]);

        $gold = $this->product->variants()->where('sku', 'SH-1-Gold')->first();
        $this->assertTrue((bool) $gold->is_default);
    }
}
```

- [ ] **Step 2: Run it to confirm it fails**

Run: `php artisan test Modules/Variant/tests/Feature/VariantRemovalTest.php`
Expected: FAIL — `Call to undefined method ...::removeProductValues()`

- [ ] **Step 3: Implement the service methods**

In `Modules/Variant/app/Services/VariantService.php`, add the import near the top (after the existing `use` lines):

```php
use Modules\Variant\Exceptions\VariantHasSalesException;
```

Replace the existing `deleteVariant` method (currently `return $variant->delete();`) with the hardened version, and add the three helpers, all inside the class:

```php
    /**
     * Whether a variant has any sale_items referencing it.
     */
    private function variantHasSales(int $variantId): bool
    {
        return DB::table('sale_items')->where('variant_id', $variantId)->exists();
    }

    /**
     * Force-delete a product variant + its stock. Blocks if it has sales.
     *
     * @throws VariantHasSalesException
     */
    public function deleteVariant(ProductVariant $variant): bool
    {
        if ($this->variantHasSales($variant->id)) {
            throw new VariantHasSalesException(
                "Can't delete {$variant->sku} — it has sales. Disable it (uncheck Active) instead."
            );
        }

        $productId  = $variant->product_id;
        $wasDefault = (bool) $variant->is_default;

        return DB::transaction(function () use ($variant, $productId, $wasDefault) {
            \Modules\Inventory\Models\WarehouseStock::where('product_id', $productId)
                ->where('variant_id', $variant->id)->delete();
            $variant->attributeValues()->detach();
            $result = $variant->forceDelete();

            if ($wasDefault) {
                $this->reassignDefault($productId);
            }

            return (bool) $result;
        });
    }

    /**
     * Surgically remove a product's variants that contain ANY of the given
     * attribute values. All-or-nothing: if any matched variant has sales,
     * throws and deletes nothing. Unrelated variants are untouched.
     *
     * @throws VariantHasSalesException
     * @return array{removed:int}
     */
    public function removeProductValues(Product $product, array $valueIds): array
    {
        $valueIds = array_values(array_unique(array_map('intval', $valueIds)));

        $variants = $product->variants()
            ->whereHas('attributeValues', fn ($q) => $q->whereIn('variant_attribute_values.id', $valueIds))
            ->get();

        if ($variants->isEmpty()) {
            return ['removed' => 0];
        }

        $soldSkus = $variants->filter(fn ($v) => $this->variantHasSales($v->id))->pluck('sku')->all();
        if (!empty($soldSkus)) {
            throw new VariantHasSalesException(
                "Can't remove " . implode(', ', $soldSkus) .
                " — they have sales. Disable them (uncheck Active) instead."
            );
        }

        $productId  = $product->id;
        $wasDefault = $variants->contains(fn ($v) => $v->is_default);

        return DB::transaction(function () use ($variants, $productId, $wasDefault) {
            $ids = $variants->pluck('id')->all();
            \Modules\Inventory\Models\WarehouseStock::where('product_id', $productId)
                ->whereIn('variant_id', $ids)->delete();
            foreach ($variants as $v) {
                $v->attributeValues()->detach();
                $v->forceDelete();
            }
            if ($wasDefault) {
                $this->reassignDefault($productId);
            }

            return ['removed' => count($ids)];
        });
    }

    /**
     * Ensure the product has a default among its active variants. No-op if it
     * already does; otherwise promotes the first active variant.
     */
    private function reassignDefault(int $productId): void
    {
        $hasDefault = ProductVariant::where('product_id', $productId)
            ->where('is_active', true)->where('is_default', true)->exists();
        if ($hasDefault) {
            return;
        }
        $first = ProductVariant::where('product_id', $productId)
            ->where('is_active', true)->orderBy('id')->first();
        if ($first) {
            $first->update(['is_default' => true]);
        }
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test Modules/Variant/tests/Feature/VariantRemovalTest.php`
Expected: PASS (3 passed)

- [ ] **Step 5: Commit**

```bash
git add Modules/Variant/app/Services/VariantService.php Modules/Variant/tests/Feature/VariantRemovalTest.php
git commit -m "feat(variant): surgical value removal + sales-guarded delete + default reassignment"
```

---

## Task 4: Product endpoints — remove-values, destroy 422, active-only picker (TDD)

**Files:**
- Modify: `Modules/Product/app/Http/Controllers/ProductController.php`
- Modify: `Modules/Product/routes/web.php`
- Test: `Modules/Variant/tests/Feature/VariantRemovalTest.php` (append endpoint tests)

- [ ] **Step 1: Add the route**

In `Modules/Product/routes/web.php`, beside the existing `destroy-variant` route, add:

```php
    Route::delete('/{product}/variants/by-values', [ProductController::class, 'removeVariantValues'])->name('remove-variant-values');
```

- [ ] **Step 2: Write the failing endpoint test**

Append to `VariantRemovalTest.php` (inside the class). It logs in an admin and hits the routes:

```php
    public function test_remove_values_endpoint_deletes_unused(): void
    {
        $admin = \App\Models\User::factory()->create();

        $this->actingAs($admin)
            ->deleteJson(route('products.remove-variant-values', $this->product), [
                'value_ids' => [$this->gold->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'removed' => 1]);

        $this->assertDatabaseMissing('product_variants', ['sku' => 'SH-1-Gold']);
    }

    public function test_remove_values_endpoint_blocks_sold_with_422(): void
    {
        $admin = \App\Models\User::factory()->create();
        $gold = $this->product->variants()->where('sku', 'SH-1-Gold')->first();
        SaleItem::create([
            'sale_id' => 1, 'product_id' => $this->product->id, 'variant_id' => $gold->id,
            'product_name' => 'Shirt', 'quantity' => 1, 'unit_price' => 200, 'subtotal' => 200,
        ]);

        $this->actingAs($admin)
            ->deleteJson(route('products.remove-variant-values', $this->product), [
                'value_ids' => [$this->gold->id],
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['message']);

        $this->assertSame(2, $this->product->variants()->count());
    }
```

> Note: if `User` has no factory in this codebase, replace `\App\Models\User::factory()->create()` with a direct `\App\Models\User::create([...])` using the columns the users table requires (check `database/migrations` for the users table). Keep the rest identical.

- [ ] **Step 3: Run it to confirm it fails**

Run: `php artisan test Modules/Variant/tests/Feature/VariantRemovalTest.php --filter=endpoint`
Expected: FAIL — route `products.remove-variant-values` not defined / 404

- [ ] **Step 4: Implement the controller method**

In `Modules/Product/app/Http/Controllers/ProductController.php`, add the import at the top with the other `use` statements:

```php
use Modules\Variant\Exceptions\VariantHasSalesException;
```

Add this method (e.g. right after `destroyVariant`):

```php
    /**
     * Remove all of a product's variants that contain any of the given
     * attribute values. Blocks (422) if any matched variant has sales.
     */
    public function removeVariantValues(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'value_ids'   => 'required|array|min:1',
            'value_ids.*' => 'integer|exists:variant_attribute_values,id',
        ]);

        $variantService = app(\Modules\Variant\Services\VariantService::class);

        try {
            $result = $variantService->removeProductValues($product, $validated['value_ids']);
        } catch (VariantHasSalesException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'removed' => $result['removed']]);
    }
```

- [ ] **Step 5: Harden `destroyVariant` to surface the sales block**

Replace the body of `destroyVariant` so the service exception maps to a 422:

```php
    public function destroyVariant(Product $product, int $variant): JsonResponse
    {
        $variantModel = \Modules\Variant\Models\ProductVariant::where('id', $variant)
            ->where('product_id', $product->id)
            ->firstOrFail();

        $variantService = app(\Modules\Variant\Services\VariantService::class);

        try {
            $variantService->deleteVariant($variantModel);
        } catch (VariantHasSalesException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Variant deleted successfully.']);
    }
```

- [ ] **Step 6: Filter the picker to active values**

In `getVariantAttributes`, change the eager load so disabled values are hidden:

```php
        $attributes = \Modules\Variant\Models\VariantAttribute::active()
            ->ordered()
            ->with(['values' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->withCount(['chartRows as active_chart_rows_count' => fn ($q) => $q->where('is_active', true)])
            ->get();
```

(Remove the old plain `->with('values')`.)

- [ ] **Step 7: Run tests to verify they pass**

Run: `php artisan test Modules/Variant/tests/Feature/VariantRemovalTest.php`
Expected: PASS (5 passed)

- [ ] **Step 8: Commit**

```bash
git add Modules/Product/app/Http/Controllers/ProductController.php Modules/Product/routes/web.php Modules/Variant/tests/Feature/VariantRemovalTest.php
git commit -m "feat(product): remove-variant-values endpoint + sales-guarded destroy + active-only picker"
```

---

## Task 5: Global value enable/disable toggle (Variants admin)

**Files:**
- Modify: `Modules/Variant/app/Http/Controllers/VariantController.php`
- Modify: `Modules/Variant/routes/web.php`
- Modify: `Modules/Variant/resources/views/edit.blade.php`

> Why a dedicated endpoint: `VariantService::updateAttribute()` deletes-and-recreates all values
> on every attribute save (churning IDs), so toggling via that form is unsafe. A standalone PATCH
> on a single value avoids the churn and takes effect instantly.

- [ ] **Step 1: Add the route**

In `Modules/Variant/routes/web.php`, inside the `variants` group, add:

```php
    Route::patch('/values/{value}/active', [VariantController::class, 'setValueActive'])->name('values.active');
```

- [ ] **Step 2: Add the controller method**

In `Modules/Variant/app/Http/Controllers/VariantController.php`, add:

```php
    public function setValueActive(\Illuminate\Http\Request $request, int $value): \Illuminate\Http\JsonResponse
    {
        $request->validate(['is_active' => 'required|boolean']);

        $val = \Modules\Variant\Models\VariantAttributeValue::findOrFail($value);
        $val->update(['is_active' => $request->boolean('is_active')]);

        return response()->json(['success' => true, 'is_active' => $val->is_active]);
    }
```

- [ ] **Step 3: Add an Active switch to each value row in the admin edit view**

In `Modules/Variant/resources/views/edit.blade.php`, in the values table header row add a column header `<th>Active</th>` (before the actions/remove column), and in the existing PHP-rendered value row (the `@foreach` near `name="values[{{ $index }}][color_code]"`), add a cell before the remove-button cell:

```blade
                  <td class="text-center">
                    <div class="form-check form-switch d-inline-flex justify-content-center mb-0">
                      <input class="form-check-input bp-value-active-cb" type="checkbox" role="switch"
                        data-value-id="{{ $value->id }}" {{ $value->is_active ? 'checked' : '' }}>
                    </div>
                  </td>
```

For dynamically-added (new, unsaved) value rows in the JS `rowHtml` builder, add a matching cell that is checked and disabled (new values are active on save; no id yet to toggle):

```javascript
      '<td class="text-center"><div class="form-check form-switch d-inline-flex justify-content-center mb-0"><input class="form-check-input" type="checkbox" role="switch" checked disabled></div></td>' +
```

- [ ] **Step 4: Wire the toggle JS (in the same view's `@push('scripts')`)**

```javascript
'use strict';
$(document).on('change', '.bp-value-active-cb', function () {
    var $cb = $(this);
    var id = $cb.data('value-id');
    $.ajax({
        url: '{{ route('variants.values.active', ['value' => ':vid']) }}'.replace(':vid', id),
        method: 'PATCH',
        data: { is_active: this.checked ? 1 : 0 },
        error: function () {
            alert('Failed to update value status.');
            $cb.prop('checked', !$cb.prop('checked'));
        }
    });
});
```

> CSRF is already set globally via `$.ajaxSetup` in the master layout, so no token needed here.

- [ ] **Step 5: Manual verification**

Run: `php artisan serve --host=127.0.0.1 --port=8000` (or use the running server)
Then, logged in as admin, open `admin/variants/{id}/edit` for the Color attribute, toggle "Gold" off, and confirm via:
Run: `php artisan tinker --execute="echo \Modules\Variant\Models\VariantAttributeValue::where('value','Gold')->value('is_active');"`
Expected: `0` (false) after toggling off; `1` after toggling on.

- [ ] **Step 6: Commit**

```bash
git add Modules/Variant/app/Http/Controllers/VariantController.php Modules/Variant/routes/web.php Modules/Variant/resources/views/edit.blade.php
git commit -m "feat(variant): per-value active toggle in attribute admin"
```

---

## Task 6: Edit-page UI — removal on deselect, group-×, per-row 422

**Files:**
- Modify: `Modules/Product/resources/views/edit.blade.php`

- [ ] **Step 1: Tag each variant row with its value ids**

In `renderVariantMatrix(variants)`, the `<tr ...>` is built around line 1279. Add a `data-value-ids` attribute so removal can filter rows client-side. Change the row opening tag to include the joined value ids from the variant's attributes:

```javascript
      var valueIds = (v.values || v.attributes || []).map(function(a) { return a.value_id; }).filter(function(x){ return x != null; }).join(',');
      var row = '<tr class="bp-variation-row' + (v.is_active ? '' : ' bp-variation-row-inactive') + '" data-variant-id="' + v.id + '" data-value-ids="' + valueIds + '">' +
```

> `getVariantMatrix` returns `attributes[].value_id`; the `generate` endpoint returns `values[]`
> without ids. To keep ids available after a generate, also add `'value_id' => $v->id` to the
> `values` map in `ProductController::generateVariants()`:
>
> ```php
>                     'values'      => $variant->attributeValues->map(fn($v) => [
>                         'attribute' => $v->attribute->name,
>                         'value'     => $v->value,
>                         'value_id'  => $v->id,
>                         'color_code'=> $v->color_code,
>                     ]),
> ```

- [ ] **Step 2: Add a shared removal helper (in the view's variant `@push('scripts')`)**

```javascript
  // Remove this product's variants that contain any of the given value ids.
  // Blocks (422) if any are sold; on success drops the matching rows.
  function removeVariantValues(valueIds, onSuccess, onBlocked) {
    $.ajax({
      url: '{{ route('products.remove-variant-values', $product) }}',
      method: 'DELETE',
      data: { value_ids: valueIds },
      success: function () {
        // Drop rows whose data-value-ids intersect the removed ids.
        $('#variationMatrixBody tr').each(function () {
          var rowIds = String($(this).data('value-ids') || '').split(',').map(Number);
          if (valueIds.some(function (id) { return rowIds.indexOf(Number(id)) !== -1; })) {
            $(this).remove();
          }
        });
        var cnt = $('#variationMatrixBody tr').length;
        document.getElementById('variantCountBadge').textContent = cnt + ' Variant' + (cnt !== 1 ? 's' : '');
        if (cnt === 0) $('#variationMatrixCard').prop('hidden', true);
        if (onSuccess) onSuccess();
      },
      error: function (xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to remove variants.';
        alert(msg);
        if (onBlocked) onBlocked();
      }
    });
  }
```

- [ ] **Step 3: Remove a value on picker confirm (diff old vs new)**

Replace the body of the `#addVariantAttrConfirm` click handler so it diffs the previous selection and removes deselected values server-side before generating the additive combos. The current handler (around line 1136) sets `selectedAttributes[attrId] = valueIds` then calls `generateAndRenderVariants()`. Change it to:

```javascript
  $('#addVariantAttrConfirm').on('click', function() {
    var attrId = parseInt($('#variantAttrSelect').val(), 10);
    if (!attrId) return;
    var valueIds = [];
    $('#variantValuesList input:checked').each(function() {
      valueIds.push(parseInt(this.dataset.valueId, 10));
    });
    if (valueIds.length === 0) { alert('Select at least one value.'); return; }

    var previous = selectedAttributes[attrId] || [];
    var removed = previous.filter(function (id) { return valueIds.indexOf(id) === -1; });

    function applySelection() {
      selectedAttributes[attrId] = valueIds;
      renderSelectedAttrs();
      $('#variantAttrPicker').addClass('d-none');
      populateAttrSelectDropdown();
      updateComboCount();
      syncSizeChart();
      generateAndRenderVariants(); // additive — creates new combos only
    }

    if (removed.length > 0) {
      removeVariantValues(removed, applySelection, null); // on block: keep current UI
    } else {
      applySelection();
    }
  });
```

- [ ] **Step 4: Remove an attribute (group ×) server-side**

Replace the `.remove-variant-attr` handler (around line 1196) to remove that attribute's variants before clearing the client state:

```javascript
  $(document).on('click', '.remove-variant-attr', function() {
    var attrId = parseInt($(this).data('attr-id'), 10);
    var valueIds = (selectedAttributes[attrId] || []).slice();

    function clearLocal() {
      delete selectedAttributes[attrId];
      renderSelectedAttrs();
      updateComboCount();
      syncSizeChart();
    }

    if (valueIds.length > 0) {
      removeVariantValues(valueIds, clearLocal, null);
    } else {
      clearLocal();
    }
  });
```

- [ ] **Step 5: Handle the sold-block on per-row delete**

Replace the `.var-del-btn` handler's `error` callback (around line 1378) so it shows the server message:

```javascript
      error: function(xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to delete variant.';
        alert(msg);
      }
```

- [ ] **Step 6: Manual verification (incognito not required — admin page)**

Run the server, open `admin/products/8/edit`:
1. Remove "Gold" via the attribute picker (uncheck + confirm) on a product where Gold has **no** sales → Gold rows disappear, stay gone after reload.
2. Try removing a value whose variant **has** sales → alert with the block message; rows remain.
3. Per-row trash on a sold variant → block message; on an unused variant → row removed.
4. Add a previously-removed value back → its combos regenerate.
5. Reload — confirm DB matches (no Gold variants):
   Run: `php artisan tinker --execute="echo \Modules\Variant\Models\ProductVariant::where('product_id',8)->whereHas('attributeValues',fn(\$q)=>\$q->where('value','Gold'))->count();"`
   Expected: `0` after removal.

- [ ] **Step 7: Commit**

```bash
git add Modules/Product/resources/views/edit.blade.php Modules/Product/app/Http/Controllers/ProductController.php
git commit -m "feat(product): edit page removes variants on value/attribute deselect, blocks if sold"
```

---

## Task 7: Final verification sweep

- [ ] **Step 1: Run the variant test suite**

Run: `php artisan test Modules/Variant/tests/Feature/VariantRemovalTest.php`
Expected: all green.

- [ ] **Step 2: Compile views (catch Blade errors)**

Run: `php artisan view:clear && php artisan view:cache && php artisan view:clear`
Expected: `Blade templates cached successfully.`

- [ ] **Step 3: Route smoke test (admin product edit 200)**

Use the QA login flow from CLAUDE.md (section 20), then:
Run: `curl -s -b /tmp/bz.txt http://127.0.0.1:8000/admin/products/8/edit -o /dev/null -w "%{http_code}\n"`
Expected: `200`

- [ ] **Step 4: Commit any remaining changes**

```bash
git status
# commit if anything is outstanding
```

---

## Spec coverage check

- Removal hard-delete unused / block-if-sold → Task 3 (`removeProductValues`, `deleteVariant`), Task 4 (endpoints).
- Add value (pick existing) → existing `generate` (additive), Task 6 picker flow.
- Global value on/off → Task 1 (column), Task 4 (active-only picker), Task 5 (admin toggle).
- Explicit-only, leave stale rows → surgical by-value removal (Task 3) never touches unrelated rows.
- Per-variant Active/Default/SKU/price/stock persist → already via `bulk-update`/`{v}/active` (unchanged).
- Default reassignment → Task 3 (`reassignDefault`).
