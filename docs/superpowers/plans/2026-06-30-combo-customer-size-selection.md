# Combo Customer-Selected Size — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let a customer choose one size for a whole combo; the chosen size resolves each component product to its matching size-variant, with correct stock and order flow.

**Architecture:** Add a `combos.size_required` toggle (admin-set). Sizes are auto-derived at runtime by `ComboService` as the intersection of the size-variant labels shared by every component product. The combo detail page renders a size selector (out-of-stock sizes disabled); add-to-cart resolves each component to its size-variant and stores the size on the cart line.

**Tech Stack:** Laravel 12 (modular), Blade, jQuery, MySQL, PHPUnit (`php artisan test`).

## Global Constraints

- Size token throughout is the size value **label** string (e.g. `M`, `L`, `XL`, `XXL`) — NOT the value id (robust across products that use different size-like attributes). Verbatim from refined spec.
- A "size-like attribute" is a `VariantAttribute` whose `name` is exactly `Size` OR matches `Size (%` OR `Size(%` (same rule as `ShopController::buildSizeChartsForProduct`).
- Price is size-independent — never alter `combo_price` based on size (spec A1).
- Variant match is by size label; if a product has multiple variants for a label, prefer the in-stock one (spec A2).
- `'use strict';` at the top of every `<script>` block. No inline `style=""`. Use named routes. (CLAUDE.md.)
- Variant ↔ value relation is `belongsToMany(VariantAttributeValue, 'product_variant_values')`.

---

### Task 1: Add `size_required` to combos (DB + model)

**Files:**
- Create: `Modules/Ecommerce/database/migrations/2026_06_30_100000_add_size_required_to_combos_table.php`
- Modify: `Modules/Ecommerce/app/Models/Combo.php` (fillable + casts)
- Test: `Modules/Ecommerce/tests/Feature/ComboModelTest.php` (append)

**Interfaces:**
- Produces: `Combo::$size_required` (bool, fillable, cast `boolean`), DB column `combos.size_required TINYINT(1) DEFAULT 0`.

- [ ] **Step 1: Write the failing test** — append to `ComboModelTest.php`:

```php
public function test_combo_size_required_defaults_false_and_is_fillable(): void
{
    $combo = \Modules\Ecommerce\Models\Combo::create([
        'name' => 'Sized Combo', 'combo_price' => 1000, 'is_active' => true,
    ]);
    $this->assertFalse((bool) $combo->fresh()->size_required);

    $combo->update(['size_required' => true]);
    $this->assertTrue($combo->fresh()->size_required);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/ComboModelTest.php --filter test_combo_size_required_defaults_false_and_is_fillable`
Expected: FAIL — `Unknown column 'size_required'` (or mass-assignment ignores it).

- [ ] **Step 3: Create the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('combos', function (Blueprint $table) {
            $table->boolean('size_required')->default(false)->after('combo_price');
        });
    }

    public function down(): void
    {
        Schema::table('combos', function (Blueprint $table) {
            $table->dropColumn('size_required');
        });
    }
};
```

- [ ] **Step 4: Update the model** — in `Combo.php`, add `'size_required'` to `$fillable` and `'size_required' => 'boolean'` to `$casts`:

```php
protected $fillable = [
    'name', 'slug', 'thumbnail', 'description', 'combo_price',
    'size_required',
    'discount_type', 'discount_value', 'is_active', 'sort_order',
];

protected $casts = [
    'combo_price'    => 'decimal:2',
    'discount_value' => 'decimal:2',
    'is_active'      => 'boolean',
    'size_required'  => 'boolean',
    'sort_order'     => 'integer',
];
```

- [ ] **Step 5: Migrate and run the test**

Run: `php artisan migrate --path=Modules/Ecommerce/database/migrations/2026_06_30_100000_add_size_required_to_combos_table.php --force && php artisan test Modules/Ecommerce/tests/Feature/ComboModelTest.php --filter test_combo_size_required_defaults_false_and_is_fillable`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add Modules/Ecommerce/database/migrations/2026_06_30_100000_add_size_required_to_combos_table.php Modules/Ecommerce/app/Models/Combo.php Modules/Ecommerce/tests/Feature/ComboModelTest.php
git commit -m "feat(combo): add size_required flag to combos"
```

---

### Task 2: ComboService size derivation + variant resolution

**Files:**
- Modify: `Modules/Ecommerce/app/Services/ComboService.php`
- Test: `Modules/Ecommerce/tests/Feature/ComboSizeTest.php` (create)

**Interfaces:**
- Produces:
  - `ComboService::productSizeVariants(\Modules\Product\Models\Product $product): array` → `['M' => ProductVariant, 'L' => ProductVariant, ...]` (active variants only; label = size-like attribute value).
  - `ComboService::availableSizes(Combo $combo): array` → ordered `[['value' => 'M', 'in_stock' => true], ...]` — labels shared by ALL components; `in_stock` false when any component can't fulfil that size for 1 unit.
  - `ComboService::resolveComponentVariant(ComboItem $item, string $sizeLabel): ?\Modules\Variant\Models\ProductVariant`.
  - `ComboService::isInStockForSize(Combo $combo, string $sizeLabel, int $qty = 1): bool`.

- [ ] **Step 1: Write the failing test** — create `ComboSizeTest.php`:

```php
<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Services\ComboService;
use Modules\Ecommerce\Tests\Concerns\CreatesComboTestData;
use Modules\Variant\Models\ProductVariant;
use Modules\Variant\Models\VariantAttribute;
use Modules\Variant\Models\VariantAttributeValue;
use Tests\TestCase;

class ComboSizeTest extends TestCase
{
    use RefreshDatabase, CreatesComboTestData;

    /** Build a product with size variants for the given labels. */
    private function productWithSizes(array $labels, array $valueIds): \Modules\Product\Models\Product
    {
        $product = $this->makeProduct(['track_stock' => false]);
        foreach ($labels as $i => $label) {
            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku'        => uniqid('v-'),
                'sell_price' => 100,
                'is_active'  => true,
            ]);
            $variant->attributeValues()->attach($valueIds[$label]);
        }
        return $product;
    }

    private function makeSizeValues(array $labels): array
    {
        $attr = VariantAttribute::create([
            'name' => 'Size', 'display_type' => 'button', 'status' => 'active', 'sort_order' => 1,
        ]);
        $ids = [];
        foreach ($labels as $i => $label) {
            $ids[$label] = VariantAttributeValue::create([
                'variant_attribute_id' => $attr->id, 'value' => $label,
                'is_active' => true, 'sort_order' => $i,
            ])->id;
        }
        return $ids;
    }

    public function test_available_sizes_is_the_intersection_across_components(): void
    {
        $values = $this->makeSizeValues(['M', 'L', 'XL']);
        $p1 = $this->productWithSizes(['M', 'L', 'XL'], $values);
        $p2 = $this->productWithSizes(['M', 'L'], $values); // no XL

        $combo = Combo::create(['name' => 'C', 'combo_price' => 500, 'is_active' => true, 'size_required' => true]);
        $combo->items()->create(['product_id' => $p1->id, 'quantity' => 1, 'sort_order' => 0]);
        $combo->items()->create(['product_id' => $p2->id, 'quantity' => 1, 'sort_order' => 1]);

        $sizes = app(ComboService::class)->availableSizes($combo->fresh('items.product.variants.attributeValues.attribute'));
        $labels = array_column($sizes, 'value');

        $this->assertEqualsCanonicalizing(['M', 'L'], $labels); // XL dropped (p2 lacks it)
    }

    public function test_resolve_component_variant_returns_the_matching_size_variant(): void
    {
        $values = $this->makeSizeValues(['M', 'L']);
        $p = $this->productWithSizes(['M', 'L'], $values);
        $combo = Combo::create(['name' => 'C', 'combo_price' => 500, 'is_active' => true, 'size_required' => true]);
        $item = $combo->items()->create(['product_id' => $p->id, 'quantity' => 1, 'sort_order' => 0]);

        $variant = app(ComboService::class)->resolveComponentVariant($item->fresh('product.variants.attributeValues.attribute'), 'L');

        $this->assertNotNull($variant);
        $this->assertTrue($variant->attributeValues->pluck('value')->contains('L'));
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/ComboSizeTest.php`
Expected: FAIL — `Call to undefined method ComboService::availableSizes()`.

- [ ] **Step 3: Implement the methods** — add to `ComboService.php` (after `componentAvailable()`):

```php
/** True for the size-like attributes (Size, Size (Shirt), …). */
private function isSizeLikeAttribute(\Modules\Variant\Models\VariantAttribute $attr): bool
{
    return $attr->name === 'Size'
        || \Illuminate\Support\Str::startsWith($attr->name, ['Size (', 'Size(']);
}

/** [sizeLabel => ProductVariant] for a product's active size variants. */
public function productSizeVariants(\Modules\Product\Models\Product $product): array
{
    $map = [];
    foreach ($product->variants as $variant) {
        if (! $variant->is_active) {
            continue;
        }
        foreach ($variant->attributeValues as $av) {
            if ($av->attribute && $this->isSizeLikeAttribute($av->attribute)) {
                // First active variant for a label wins (A2 stock preference handled below).
                $map[$av->value] ??= $variant;
            }
        }
    }
    return $map;
}

public function resolveComponentVariant(ComboItem $item, string $sizeLabel): ?\Modules\Variant\Models\ProductVariant
{
    if (! $item->product) {
        return null;
    }
    return $this->productSizeVariants($item->product)[$sizeLabel] ?? null;
}

/** Integer stock a specific component variant imposes, or null if unconstrained. */
private function componentAvailableForVariant(ComboItem $item, ?int $variantId): ?int
{
    $product = $item->product;
    if (! $product || ! $product->track_stock || $product->allow_negative_stock) {
        return null;
    }
    $hasHistory = DB::table('warehouse_stock')
        ->where('product_id', $item->product_id)
        ->when($variantId, fn ($q, $v) => $q->where('variant_id', $v))
        ->exists();
    if (! $hasHistory) {
        return null;
    }
    return (int) app(InventoryService::class)->getStockLevel($item->product_id, $variantId);
}

/** Max combos buildable for a size; null = unconstrained. */
public function availableStockForSize(Combo $combo, string $sizeLabel): ?int
{
    $min = null;
    foreach ($combo->items as $item) {
        $variant = $this->resolveComponentVariant($item, $sizeLabel);
        $avail = $this->componentAvailableForVariant($item, $variant?->id);
        if ($avail === null) {
            continue;
        }
        $perCombo = $item->quantity > 0 ? intdiv($avail, $item->quantity) : 0;
        $min = $min === null ? $perCombo : min($min, $perCombo);
    }
    return $min;
}

public function isInStockForSize(Combo $combo, string $sizeLabel, int $qty = 1): bool
{
    $avail = $this->availableStockForSize($combo, $sizeLabel);
    return $avail === null || $avail >= $qty;
}

/** Ordered list of selectable sizes shared by every component. */
public function availableSizes(Combo $combo): array
{
    $perComponent = [];           // index => [label => variant]
    foreach ($combo->items as $item) {
        if (! $item->product) {
            return [];            // a missing product means we can't guarantee any size
        }
        $perComponent[] = $this->productSizeVariants($item->product);
    }
    if (empty($perComponent)) {
        return [];
    }

    // Intersection of labels present in every component.
    $common = array_keys($perComponent[0]);
    foreach ($perComponent as $map) {
        $common = array_values(array_intersect($common, array_keys($map)));
    }
    if (empty($common)) {
        return [];
    }

    // Order by the size attribute's sort_order via the first component's value rows.
    $order = \Modules\Variant\Models\VariantAttributeValue::whereIn('value', $common)
        ->orderBy('sort_order')->orderBy('id')
        ->pluck('value')->unique()->values()->all();
    $ordered = array_values(array_filter($order, fn ($l) => in_array($l, $common, true)));
    foreach ($common as $l) {                       // keep any label missing from $order
        if (! in_array($l, $ordered, true)) { $ordered[] = $l; }
    }

    return array_map(fn ($label) => [
        'value'    => $label,
        'in_stock' => $this->isInStockForSize($combo, $label, 1),
    ], $ordered);
}
```

- [ ] **Step 4: Run to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Feature/ComboSizeTest.php`
Expected: PASS (both tests).

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Services/ComboService.php Modules/Ecommerce/tests/Feature/ComboSizeTest.php
git commit -m "feat(combo): derive combo sizes and resolve per-size variants"
```

---

### Task 3: Admin — toggle, validation, persist

**Files:**
- Modify: `Modules/Ecommerce/app/Http/Requests/StoreComboRequest.php`, `UpdateComboRequest.php` (add rule)
- Modify: `Modules/Ecommerce/app/Services/ComboService.php` (`persist` saves `size_required`)
- Modify: `Modules/Ecommerce/resources/views/combos/create.blade.php`, `edit.blade.php` (toggle)
- Test: `Modules/Ecommerce/tests/Feature/ComboAdminTest.php` (append)

**Interfaces:**
- Consumes: `Combo::$size_required` (Task 1).
- Produces: combo create/edit accept `size_required` (boolean) and persist it.

- [ ] **Step 1: Write the failing test** — append to `ComboAdminTest.php` (mirror the existing admin-create test's auth + payload; key assertion below):

```php
public function test_admin_can_enable_customer_size_selection(): void
{
    $this->actingAsAdmin(); // existing helper in this test class
    $product = $this->makeProduct();

    $res = $this->post(route('ecommerce.combos.store'), [
        'name' => 'Sized Combo', 'combo_price' => 1500, 'is_active' => 1,
        'size_required' => 1,
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ]);

    $res->assertRedirect();
    $this->assertTrue((bool) Combo::where('name', 'Sized Combo')->first()->size_required);
}
```

> If `ComboAdminTest` has no `actingAsAdmin()` / `makeProduct()`, reuse whatever auth + product setup the existing passing tests in that file use (read the top of the file first).

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/ComboAdminTest.php --filter test_admin_can_enable_customer_size_selection`
Expected: FAIL — `size_required` is false (not validated/persisted).

- [ ] **Step 3: Add the validation rule** — in BOTH `StoreComboRequest.php` and `UpdateComboRequest.php` `rules()`, add after `'is_active'`:

```php
'size_required'      => ['nullable', 'boolean'],
```

- [ ] **Step 4: Persist it** — in `ComboService::persist()`, inside `$combo->fill([...])`, add:

```php
'size_required'  => (bool) ($data['size_required'] ?? false),
```

- [ ] **Step 5: Add the toggle to the forms** — in `combos/create.blade.php` and `combos/edit.blade.php`, in the Visibility card (next to the Active switch), add:

```blade
<label class="form-check form-switch mb-0 mt-2">
    <input type="hidden" name="size_required" value="0">
    <input class="form-check-input" type="checkbox" name="size_required" value="1"
        {{ old('size_required', $combo->size_required ?? false) ? 'checked' : '' }}>
    <span class="fs-13 fw-600 ms-1">Let customer choose size</span>
</label>
```

> `create.blade.php` has no `$combo`; `old('size_required', false)` covers it. `edit.blade.php` has `$combo`.

- [ ] **Step 6: Run to verify it passes**

Run: `php artisan view:clear && php artisan test Modules/Ecommerce/tests/Feature/ComboAdminTest.php --filter test_admin_can_enable_customer_size_selection`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add Modules/Ecommerce/app/Http/Requests/StoreComboRequest.php Modules/Ecommerce/app/Http/Requests/UpdateComboRequest.php Modules/Ecommerce/app/Services/ComboService.php Modules/Ecommerce/resources/views/combos/create.blade.php Modules/Ecommerce/resources/views/combos/edit.blade.php Modules/Ecommerce/tests/Feature/ComboAdminTest.php
git commit -m "feat(combo): admin toggle to let customers choose combo size"
```

---

### Task 4: Storefront — size selector on the combo detail page

**Files:**
- Modify: `Modules/Ecommerce/app/Http/Controllers/Storefront/ComboController.php` (`show` passes `$sizeOptions`)
- Modify: `Modules/Ecommerce/resources/views/storefront/pages/combos/show.blade.php` (selector + JS)
- Test: `Modules/Ecommerce/tests/Feature/StorefrontComboTest.php` (append)

**Interfaces:**
- Consumes: `ComboService::availableSizes()` (Task 2), `Combo::$size_required`.
- Produces: detail page renders `.combo-size-options` with `input[name="combo_size"]` radios when `size_required`; hidden field carries the choice for the add-combo JS.

- [ ] **Step 1: Write the failing test** — append to `StorefrontComboTest.php`:

```php
public function test_size_required_combo_shows_size_selector(): void
{
    // Build a size-required combo with one product that has M/L variants.
    $attr = \Modules\Variant\Models\VariantAttribute::create(['name' => 'Size', 'display_type' => 'button', 'status' => 'active', 'sort_order' => 1]);
    $m = \Modules\Variant\Models\VariantAttributeValue::create(['variant_attribute_id' => $attr->id, 'value' => 'M', 'is_active' => true, 'sort_order' => 0])->id;
    $l = \Modules\Variant\Models\VariantAttributeValue::create(['variant_attribute_id' => $attr->id, 'value' => 'L', 'is_active' => true, 'sort_order' => 1])->id;
    $p = $this->makeProduct(['track_stock' => false]);
    foreach ([$m, $l] as $vid) {
        \Modules\Variant\Models\ProductVariant::create(['product_id' => $p->id, 'sku' => uniqid('v-'), 'sell_price' => 100, 'is_active' => true])
            ->attributeValues()->attach($vid);
    }
    $combo = \Modules\Ecommerce\Models\Combo::create(['name' => 'Sized', 'combo_price' => 900, 'is_active' => true, 'size_required' => true]);
    $combo->items()->create(['product_id' => $p->id, 'quantity' => 1, 'sort_order' => 0]);

    $this->get(route('storefront.combos.show', $combo->slug))
        ->assertOk()
        ->assertSee('combo_size', false)   // the radio group name
        ->assertSee('>M<', false)
        ->assertSee('>L<', false);
}
```

> Uses `CreatesComboTestData` (`makeProduct`). Add `use CreatesComboTestData;` to the test class if not already present.

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontComboTest.php --filter test_size_required_combo_shows_size_selector`
Expected: FAIL — `combo_size` not in the page.

- [ ] **Step 3: Pass sizes from the controller** — in `ComboController::show`, first ensure the combo's variant attribute names are eager-loaded so `availableSizes` doesn't N+1: add `'items.product.variants.attributeValues.attribute'` to the existing `Combo::active()->where('slug', $slug)->with([...])` list. Then, after `$relatedCombos = ...`, add and include in the view payload:

```php
$sizeOptions = $combo->size_required ? $this->service->availableSizes($combo) : [];
```
```php
return view('ecommerce::storefront.pages.combos.show', [
    'combo'         => $combo,
    'service'       => $this->service,
    'relatedCombos' => $relatedCombos,
    'sizeCharts'    => $sizeCharts,
    'sizeOptions'   => $sizeOptions,
    'seo'           => $seo,
]);
```

- [ ] **Step 4: Render the selector** — in `combos/show.blade.php`, immediately BEFORE the `{{-- Quantity & Add to Cart --}}` block, add:

```blade
@if ($combo->size_required && !empty($sizeOptions))
    <div class="details_single_variant combo_size_field">
        <p class="variant_title">Size :</p>
        <ul class="details_variant_size" id="comboSizeOptions">
            @foreach ($sizeOptions as $opt)
                <li class="variant_option_btn combo-size-option {{ $opt['in_stock'] ? '' : 'is-disabled' }}"
                    data-size="{{ $opt['value'] }}">{{ $opt['value'] }}</li>
            @endforeach
        </ul>
        <input type="hidden" id="comboSizeInput" name="combo_size" value="">
        <p class="text-danger fs-12 mt-1 d-none" id="comboSizeError">Please select a size.</p>
    </div>
@endif
```

- [ ] **Step 5: Wire the JS** — in the combo `show.blade.php` `@push('scripts')`, inside the existing `$(function(){...})`, add size selection + gate Buy Now / Add to cart. Add near the top of the ready block:

```javascript
var comboSizeRequired = {{ $combo->size_required && !empty($sizeOptions) ? 'true' : 'false' }};

$('#comboSizeOptions').on('click', '.combo-size-option', function () {
    if ($(this).hasClass('is-disabled')) { return; }
    $('#comboSizeOptions .combo-size-option').removeClass('active');
    $(this).addClass('active');
    $('#comboSizeInput').val($(this).data('size'));
    $('#comboSizeError').addClass('d-none');
});

// Returns the chosen size, or null if required-but-missing (and shows the error).
function comboChosenSize() {
    if (!comboSizeRequired) { return ''; }
    var size = $('#comboSizeInput').val();
    if (!size) { $('#comboSizeError').removeClass('d-none'); return null; }
    return size;
}
```

Then in BOTH the `#comboAddToCart` and `#comboBuyNow` click handlers, add the size to the payload and block when missing — at the top of each handler after `e.preventDefault();`:

```javascript
var size = comboChosenSize();
if (size === null) { return; }
```
and add `size: size,` to each `data: { combo_id: comboId, quantity: qty }` object (→ `data: { combo_id: comboId, quantity: qty, combo_size: size }`).

- [ ] **Step 6: Run to verify it passes**

Run: `php artisan view:clear && php artisan test Modules/Ecommerce/tests/Feature/StorefrontComboTest.php --filter test_size_required_combo_shows_size_selector`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add Modules/Ecommerce/app/Http/Controllers/Storefront/ComboController.php Modules/Ecommerce/resources/views/storefront/pages/combos/show.blade.php Modules/Ecommerce/tests/Feature/StorefrontComboTest.php
git commit -m "feat(combo): size selector on combo detail page"
```

---

### Task 5: Cart — size-aware addCombo

**Files:**
- Modify: `Modules/Ecommerce/app/Http/Controllers/Storefront/CartController.php` (`addCombo`)
- Test: `Modules/Ecommerce/tests/Feature/ComboCartTest.php` (append)

**Interfaces:**
- Consumes: `ComboService::availableSizes`, `resolveComponentVariant`, `isInStockForSize` (Task 2), `combo_size` request field (Task 4).
- Produces: cart line key `combo:{id}:{size}` for size combos; line carries `'size' => label` and components with resolved `variant_id` + size `variant_name`.

- [ ] **Step 1: Write the failing test** — append to `ComboCartTest.php`:

```php
public function test_size_required_combo_requires_a_valid_size_and_resolves_variants(): void
{
    $attr = \Modules\Variant\Models\VariantAttribute::create(['name' => 'Size', 'display_type' => 'button', 'status' => 'active', 'sort_order' => 1]);
    $l = \Modules\Variant\Models\VariantAttributeValue::create(['variant_attribute_id' => $attr->id, 'value' => 'L', 'is_active' => true, 'sort_order' => 1])->id;
    $p = $this->makeProduct(['track_stock' => false]);
    $variant = \Modules\Variant\Models\ProductVariant::create(['product_id' => $p->id, 'sku' => uniqid('v-'), 'sell_price' => 100, 'is_active' => true]);
    $variant->attributeValues()->attach($l);

    $combo = \Modules\Ecommerce\Models\Combo::create(['name' => 'Sized', 'combo_price' => 900, 'is_active' => true, 'size_required' => true]);
    $combo->items()->create(['product_id' => $p->id, 'quantity' => 1, 'sort_order' => 0]);

    // Missing size → rejected.
    $this->postJson(route('storefront.cart.add-combo'), ['combo_id' => $combo->id, 'quantity' => 1])
        ->assertStatus(422);

    // Valid size → added under combo:{id}:L with the resolved variant.
    $this->postJson(route('storefront.cart.add-combo'), ['combo_id' => $combo->id, 'quantity' => 1, 'combo_size' => 'L'])
        ->assertOk();

    $cart = session('cart', []);
    $this->assertArrayHasKey('combo:'.$combo->id.':L', $cart);
    $line = $cart['combo:'.$combo->id.':L'];
    $this->assertSame('L', $line['size']);
    $this->assertSame($variant->id, $line['components'][0]['variant_id']);
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/ComboCartTest.php --filter test_size_required_combo_requires_a_valid_size_and_resolves_variants`
Expected: FAIL — added without size under `combo:{id}` and variant_id null.

- [ ] **Step 3: Implement size handling in `addCombo`** — replace the validation + components/key/line construction. After loading `$combo` and `$quantity`, add the size block and use it:

```php
$request->validate([
    'combo_id'   => 'required|integer|exists:combos,id',
    'quantity'   => 'nullable|integer|min:1',
    'combo_size' => 'nullable|string|max:50',
]);

$combo = Combo::active()
    ->with(['items.product.variants.attributeValues.attribute', 'items.variant.attributeValues.attribute'])
    ->findOrFail($request->integer('combo_id'));
$quantity = max(1, (int) $request->input('quantity', 1));

$size = null;
if ($combo->size_required) {
    $size = trim((string) $request->input('combo_size', ''));
    $available = collect($combos->availableSizes($combo));
    $match = $available->firstWhere('value', $size);
    if ($size === '' || ! $match) {
        $message = __('Please select a valid size.');
        return $request->ajax() || $request->wantsJson()
            ? response()->json(['success' => false, 'message' => $message], 422)
            : redirect()->back()->with('error', $message);
    }
    if (! $combos->isInStockForSize($combo, $size, $quantity)) {
        $message = __('This size is out of stock.');
        return $request->ajax() || $request->wantsJson()
            ? response()->json(['success' => false, 'message' => $message], 422)
            : redirect()->back()->with('error', $message);
    }
}

if (! $combo->size_required && ! $combos->isInStock($combo, $quantity)) {
    $message = __('This combo is out of stock.');
    return $request->ajax() || $request->wantsJson()
        ? response()->json(['success' => false, 'message' => $message], 422)
        : redirect()->back()->with('error', $message);
}

$components = $combo->items->map(function ($item) use ($combos, $size) {
    $variant = $size ? $combos->resolveComponentVariant($item, $size) : $item->variant;
    return [
        'product_id'   => $item->product_id,
        'variant_id'   => $variant?->id ?? $item->variant_id,
        'quantity'     => $item->quantity,
        'name'         => $item->product->name ?? __('Product'),
        'variant_name' => $size ?: $item->variant?->variant_name,
    ];
})->all();

$cart = session('cart', []);
$key  = 'combo:' . $combo->id . ($size ? ':' . $size : '');
if (isset($cart[$key])) {
    $cart[$key]['quantity'] += $quantity;
} else {
    $cart[$key] = [
        'type'       => 'combo',
        'combo_id'   => $combo->id,
        'name'       => $combo->name,
        'slug'       => $combo->slug,
        'thumbnail'  => $combo->thumbnail,
        'price'      => $combos->effectivePrice($combo),
        'quantity'   => $quantity,
        'size'       => $size,           // '' / null when not a size combo
        'components' => $components,
    ];
}
```

> Keep the existing success-response block below this unchanged.

- [ ] **Step 4: Run to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Feature/ComboCartTest.php`
Expected: PASS (new test + the existing `add combo to cart uses server price` still green).

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Http/Controllers/Storefront/CartController.php Modules/Ecommerce/tests/Feature/ComboCartTest.php
git commit -m "feat(combo): resolve size variants and key cart by size in addCombo"
```

---

### Task 6: Combo cards route to detail page for size-required combos

**Files:**
- Modify: `Modules/Ecommerce/resources/views/storefront/partials/combo-card.blade.php`
- Test: `Modules/Ecommerce/tests/Feature/StorefrontComboTest.php` (append)

**Interfaces:**
- Consumes: `Combo::$size_required`.
- Produces: for size-required combos the card's cart icon is an `<a href="{combo show}">` (no `combo-add-cart-trigger`).

- [ ] **Step 1: Write the failing test** — append to `StorefrontComboTest.php` (reuse the size-combo builder from Task 4's test; factor it into a small private helper `makeSizeCombo()` in the test class if convenient):

```php
public function test_size_required_combo_card_links_to_detail_instead_of_quick_add(): void
{
    $combo = $this->makeSizeCombo(); // size_required combo with M/L (helper)
    // The shop page renders combo cards for a category; assert via the combos index.
    $this->get(route('storefront.combos.index'))
        ->assertOk()
        ->assertDontSee('combo-add-cart-trigger" data-combo-id="'.$combo->id, false);
}
```

> If a `makeSizeCombo()` helper doesn't exist, extract the setup from Task 4's test into one. The combos index must include the size combo (it lists active combos).

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontComboTest.php --filter test_size_required_combo_card_links_to_detail`
Expected: FAIL — card still renders the `combo-add-cart-trigger` for the size combo.

- [ ] **Step 3: Branch the card's cart button** — in `combo-card.blade.php`, replace the in-stock add-to-cart `<a class="product_add_cart_btn combo-add-cart-trigger" ...>` block with:

```blade
@if($inStock)
    @if($combo->size_required)
        {{-- Size combos: pick the size on the detail page, don't quick-add. --}}
        <a class="product_add_cart_btn" href="{{ route('storefront.combos.show', $combo->slug) }}"
           aria-label="Choose size" title="Choose size">
            <i class="fas fa-cart-plus"></i>
        </a>
    @else
        <a class="product_add_cart_btn combo-add-cart-trigger" href="#"
           data-combo-id="{{ $combo->id }}"
           aria-label="Add to cart" title="Add to cart">
            <i class="fas fa-cart-plus"></i>
        </a>
    @endif
@endif
```

- [ ] **Step 4: Run to verify it passes**

Run: `php artisan view:clear && php artisan test Modules/Ecommerce/tests/Feature/StorefrontComboTest.php --filter test_size_required_combo_card_links_to_detail`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/resources/views/storefront/partials/combo-card.blade.php Modules/Ecommerce/tests/Feature/StorefrontComboTest.php
git commit -m "feat(combo): size-required combo cards link to detail page"
```

---

### Task 7: Show the chosen size in mini-cart and checkout summary

**Files:**
- Modify: `Modules/Ecommerce/resources/views/storefront/partials/mini-cart-items.blade.php`
- Modify: `Modules/Ecommerce/resources/views/storefront/pages/checkout/index.blade.php` (Order Summary item loop)
- Test: manual/visual (no new automated test — display-only)

**Interfaces:**
- Consumes: cart line `'size'` field (Task 5).

- [ ] **Step 1: Mini-cart** — in `mini-cart-items.blade.php`, inside the `@unless ($isCombo)` … `@endunless` area, ADD a combo-size line. Right BEFORE the `@unless ($isCombo)` block, add:

```blade
@if ($isCombo && !empty($item['size']))
    <small class="text-muted d-block"><b>Size:</b> {{ $item['size'] }}</small>
@endif
```

- [ ] **Step 2: Checkout summary** — in `checkout/index.blade.php`, in the order-summary item loop, AFTER the variant block (`@if (!empty($item['variant_attributes'])) … @endif`), add:

```blade
@if ($isComboLine && !empty($item['size']))
    <p class="fs-12 text-muted mb-1"><b>Size:</b> {{ $item['size'] }}</p>
@endif
```

- [ ] **Step 3: Verify rendering** — add a size combo to the cart and load both surfaces:

Run:
```bash
php artisan view:clear
# (manual) add a size combo via the detail page, open the mini-cart drawer and /checkout
```
Expected: combo line shows `Size: L`; non-size combos unchanged (name / price / qty only).

- [ ] **Step 4: Commit**

```bash
git add Modules/Ecommerce/resources/views/storefront/partials/mini-cart-items.blade.php Modules/Ecommerce/resources/views/storefront/pages/checkout/index.blade.php
git commit -m "feat(combo): show chosen size in mini-cart and checkout summary"
```

---

### Task 8: Full regression + manual smoke

**Files:** none (verification only)

- [ ] **Step 1: Run the combo suite**

Run: `php artisan test Modules/Ecommerce/tests/Feature/ComboModelTest.php Modules/Ecommerce/tests/Feature/ComboSizeTest.php Modules/Ecommerce/tests/Feature/ComboAdminTest.php Modules/Ecommerce/tests/Feature/ComboCartTest.php Modules/Ecommerce/tests/Feature/StorefrontComboTest.php Modules/Ecommerce/tests/Feature/ComboCheckoutTest.php`
Expected: all PASS.

- [ ] **Step 2: Manual smoke (admin → storefront → order)**
  1. Admin: create a combo of two size-variant products, enable "Let customer choose size", save.
  2. Storefront combo detail: size selector shows the common sizes; out-of-stock sizes disabled; Buy Now/Add to cart blocked until a size is picked.
  3. Combo card cart icon → opens the detail page (no instant add).
  4. Add size L → mini-cart shows `Size: L`; checkout summary shows `Size: L`.
  5. Place the order → the L variant of each component is decremented; order items reference the L variants.
  6. Non-size combo still instant-adds and behaves exactly as before.

- [ ] **Step 3: Commit (if any fixups were needed)**

```bash
git add -A && git commit -m "test(combo): regression pass for customer-selected combo size"
```

---

## Notes for the implementer
- Run one task at a time; each ends green and committed.
- `php artisan view:clear` after editing any Blade before hitting the page/tests.
- The login rate limiter is `throttle:5,15` — avoid repeated curl logins when smoke-testing.
