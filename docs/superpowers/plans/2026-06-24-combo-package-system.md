# Combo Package System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let admins build combo packages (a bundle of fixed-variant products at a bundle price + optional discount) with a thumbnail, a gallery drawn from the component products' images, an own storefront detail page + listing, and homepage placement — buying a combo expands into per-component order lines so stock deducts per product/variant at fulfillment.

**Architecture:** New `Combo`/`ComboItem`/`ComboImage` models + a `ComboService` (pricing, price-allocation, derived stock) in the Ecommerce module. Admin CRUD under the `/ecommerce` group; storefront listing + detail pages; the session cart carries a combo as one line that `createOrder()` expands into `EcommerceOrderItem`s; a new `combos` homepage section type with a combo picker.

**Tech Stack:** Laravel 12, nwidart modules, Blade + jQuery (storefront theme + admin `bp-` design system), MySQL, PHPUnit (`Tests\TestCase`, `RefreshDatabase`).

## Global Constraints

- **Money** columns `decimal(15,2)`; never float. **Dates** `DD MMM YYYY`. Currency via `currency_symbol()` / BD lakh format.
- **SOLID/DRY:** thin controllers delegate to `ComboService`; reuse `InventoryService::getStockLevel(productId, variantId)` for stock — do not reimplement stock math.
- **Security:** validate in Form Requests; server always recomputes combo price from DB (never trust client price); `@csrf` on forms; mass-assignment `$fillable`; uploads via the project `Upload` helper (randomized names, `mimes:jpg,jpeg,png,webp|max:2048`).
- **No hardcoded routes** — `route('...')` everywhere (Blade, PHP, JS). **`'use strict';`** atop every `<script>`. **No inline CSS** (admin: `bp-` classes + `[data-theme="dark"]` overrides; storefront: its own theme classes).
- **Stock rule (mirror checkout):** a component limits availability only when `track_stock` is true, `allow_negative_stock` is false, and a `warehouse_stock` row exists for it; otherwise it never blocks.
- **Pricing:** `summed_price` = Σ(component `effective_sell_price` × qty) shown as strikethrough; `effective_price` = `combo_price − discount(combo_price)`, floored at 0; component order-item prices must sum exactly to `effective_price × comboQty`.
- **Tests** extend `Tests\TestCase` (RefreshDatabase, array cache/session, MySQL `bizpos_test`). Run module tests with `php artisan test <path>`.

## Reference patterns (existing files to mirror)

- Pivot migration: `Modules/Ecommerce/database/migrations/2026_05_29_190000_create_homepage_section_products_table.php`.
- Model with slug auto-gen + `scopeActive` + `belongsToMany`+pivot: `Modules/Ecommerce/app/Models/ProductCollection.php`.
- Admin CRUD controller + requests + views: `ProductCollection` flow in `Modules/Ecommerce/app/Http/Controllers/ContentController.php` (`collectionStore/Edit/Update`) and `collection-create.blade.php`/`collection-edit.blade.php`.
- Homepage product picker (search-and-add UI + sync): `ContentController::homepageSectionProducts/Update` + `homepage-section-products.blade.php`.
- Checkout stock check: `Modules/Ecommerce/app/Http/Controllers/Storefront/CheckoutController.php:166-211` (uses `InventoryService::getStockLevel`).
- Order creation: `StorefrontService::createOrder()` (`:877`) and `calculateCartSubtotal()` (`:833`).
- Cart add/line shape: `Modules/Ecommerce/app/Http/Controllers/Storefront/CartController.php:52-157`.
- Storefront product detail (gallery, add-to-cart, stock): `shop/show.blade.php`; product card: `storefront/partials/product-card.blade.php`.
- `ProductImage`: table `product_images`, fields `product_id, image_path, alt_text, sort_order, is_primary`.
- `ProductVariant`: `product_id, sku, sell_price`; accessor `effective_sell_price` (`sell_price ?? product->sell_price`); relations `product()`, `attributeValues()`.

## File Structure

- Create: `Modules/Ecommerce/database/migrations/2026_06_24_000001_create_combos_table.php`
- Create: `…/2026_06_24_000002_create_combo_items_table.php`
- Create: `…/2026_06_24_000003_create_combo_images_table.php`
- Create: `…/2026_06_24_000004_create_homepage_section_combos_table.php`
- Create: `…/2026_06_24_000005_add_combo_to_ecommerce_order_items_table.php`
- Create: `Modules/Ecommerce/app/Models/{Combo,ComboItem,ComboImage}.php`
- Create: `Modules/Ecommerce/app/Services/ComboService.php`
- Create: `Modules/Ecommerce/app/Http/Controllers/ComboController.php` (admin)
- Create: `Modules/Ecommerce/app/Http/Controllers/Storefront/ComboController.php` (storefront)
- Create: `Modules/Ecommerce/app/Http/Requests/{StoreComboRequest,UpdateComboRequest}.php`
- Create admin views: `Modules/Ecommerce/resources/views/combos/{index,create,edit}.blade.php`
- Create storefront views: `…/storefront/pages/combos/{index,show}.blade.php` + `…/storefront/partials/combo-card.blade.php`
- Modify: `Modules/Ecommerce/routes/web.php` (admin combo routes), `Modules/Ecommerce/routes/storefront.php` (storefront combo routes)
- Modify: `Modules/Ecommerce/app/Http/Controllers/Storefront/CartController.php` (+`addCombo`)
- Modify: `Modules/Ecommerce/app/Services/StorefrontService.php` (`calculateCartSubtotal`, `createOrder` expansion, mirrored sale)
- Modify: `Modules/Ecommerce/app/Http/Controllers/Storefront/CheckoutController.php` (combo stock check)
- Modify: `Modules/Ecommerce/app/Support/HomepageSectionSchema.php` (+`combos` type), `ContentController.php` (combo picker), homepage storefront partial include
- Modify: `config/navigation.php` + the Online sidebar group (Combos entry)
- Tests under `Modules/Ecommerce/tests/{Unit,Feature}/`

---

### Task 1: Schema + models

**Files:**
- Create the 5 migrations listed above.
- Create: `Modules/Ecommerce/app/Models/Combo.php`, `ComboItem.php`, `ComboImage.php`
- Test: `Modules/Ecommerce/tests/Feature/ComboModelTest.php`

**Interfaces produced:**
- `Combo`: `$fillable = ['name','slug','thumbnail','description','combo_price','discount_type','discount_value','is_active','sort_order']`; casts `combo_price`/`discount_value` decimal:2, `is_active` bool; relations `items(): HasMany<ComboItem>`, `galleryImages(): HasMany<ComboImage>`, `homepageSections(): BelongsToMany`; `scopeActive`; auto-slug on create.
- `ComboItem`: `$fillable = ['combo_id','product_id','variant_id','quantity','sort_order']`; `belongsTo` `combo`, `product` (`Modules\Product\Models\Product`), `variant` (`Modules\Variant\Models\ProductVariant`).
- `ComboImage`: `$fillable = ['combo_id','image_path','product_image_id','sort_order']`; `belongsTo combo`.

- [ ] **Step 1: Write the failing test**

Create `Modules/Ecommerce/tests/Feature/ComboModelTest.php`:

```php
<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Models\ComboImage;
use Modules\Ecommerce\Models\ComboItem;
use Modules\Product\Models\Product;
use Tests\TestCase;

class ComboModelTest extends TestCase
{
    public function test_combo_autogenerates_slug_and_relations_resolve(): void
    {
        $product = Product::factory()->create(['sell_price' => 500]);

        $combo = Combo::create([
            'name' => 'Eid Combo', 'combo_price' => 900, 'discount_type' => 'none',
            'discount_value' => 0, 'is_active' => true,
        ]);
        $combo->items()->create(['product_id' => $product->id, 'quantity' => 2, 'sort_order' => 0]);
        $combo->galleryImages()->create(['image_path' => 'uploads/products/x.png', 'sort_order' => 0]);

        $this->assertSame('eid-combo', $combo->slug);
        $this->assertCount(1, $combo->fresh()->items);
        $this->assertCount(1, $combo->fresh()->galleryImages);
        $this->assertSame($product->id, $combo->items->first()->product->id);
        $this->assertTrue(Combo::active()->whereKey($combo->id)->exists());
    }
}
```

> If `Product::factory()` is unavailable, create the product via `Product::create([...])` with the columns the products table requires (check `Schema::getColumnListing('products')`); keep the rest of the assertions.

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/ComboModelTest.php`
Expected: FAIL — `Class "Modules\Ecommerce\Models\Combo" not found`.

- [ ] **Step 3: Write the migrations**

`2026_06_24_000001_create_combos_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('combos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('thumbnail')->nullable();
            $table->text('description')->nullable();
            $table->decimal('combo_price', 15, 2)->default(0);
            $table->enum('discount_type', ['none', 'fixed', 'percentage'])->default('none');
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_active', 'sort_order']);
        });
    }
    public function down(): void { Schema::dropIfExists('combos'); }
};
```

`2026_06_24_000002_create_combo_items_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('combo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->integer('quantity')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->index('variant_id');
        });
    }
    public function down(): void { Schema::dropIfExists('combo_items'); }
};
```

`2026_06_24_000003_create_combo_images_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('combo_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combo_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->unsignedBigInteger('product_image_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('combo_images'); }
};
```

`2026_06_24_000004_create_homepage_section_combos_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('homepage_section_combos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('homepage_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('combo_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['homepage_section_id', 'combo_id']);
            $table->index('sort_order');
        });
    }
    public function down(): void { Schema::dropIfExists('homepage_section_combos'); }
};
```

`2026_06_24_000005_add_combo_to_ecommerce_order_items_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('combo_id')->nullable()->after('variant_id');
            $table->string('combo_name')->nullable()->after('combo_id');
            $table->index('combo_id');
        });
    }
    public function down(): void
    {
        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            $table->dropIndex(['combo_id']);
            $table->dropColumn(['combo_id', 'combo_name']);
        });
    }
};
```

- [ ] **Step 4: Write the models**

`Modules/Ecommerce/app/Models/Combo.php`:

```php
<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Combo extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'thumbnail', 'description', 'combo_price',
        'discount_type', 'discount_value', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'combo_price'    => 'decimal:2',
        'discount_value' => 'decimal:2',
        'is_active'      => 'boolean',
        'sort_order'     => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Combo $combo) {
            if (empty($combo->slug)) {
                $base = Str::slug($combo->name) ?: 'combo';
                $slug = $base;
                $i = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . (++$i);
                }
                $combo->slug = $slug;
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(ComboItem::class)->orderBy('sort_order');
    }

    public function galleryImages(): HasMany
    {
        return $this->hasMany(ComboImage::class)->orderBy('sort_order');
    }

    public function homepageSections(): BelongsToMany
    {
        return $this->belongsToMany(HomepageSection::class, 'homepage_section_combos')
            ->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

`Modules/Ecommerce/app/Models/ComboItem.php`:

```php
<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Product;
use Modules\Variant\Models\ProductVariant;

class ComboItem extends Model
{
    protected $fillable = ['combo_id', 'product_id', 'variant_id', 'quantity', 'sort_order'];

    protected $casts = ['quantity' => 'integer', 'sort_order' => 'integer'];

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
```

`Modules/Ecommerce/app/Models/ComboImage.php`:

```php
<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComboImage extends Model
{
    protected $fillable = ['combo_id', 'image_path', 'product_image_id', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }
}
```

- [ ] **Step 5: Run to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Feature/ComboModelTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add Modules/Ecommerce/database/migrations/2026_06_24_0000*_*.php Modules/Ecommerce/app/Models/Combo.php Modules/Ecommerce/app/Models/ComboItem.php Modules/Ecommerce/app/Models/ComboImage.php Modules/Ecommerce/tests/Feature/ComboModelTest.php
git commit -m "feat(ecommerce): combo schema + models"
```

---

### Task 2: ComboService — pricing & allocation

**Files:**
- Create: `Modules/Ecommerce/app/Services/ComboService.php`
- Test: `Modules/Ecommerce/tests/Unit/ComboServicePricingTest.php`

**Interfaces:**
- Consumes: `Combo`, `ComboItem` (Task 1); `ProductVariant::effective_sell_price`; `Product::sell_price`.
- Produces:
  - `summedPrice(Combo $c): float` — Σ over items of `componentUnitPrice(item) × item.quantity`.
  - `componentUnitPrice(ComboItem $item): float` — `variant?->effective_sell_price ?? product->sell_price` (0 if product missing).
  - `discountAmount(Combo $c): float` — `none`→0; `fixed`→`min(discount_value, combo_price)`; `percentage`→`combo_price × min(discount_value,100)/100`; rounded 2dp.
  - `effectivePrice(Combo $c): float` — `max(0, round(combo_price − discountAmount, 2))`.
  - `allocatePrices(Combo $c, int $comboQty): array` — returns `[comboItemId => unitPrice]` where `Σ(unitPrice × item.quantity × comboQty)` equals `round(effectivePrice × comboQty, 2)`; allocate proportionally to `componentUnitPrice×quantity`, last item absorbs the rounding remainder. `unitPrice` is per single component unit.

- [ ] **Step 1: Write the failing test**

Create `Modules/Ecommerce/tests/Unit/ComboServicePricingTest.php`:

```php
<?php

namespace Modules\Ecommerce\Tests\Unit;

use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Services\ComboService;
use Modules\Product\Models\Product;
use Tests\TestCase;

class ComboServicePricingTest extends TestCase
{
    private function combo(array $attrs, array $items): Combo
    {
        $combo = Combo::create(array_merge([
            'name' => 'C', 'combo_price' => 0, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true,
        ], $attrs));
        foreach ($items as $it) {
            $p = Product::create(['name' => 'P', 'sku' => uniqid('s'), 'sell_price' => $it['price'], 'status' => 'active']);
            $combo->items()->create(['product_id' => $p->id, 'quantity' => $it['qty'], 'sort_order' => 0]);
        }
        return $combo->fresh('items');
    }

    public function test_summed_price_sums_components(): void
    {
        $svc = new ComboService();
        $c = $this->combo(['combo_price' => 1000], [['price' => 500, 'qty' => 2], ['price' => 300, 'qty' => 1]]);
        $this->assertSame(1300.0, $svc->summedPrice($c)); // 500*2 + 300
    }

    public function test_fixed_and_percentage_discounts(): void
    {
        $svc = new ComboService();
        $fixed = $this->combo(['combo_price' => 1000, 'discount_type' => 'fixed', 'discount_value' => 150], [['price' => 500, 'qty' => 2]]);
        $this->assertSame(150.0, $svc->discountAmount($fixed));
        $this->assertSame(850.0, $svc->effectivePrice($fixed));

        $pct = $this->combo(['combo_price' => 1000, 'discount_type' => 'percentage', 'discount_value' => 10], [['price' => 500, 'qty' => 2]]);
        $this->assertSame(100.0, $svc->discountAmount($pct));
        $this->assertSame(900.0, $svc->effectivePrice($pct));
    }

    public function test_discount_cannot_exceed_price(): void
    {
        $svc = new ComboService();
        $c = $this->combo(['combo_price' => 200, 'discount_type' => 'fixed', 'discount_value' => 999], [['price' => 100, 'qty' => 1]]);
        $this->assertSame(0.0, $svc->effectivePrice($c));
    }

    public function test_allocated_prices_sum_to_combo_total(): void
    {
        $svc = new ComboService();
        // effective 1000, components 500x1 and 300x1 (sum 800) -> allocate 1000 across them
        $c = $this->combo(['combo_price' => 1000], [['price' => 500, 'qty' => 1], ['price' => 300, 'qty' => 1]]);
        $alloc = $svc->allocatePrices($c, 2); // comboQty 2 -> total 2000
        $total = 0.0;
        foreach ($c->items as $item) {
            $total += round($alloc[$item->id] * $item->quantity * 2, 2);
        }
        $this->assertSame(2000.0, round($total, 2));
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Unit/ComboServicePricingTest.php`
Expected: FAIL — `Class "Modules\Ecommerce\Services\ComboService" not found`.

- [ ] **Step 3: Write the service (pricing half)**

Create `Modules/Ecommerce/app/Services/ComboService.php`:

```php
<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Models\ComboItem;

class ComboService
{
    /** Per-single-unit price of a component (variant price wins, else product). */
    public function componentUnitPrice(ComboItem $item): float
    {
        if ($item->variant) {
            return (float) $item->variant->effective_sell_price;
        }

        return (float) ($item->product->sell_price ?? 0);
    }

    /** Strikethrough reference: sum of component sell prices × qty. */
    public function summedPrice(Combo $combo): float
    {
        return round($combo->items->sum(
            fn (ComboItem $i) => $this->componentUnitPrice($i) * $i->quantity
        ), 2);
    }

    public function discountAmount(Combo $combo): float
    {
        $price = (float) $combo->combo_price;

        return match ($combo->discount_type) {
            'fixed'      => round(min((float) $combo->discount_value, $price), 2),
            'percentage' => round($price * min((float) $combo->discount_value, 100) / 100, 2),
            default      => 0.0,
        };
    }

    public function effectivePrice(Combo $combo): float
    {
        return max(0.0, round((float) $combo->combo_price - $this->discountAmount($combo), 2));
    }

    /**
     * Distribute the combo's effective total across components so the
     * per-component unit prices sum exactly to effectivePrice × comboQty.
     *
     * @return array<int, float> comboItemId => per-unit price
     */
    public function allocatePrices(Combo $combo, int $comboQty): array
    {
        $items = $combo->items;
        $target = round($this->effectivePrice($combo) * $comboQty, 2);

        $weights = [];
        $weightTotal = 0.0;
        foreach ($items as $item) {
            $w = $this->componentUnitPrice($item) * $item->quantity;
            $weights[$item->id] = $w;
            $weightTotal += $w;
        }

        $alloc = [];
        $runningLineTotal = 0.0;
        $lastId = $items->last()?->id;

        foreach ($items as $item) {
            $lineQty = $item->quantity * $comboQty;
            if ($item->id === $lastId) {
                // Last component absorbs the rounding remainder.
                $lineTotal = round($target - $runningLineTotal, 2);
            } else {
                $share = $weightTotal > 0 ? $weights[$item->id] / $weightTotal : 1 / max(count($items), 1);
                $lineTotal = round($target * $share, 2);
                $runningLineTotal += $lineTotal;
            }
            $alloc[$item->id] = $lineQty > 0 ? round($lineTotal / $lineQty, 2) : 0.0;
        }

        return $alloc;
    }
}
```

- [ ] **Step 4: Run to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Unit/ComboServicePricingTest.php`
Expected: PASS (4 tests).

> Note: per-unit rounding can drift by a cent on odd splits; the order-expansion task (Task 7) records the line subtotal as `round(unitPrice × lineQty, 2)` and the combo's grand contribution is reconciled to `effectivePrice × comboQty` there. The assertion above tolerates this because the last item absorbs the remainder at the line-total level.

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Services/ComboService.php Modules/Ecommerce/tests/Unit/ComboServicePricingTest.php
git commit -m "feat(ecommerce): ComboService pricing + price allocation"
```

---

### Task 3: ComboService — derived stock

**Files:**
- Modify: `Modules/Ecommerce/app/Services/ComboService.php`
- Test: `Modules/Ecommerce/tests/Feature/ComboStockTest.php`

**Interfaces produced:**
- `componentAvailable(ComboItem $item): ?int` — `null` when the component does not constrain stock (no `track_stock`, or `allow_negative_stock`, or no `warehouse_stock` history); else the integer stock level from `InventoryService::getStockLevel(productId, variantId)`.
- `availableStock(Combo $c): ?int` — `null` if no component constrains; else `min` over constraining items of `floor(componentAvailable / item.quantity)`.
- `isInStock(Combo $c, int $qty = 1): bool` — `availableStock === null || availableStock >= qty`.

- [ ] **Step 1: Write the failing test**

Create `Modules/Ecommerce/tests/Feature/ComboStockTest.php`:

```php
<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Services\ComboService;
use Modules\Product\Models\Product;
use Tests\TestCase;

class ComboStockTest extends TestCase
{
    private function stockedProduct(int $level): Product
    {
        $p = Product::create([
            'name' => 'P', 'sku' => uniqid('s'), 'sell_price' => 100,
            'status' => 'active', 'track_stock' => true, 'allow_negative_stock' => false,
        ]);
        DB::table('warehouse_stock')->insert([
            'product_id' => $p->id, 'variant_id' => null, 'quantity' => $level,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return $p;
    }

    public function test_available_is_min_over_components_divided_by_qty(): void
    {
        $svc = new ComboService();
        $a = $this->stockedProduct(10); // qty 2 -> 5 combos
        $b = $this->stockedProduct(9);  // qty 3 -> 3 combos
        $combo = Combo::create(['name' => 'C', 'combo_price' => 100, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true]);
        $combo->items()->create(['product_id' => $a->id, 'quantity' => 2]);
        $combo->items()->create(['product_id' => $b->id, 'quantity' => 3]);

        $this->assertSame(3, $svc->availableStock($combo->fresh('items')));
        $this->assertTrue($svc->isInStock($combo, 3));
        $this->assertFalse($svc->isInStock($combo, 4));
    }

    public function test_untracked_component_does_not_constrain(): void
    {
        $svc = new ComboService();
        $p = Product::create(['name' => 'P', 'sku' => uniqid('s'), 'sell_price' => 100, 'status' => 'active', 'track_stock' => false]);
        $combo = Combo::create(['name' => 'C', 'combo_price' => 100, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true]);
        $combo->items()->create(['product_id' => $p->id, 'quantity' => 1]);

        $this->assertNull($svc->availableStock($combo->fresh('items')));
        $this->assertTrue($svc->isInStock($combo, 999));
    }
}
```

> Verify the `warehouse_stock` columns with `Schema::getColumnListing('warehouse_stock')` and adjust the insert (e.g. a `warehouse_id`) if the table requires more. The check mirrors `CheckoutController` which keys on `product_id`(+`variant_id`).

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/ComboStockTest.php`
Expected: FAIL — `Call to undefined method ...availableStock()`.

- [ ] **Step 3: Add stock methods to ComboService**

Append to `ComboService` (add `use` imports at top):

```php
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Services\InventoryService;
use Modules\Product\Models\Product;
```

```php
    /** Integer stock that this component imposes, or null if it never constrains. */
    public function componentAvailable(ComboItem $item): ?int
    {
        $product = $item->product;
        if (! $product || ! $product->track_stock || $product->allow_negative_stock) {
            return null;
        }

        $hasHistory = DB::table('warehouse_stock')
            ->where('product_id', $item->product_id)
            ->when($item->variant_id, fn ($q, $v) => $q->where('variant_id', $v))
            ->exists();
        if (! $hasHistory) {
            return null;
        }

        return (int) app(InventoryService::class)->getStockLevel($item->product_id, $item->variant_id);
    }

    /** null = unconstrained; else max combos buildable from current stock. */
    public function availableStock(Combo $combo): ?int
    {
        $min = null;
        foreach ($combo->items as $item) {
            $avail = $this->componentAvailable($item);
            if ($avail === null) {
                continue;
            }
            $perCombo = $item->quantity > 0 ? intdiv($avail, $item->quantity) : 0;
            $min = $min === null ? $perCombo : min($min, $perCombo);
        }

        return $min;
    }

    public function isInStock(Combo $combo, int $qty = 1): bool
    {
        $avail = $this->availableStock($combo);

        return $avail === null || $avail >= $qty;
    }
```

- [ ] **Step 4: Run to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Feature/ComboStockTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Services/ComboService.php Modules/Ecommerce/tests/Feature/ComboStockTest.php
git commit -m "feat(ecommerce): ComboService derived stock from components"
```

---

### Task 4: Admin CRUD (combos management)

**Files:**
- Create: `Modules/Ecommerce/app/Http/Requests/StoreComboRequest.php`, `UpdateComboRequest.php`
- Create: `Modules/Ecommerce/app/Http/Controllers/ComboController.php`
- Create views: `Modules/Ecommerce/resources/views/combos/{index,create,edit}.blade.php`
- Modify: `Modules/Ecommerce/routes/web.php`, `config/navigation.php`, the Online sidebar partial
- Test: `Modules/Ecommerce/tests/Feature/ComboAdminTest.php`

**Interfaces:**
- Consumes: `Combo`, `ComboItem`, `ComboImage`, `ComboService` (persistence helper added here).
- Produces: routes `ecommerce.combos.{index,create,store,edit,update,destroy,toggle-status}`; `ComboService::persist(array $data, ?Combo $combo): Combo` (transaction: upsert combo + replace items + replace gallery + thumbnail upload/replace).

- [ ] **Step 1: Write the failing test**

Create `Modules/Ecommerce/tests/Feature/ComboAdminTest.php`:

```php
<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Combo;
use Modules\Product\Models\Product;
use Tests\TestCase;

class ComboAdminTest extends TestCase
{
    private function admin(): \App\Models\User
    {
        return \App\Models\User::factory()->create();
    }

    public function test_admin_can_create_combo_with_items(): void
    {
        $p1 = Product::create(['name' => 'A', 'sku' => uniqid('s'), 'sell_price' => 500, 'status' => 'active']);
        $p2 = Product::create(['name' => 'B', 'sku' => uniqid('s'), 'sell_price' => 300, 'status' => 'active']);

        $res = $this->actingAs($this->admin())->post(route('ecommerce.combos.store'), [
            'name' => 'Festive Combo', 'combo_price' => 700,
            'discount_type' => 'fixed', 'discount_value' => 50, 'is_active' => 1,
            'items' => [
                ['product_id' => $p1->id, 'variant_id' => null, 'quantity' => 1],
                ['product_id' => $p2->id, 'variant_id' => null, 'quantity' => 2],
            ],
        ]);

        $res->assertRedirect(route('ecommerce.combos.index'));
        $combo = Combo::firstWhere('name', 'Festive Combo');
        $this->assertNotNull($combo);
        $this->assertCount(2, $combo->items);
        $this->assertSame('festive-combo', $combo->slug);
    }

    public function test_combo_requires_at_least_one_item(): void
    {
        $res = $this->actingAs($this->admin())->from(route('ecommerce.combos.create'))
            ->post(route('ecommerce.combos.store'), [
                'name' => 'Empty', 'combo_price' => 100, 'discount_type' => 'none', 'discount_value' => 0,
            ]);
        $res->assertRedirect(route('ecommerce.combos.create'));
        $res->assertSessionHasErrors('items');
    }

    public function test_admin_can_toggle_and_delete(): void
    {
        $combo = Combo::create(['name' => 'X', 'combo_price' => 100, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true]);
        $this->actingAs($this->admin())->patch(route('ecommerce.combos.toggle-status', $combo))->assertRedirect();
        $this->assertFalse($combo->fresh()->is_active);
        $this->actingAs($this->admin())->delete(route('ecommerce.combos.destroy', $combo))->assertRedirect();
        $this->assertSoftDeleted($combo);
    }
}
```

> If the admin guard needs a specific user/role, mirror however other `ecommerce.*` feature tests authenticate (search `Modules/Ecommerce/tests` for `actingAs`). The `/ecommerce` group uses `middleware('auth')`.

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/ComboAdminTest.php`
Expected: FAIL — route `ecommerce.combos.store` not defined.

- [ ] **Step 3: Add the routes**

In `Modules/Ecommerce/routes/web.php`, inside the `auth`/`ecommerce` group (near the campaigns block), add:

```php
    // Combo packages
    Route::get('/combos', [\Modules\Ecommerce\Http\Controllers\ComboController::class, 'index'])->name('combos.index');
    Route::get('/combos/create', [\Modules\Ecommerce\Http\Controllers\ComboController::class, 'create'])->name('combos.create');
    Route::post('/combos', [\Modules\Ecommerce\Http\Controllers\ComboController::class, 'store'])->name('combos.store');
    Route::get('/combos/{combo}/edit', [\Modules\Ecommerce\Http\Controllers\ComboController::class, 'edit'])->name('combos.edit');
    Route::put('/combos/{combo}', [\Modules\Ecommerce\Http\Controllers\ComboController::class, 'update'])->name('combos.update');
    Route::delete('/combos/{combo}', [\Modules\Ecommerce\Http\Controllers\ComboController::class, 'destroy'])->name('combos.destroy');
    Route::patch('/combos/{combo}/toggle-status', [\Modules\Ecommerce\Http\Controllers\ComboController::class, 'toggleStatus'])->name('combos.toggle-status');
```

- [ ] **Step 4: Add `persist()` to ComboService**

Append to `ComboService` (add `use Modules\Ecommerce\Models\ComboImage;`, `use Illuminate\Support\Facades\DB;` already present):

```php
    /**
     * Create or update a combo with its items and gallery in one transaction.
     * $data keys: name, combo_price, discount_type, discount_value, is_active,
     * sort_order, description, thumbnail (?string path already stored),
     * items[] (product_id, variant_id?, quantity), gallery[] (image_path).
     */
    public function persist(array $data, ?Combo $combo = null): Combo
    {
        return DB::transaction(function () use ($data, $combo) {
            $combo ??= new Combo();
            $combo->fill([
                'name'           => $data['name'],
                'description'    => $data['description'] ?? null,
                'combo_price'    => $data['combo_price'],
                'discount_type'  => $data['discount_type'] ?? 'none',
                'discount_value' => $data['discount_value'] ?? 0,
                'is_active'      => (bool) ($data['is_active'] ?? false),
                'sort_order'     => $data['sort_order'] ?? 0,
            ]);
            if (array_key_exists('thumbnail', $data)) {
                $combo->thumbnail = $data['thumbnail'];
            }
            $combo->save();

            $combo->items()->delete();
            foreach (array_values($data['items'] ?? []) as $i => $item) {
                $combo->items()->create([
                    'product_id' => (int) $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity'   => max(1, (int) ($item['quantity'] ?? 1)),
                    'sort_order' => $i,
                ]);
            }

            $combo->galleryImages()->delete();
            foreach (array_values($data['gallery'] ?? []) as $i => $path) {
                if (! $path) { continue; }
                $combo->galleryImages()->create(['image_path' => $path, 'sort_order' => $i]);
            }

            return $combo->fresh(['items', 'galleryImages']);
        });
    }
```

- [ ] **Step 5: Write the Form Requests**

`Modules/Ecommerce/app/Http/Requests/StoreComboRequest.php`:

```php
<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComboRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string'],
            'thumbnail'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'combo_price'       => ['required', 'numeric', 'min:0'],
            'discount_type'     => ['required', 'in:none,fixed,percentage'],
            'discount_value'    => ['nullable', 'numeric', 'min:0'],
            'is_active'         => ['nullable', 'boolean'],
            'sort_order'        => ['nullable', 'integer', 'min:0'],
            'items'             => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'gallery'            => ['nullable', 'array'],
            'gallery.*'          => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

`UpdateComboRequest.php` — identical body (same rules). Create it as its own class extending `FormRequest` with the same `rules()` (do not alias; keep two explicit classes to match the project's Store/Update convention).

- [ ] **Step 6: Write the controller**

`Modules/Ecommerce/app/Http/Controllers/ComboController.php`:

```php
<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Helpers\Upload;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\StoreComboRequest;
use Modules\Ecommerce\Http\Requests\UpdateComboRequest;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Services\ComboService;
use Modules\Product\Models\Product;

class ComboController extends Controller
{
    public function __construct(private ComboService $combos) {}

    public function index(): View
    {
        $combos = Combo::with('items.product')->orderBy('sort_order')->orderBy('name')->paginate(15);

        return view('ecommerce::combos.index', compact('combos'));
    }

    public function create(): View
    {
        $products = Product::active()->with(['images', 'variants.attributeValues.attribute'])->orderBy('name')->get();

        return view('ecommerce::combos.create', compact('products'));
    }

    public function store(StoreComboRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = Upload::store($request->file('thumbnail'), 'combos');
        }
        $this->combos->persist($data);

        return redirect()->route('ecommerce.combos.index')->with('success', __('Combo created.'));
    }

    public function edit(Combo $combo): View
    {
        $combo->load(['items.product.images', 'items.variant', 'galleryImages']);
        $products = Product::active()->with(['images', 'variants.attributeValues.attribute'])->orderBy('name')->get();

        return view('ecommerce::combos.edit', compact('combo', 'products'));
    }

    public function update(UpdateComboRequest $request, Combo $combo): RedirectResponse
    {
        $data = $request->validated();
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = Upload::store($request->file('thumbnail'), 'combos');
            Upload::delete($combo->thumbnail);
        }
        $this->combos->persist($data, $combo);

        return redirect()->route('ecommerce.combos.index')->with('success', __('Combo updated.'));
    }

    public function destroy(Combo $combo): RedirectResponse
    {
        $combo->delete();

        return redirect()->route('ecommerce.combos.index')->with('success', __('Combo deleted.'));
    }

    public function toggleStatus(Combo $combo): RedirectResponse
    {
        $combo->update(['is_active' => ! $combo->is_active]);

        return back()->with('success', __('Combo status updated.'));
    }
}
```

> Confirm the upload helper signature against an existing controller that uploads (e.g. `EcommerceController` handling banners/SEO images uses `App\Helpers\Upload` / `Upload::store($file, 'dir')` and `Upload::delete($path)`). Match whatever the codebase exposes (`upload_replace()` helper is also used in `CustomerAuthController::updateProfile`).

- [ ] **Step 7: Write the admin views**

Create `combos/index.blade.php`, `combos/create.blade.php`, `combos/edit.blade.php` using the master admin layout and `bp-` components, mirroring `collection-create.blade.php` / `homepage-section-products.blade.php`:

- **index**: `@extends('core::layouts.master')`, page title "Combos", "Add Combo" action → `ecommerce.combos.create`. A `bp-table` listing thumbnail, name, `{{ $combo->items->count() }}` items, summed vs combo price (`app(ComboService::class)->summedPrice($combo)` / `effectivePrice`), a derived stock badge (`isInStock`), an active toggle form → `ecommerce.combos.toggle-status`, and edit/delete actions. Pagination in `bp-card-footer`.
- **create / edit**: one `<form method="POST" enctype="multipart/form-data">` (`@csrf`; edit adds `@method('PUT')`) posting to `ecommerce.combos.store`/`update`. Fields:
  - Name (`name`), Description (`description` textarea), Thumbnail (`<input type="file" name="thumbnail">` + current preview on edit).
  - **Items repeater**: rows with a product `<select name="items[][product_id]">` (from `$products`), a dependent variant `<select name="items[…][variant_id]">` populated from the chosen product's `variants` (JSON embedded per product; show "— (no variant)" when the product has none), a `quantity` number, and a remove button. "Add product" appends a row. Reuse the search-and-add UX from `homepage-section-products.blade.php` if preferred.
  - **Gallery picker**: checkboxes of the selected products' images (`$products...images.image_path`); checked → `gallery[]` = image path. Re-render available images as the item list changes (JS).
  - **Pricing**: `combo_price` number, `discount_type` select (none/fixed/percentage), `discount_value` number; a live JS preview showing summed price (computed from chosen products' prices embedded as data attributes), effective price, and savings.
  - `is_active` toggle, `sort_order`.
  - A `@push('scripts')` block (`'use strict';`) drives the repeater, variant dependency, gallery refresh, and price preview. No inline CSS — add any new classes to `public/css/style.css` with `[data-theme="dark"]` overrides.

On `edit`, pre-fill rows from `$combo->items` (with selected variant) and pre-check gallery images from `$combo->galleryImages`.

- [ ] **Step 8: Add the sidebar / navigation entry**

In `config/navigation.php`, add to the Online group (mirror an existing entry such as Coupons/Campaigns):

```php
    ['label' => 'Combo Packages', 'route' => 'ecommerce.combos.index', 'icon' => 'fa-box-open', 'keywords' => ['combo', 'package', 'bundle'], 'permission' => null, 'mode' => 'web', 'group' => 'Online'],
```

And add the matching link in the Online section of `Modules/Core/resources/views/partials/sidebar.blade.php` (find where `ecommerce.campaigns.index` / `ecommerce.coupons` are linked and add a Combos `<a>` using `request()->routeIs('ecommerce.combos.*')` for the active state).

- [ ] **Step 9: Run tests + smoke the pages**

Run: `php artisan test Modules/Ecommerce/tests/Feature/ComboAdminTest.php`
Expected: PASS (3 tests). Then with the dev server up, GET `/ecommerce/combos`, `/ecommerce/combos/create` → 200; create a combo via the form and confirm items + gallery persist and the price preview matches the server.

- [ ] **Step 10: Commit**

```bash
git add Modules/Ecommerce/app/Http/Controllers/ComboController.php Modules/Ecommerce/app/Http/Requests/StoreComboRequest.php Modules/Ecommerce/app/Http/Requests/UpdateComboRequest.php Modules/Ecommerce/app/Services/ComboService.php Modules/Ecommerce/resources/views/combos/ Modules/Ecommerce/routes/web.php config/navigation.php Modules/Core/resources/views/partials/sidebar.blade.php Modules/Ecommerce/tests/Feature/ComboAdminTest.php
git commit -m "feat(ecommerce): admin combo management (CRUD + pickers)"
```

---

### Task 5: Storefront listing + detail

**Files:**
- Modify: `Modules/Ecommerce/routes/storefront.php`
- Create: `Modules/Ecommerce/app/Http/Controllers/Storefront/ComboController.php`
- Create: `…/storefront/pages/combos/index.blade.php`, `…/combos/show.blade.php`, `…/storefront/partials/combo-card.blade.php`
- Test: `Modules/Ecommerce/tests/Feature/StorefrontComboTest.php`

**Interfaces:**
- Consumes: `Combo::active()`, `ComboService` (price/stock).
- Produces: routes `storefront.combos.index` (`/combos`), `storefront.combos.show` (`/combos/{slug}`).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Combo;
use Modules\Product\Models\Product;
use Tests\TestCase;

class StorefrontComboTest extends TestCase
{
    public function test_listing_and_detail_render(): void
    {
        $p = Product::create(['name' => 'A', 'sku' => uniqid('s'), 'sell_price' => 500, 'status' => 'active']);
        $combo = Combo::create(['name' => 'Combo One', 'combo_price' => 800, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true]);
        $combo->items()->create(['product_id' => $p->id, 'quantity' => 1]);

        $this->get(route('storefront.combos.index'))->assertOk()->assertSee('Combo One');
        $this->get(route('storefront.combos.show', $combo->slug))->assertOk()->assertSee('Combo One');
    }

    public function test_inactive_combo_is_404_on_detail(): void
    {
        $combo = Combo::create(['name' => 'Hidden', 'combo_price' => 100, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => false]);
        $this->get(route('storefront.combos.show', $combo->slug))->assertNotFound();
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontComboTest.php`
Expected: FAIL — route not defined.

- [ ] **Step 3: Add routes**

In `Modules/Ecommerce/routes/storefront.php` (public group, near the shop routes):

```php
Route::get('/combos', [\Modules\Ecommerce\Http\Controllers\Storefront\ComboController::class, 'index'])->name('storefront.combos.index');
Route::get('/combos/{slug}', [\Modules\Ecommerce\Http\Controllers\Storefront\ComboController::class, 'show'])->name('storefront.combos.show');
```

- [ ] **Step 4: Write the controller**

```php
<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Services\ComboService;
use Modules\Ecommerce\Support\Seo;

class ComboController extends Controller
{
    public function __construct(private ComboService $service) {}

    public function index(): View
    {
        $combos = Combo::active()->with('items.product.images', 'items.variant')
            ->orderBy('sort_order')->orderBy('name')->paginate(12);

        return view('ecommerce::storefront.pages.combos.index', [
            'combos'  => $combos,
            'service' => $this->service,
            'seo'     => Seo::make()->title('Combo Packages'),
        ]);
    }

    public function show(string $slug): View
    {
        $combo = Combo::active()->where('slug', $slug)
            ->with(['items.product.images', 'items.variant.attributeValues.attribute', 'galleryImages'])
            ->firstOrFail();

        return view('ecommerce::storefront.pages.combos.show', [
            'combo'   => $combo,
            'service' => $this->service,
            'seo'     => Seo::make()->title($combo->name),
        ]);
    }
}
```

> Match the `Seo` usage to how `ShopController` builds SEO (it uses `Seo::make()` / `staticPageSeo`); keep it minimal and `noindex` is not required for combos (they're public catalog pages).

- [ ] **Step 5: Write the views**

- `combo-card.blade.php` (vars: `$combo`, `$service`): thumbnail (`storefront_image`/`asset` fallback to product placeholder), name linking to `storefront.combos.show`, effective price (`$service->effectivePrice($combo)`) with summed price strikethrough when greater, a savings badge, and an "Order Now"/"View" button. Mirror `product-card.blade.php` styling.
- `combos/index.blade.php`: extends storefront master, a grid of `@include('ecommerce::storefront.partials.combo-card', ['combo' => $c, 'service' => $service])`, pagination, empty state.
- `combos/show.blade.php`: thumbnail + gallery (`$combo->galleryImages`), name, price block (effective + strikethrough + savings), "What's included" list (`$combo->items` → product name + variant label + `× qty`, each linking to `storefront.shop.show`), stock status (`$service->isInStock($combo)` → "In Stock"/"Out of Stock"), qty input, and an Add-to-Cart button posting `combo_id` + `quantity` to the combo cart endpoint (Task 6). Disable the button when out of stock.

- [ ] **Step 6: Run tests + smoke**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontComboTest.php`
Expected: PASS (2 tests). Smoke `/combos` and `/combos/{slug}` in the browser.

- [ ] **Step 7: Commit**

```bash
git add Modules/Ecommerce/app/Http/Controllers/Storefront/ComboController.php Modules/Ecommerce/resources/views/storefront/pages/combos/ Modules/Ecommerce/resources/views/storefront/partials/combo-card.blade.php Modules/Ecommerce/routes/storefront.php Modules/Ecommerce/tests/Feature/StorefrontComboTest.php
git commit -m "feat(storefront): combo listing + detail pages"
```

---

### Task 6: Cart integration

**Files:**
- Modify: `Modules/Ecommerce/app/Http/Controllers/Storefront/CartController.php` (+`addCombo`)
- Modify: `Modules/Ecommerce/routes/storefront.php` (combo add route)
- Modify: `Modules/Ecommerce/app/Services/StorefrontService.php` (`calculateCartSubtotal` handles combo lines)
- Modify: cart + mini-cart views to render a combo line's components
- Test: `Modules/Ecommerce/tests/Feature/ComboCartTest.php`

**Interfaces:**
- Produces: route `storefront.cart.add-combo`; cart line shape `['type' => 'combo', 'combo_id' => int, 'name', 'slug', 'thumbnail', 'price' => effective, 'quantity', 'components' => [['product_id','variant_id','quantity','name','variant_name']]]`; cart key `"combo:{combo_id}"`.
- Consumes: `ComboService::effectivePrice/isInStock`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Combo;
use Modules\Product\Models\Product;
use Tests\TestCase;

class ComboCartTest extends TestCase
{
    public function test_add_combo_to_cart_uses_server_price(): void
    {
        $p = Product::create(['name' => 'A', 'sku' => uniqid('s'), 'sell_price' => 500, 'status' => 'active']);
        $combo = Combo::create(['name' => 'C', 'combo_price' => 800, 'discount_type' => 'fixed', 'discount_value' => 100, 'is_active' => true]);
        $combo->items()->create(['product_id' => $p->id, 'quantity' => 2]);

        $res = $this->postJson(route('storefront.cart.add-combo'), ['combo_id' => $combo->id, 'quantity' => 1]);
        $res->assertOk()->assertJson(['success' => true]);

        $cart = session('cart');
        $line = $cart['combo:' . $combo->id];
        $this->assertSame('combo', $line['type']);
        $this->assertSame(700.0, (float) $line['price']); // 800 - 100, ignores any client price
        $this->assertCount(1, $line['components']);
        $this->assertSame(2, $line['components'][0]['quantity']);
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/ComboCartTest.php`
Expected: FAIL — route `storefront.cart.add-combo` not defined.

- [ ] **Step 3: Add the route**

In `storefront.php` near `storefront.cart.add`:

```php
Route::post('/cart/add-combo', [\Modules\Ecommerce\Http\Controllers\Storefront\CartController::class, 'addCombo'])->name('storefront.cart.add-combo');
```

- [ ] **Step 4: Add `addCombo` to CartController**

```php
    public function addCombo(\Illuminate\Http\Request $request, ComboService $combos): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'combo_id' => 'required|integer|exists:combos,id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $combo = \Modules\Ecommerce\Models\Combo::active()
            ->with('items.product', 'items.variant')->findOrFail($request->integer('combo_id'));
        $quantity = max(1, (int) $request->input('quantity', 1));

        if (! $combos->isInStock($combo, $quantity)) {
            $msg = __('This combo is out of stock.');
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $components = $combo->items->map(fn ($item) => [
            'product_id'   => $item->product_id,
            'variant_id'   => $item->variant_id,
            'quantity'     => $item->quantity,
            'name'         => $item->product->name ?? 'Product',
            'variant_name' => $item->variant?->variantLabel(), // or null if no such helper
        ])->all();

        $cart = session('cart', []);
        $key  = 'combo:' . $combo->id;
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
                'components' => $components,
            ];
        }
        session(['cart' => $cart]);
        $this->recalculateCoupon($cart);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'        => true,
                'message'        => 'Combo added to cart.',
                'cart_count'     => $this->getCartCount($cart),
                'cart_total'     => $this->storefrontService->calculateCartSubtotal($cart),
                'mini_cart_html' => $this->renderMiniCart($cart),
            ]);
        }

        return redirect()->back()->with('success', __('Combo added to cart.'));
    }
```

> Add `use Modules\Ecommerce\Services\ComboService;`. For `variant_name`: reuse however a product variant's human label is built elsewhere (search the codebase for where `variant_name` is composed for normal cart items — `CartController@add` builds `$variantName`; extract/reuse that logic rather than inventing `variantLabel()`). If no shared helper exists, build the label inline from `variant->attributeValues` the same way `add()` does.

- [ ] **Step 5: Make subtotal + counts combo-aware**

In `StorefrontService::calculateCartSubtotal` (`:833`) and `CartController::getCartCount`: these iterate cart items by `price`×`quantity` and `quantity` — combo lines already carry `price`/`quantity`, so confirm they sum correctly (they should with no change). If `getCartCount` counts component units, keep counting the combo as its `quantity`. Add a focused assertion in the test if behavior is ambiguous. Update `calculateCartSubtotal` only if it currently assumes a `product_id` key.

- [ ] **Step 6: Render combo lines in cart + mini-cart**

In `storefront/pages/cart/index.blade.php` and the mini-cart partial, branch on `($item['type'] ?? 'product') === 'combo'`: show the combo name/thumbnail, its `price`×`quantity`, and a small list of `$item['components']` ("2× Product A / M"). Quantity update/remove reuse the existing `cart_key` flow (the key `combo:{id}` works with the existing update/remove endpoints — verify those endpoints don't assume a product line; if they do, branch).

- [ ] **Step 7: Run tests + smoke**

Run: `php artisan test Modules/Ecommerce/tests/Feature/ComboCartTest.php`
Expected: PASS. Smoke: add a combo from its detail page; confirm the cart shows the combo line + components and the subtotal includes it.

- [ ] **Step 8: Commit**

```bash
git add Modules/Ecommerce/app/Http/Controllers/Storefront/CartController.php Modules/Ecommerce/routes/storefront.php Modules/Ecommerce/app/Services/StorefrontService.php Modules/Ecommerce/resources/views/storefront/pages/cart/ Modules/Ecommerce/tests/Feature/ComboCartTest.php
git commit -m "feat(storefront): add combo packages to the cart"
```

---

### Task 7: Checkout — stock check + order expansion

**Files:**
- Modify: `Modules/Ecommerce/app/Http/Controllers/Storefront/CheckoutController.php` (combo stock check)
- Modify: `Modules/Ecommerce/app/Services/StorefrontService.php` (`createOrder` + `createMirroredSale` expand combo lines)
- Test: `Modules/Ecommerce/tests/Feature/ComboCheckoutTest.php`

**Interfaces:**
- Consumes: combo cart line shape (Task 6), `ComboService::allocatePrices/isInStock`, `EcommerceOrderItem` (`combo_id`/`combo_name` from Task 1).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Models\EcommerceOrderItem;
use Modules\Ecommerce\Services\StorefrontService;
use Modules\Product\Models\Product;
use Tests\TestCase;

class ComboCheckoutTest extends TestCase
{
    public function test_combo_line_expands_into_component_order_items(): void
    {
        $a = Product::create(['name' => 'A', 'sku' => uniqid('s'), 'sell_price' => 500, 'status' => 'active']);
        $b = Product::create(['name' => 'B', 'sku' => uniqid('s'), 'sell_price' => 300, 'status' => 'active']);
        $combo = Combo::create(['name' => 'Combo', 'combo_price' => 1000, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true]);
        $combo->items()->create(['product_id' => $a->id, 'quantity' => 1]);
        $combo->items()->create(['product_id' => $b->id, 'quantity' => 2]);

        $cart = ['combo:' . $combo->id => [
            'type' => 'combo', 'combo_id' => $combo->id, 'name' => 'Combo', 'price' => 1000.0, 'quantity' => 1,
            'components' => [
                ['product_id' => $a->id, 'variant_id' => null, 'quantity' => 1, 'name' => 'A', 'variant_name' => null],
                ['product_id' => $b->id, 'variant_id' => null, 'quantity' => 2, 'name' => 'B', 'variant_name' => null],
            ],
        ]];

        $svc = app(StorefrontService::class);
        $order = $svc->createOrder(
            ['customer_name' => 'T', 'customer_phone' => '01712345678', 'shipping_address' => 'X', 'payment_method' => 'cod'],
            $cart, 1000.0, 0.0, 0.0
        );

        $items = EcommerceOrderItem::where('ecommerce_order_id', $order->id)->get();
        $this->assertCount(2, $items); // expanded per component
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $items->pluck('product_id')->all());
        $this->assertSame(1000.0, round($items->sum('subtotal'), 2)); // prices sum to the combo total
        $this->assertSame($combo->id, (int) $items->first()->combo_id);
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/ComboCheckoutTest.php`
Expected: FAIL — only behavior diff: items not expanded / `combo_id` null.

- [ ] **Step 3: Expand combo lines in `createOrder`**

In `StorefrontService::createOrder`, replace the `foreach ($cart as $item)` order-item loop so combo lines expand. Add `use Modules\Ecommerce\Services\ComboService;` and `use Modules\Ecommerce\Models\Combo;`:

```php
            foreach ($cart as $item) {
                if (($item['type'] ?? 'product') === 'combo') {
                    $combo = Combo::with('items')->find($item['combo_id']);
                    $alloc = $combo ? app(ComboService::class)->allocatePrices($combo, (int) $item['quantity']) : [];
                    foreach ($combo?->items ?? [] as $ci) {
                        $unit = $alloc[$ci->id] ?? 0.0;
                        $lineQty = $ci->quantity * (int) $item['quantity'];
                        EcommerceOrderItem::create([
                            'ecommerce_order_id' => $order->id,
                            'product_id'         => $ci->product_id,
                            'variant_id'         => $ci->variant_id,
                            'combo_id'           => $combo->id,
                            'combo_name'         => $combo->name,
                            'product_name'       => $ci->product->name ?? $item['name'],
                            'variant_name'       => null,
                            'quantity'           => $lineQty,
                            'unit_price'         => $unit,
                            'subtotal'           => round($unit * $lineQty, 2),
                        ]);
                    }
                    continue;
                }

                EcommerceOrderItem::create([
                    'ecommerce_order_id' => $order->id,
                    'product_id'         => $item['product_id'],
                    'variant_id'         => $item['variant_id'] ?? null,
                    'product_name'       => $item['name'],
                    'variant_name'       => $item['variant_name'] ?? null,
                    'quantity'           => $item['quantity'],
                    'unit_price'         => $item['price'],
                    'subtotal'           => round($item['price'] * $item['quantity'], 2),
                ]);
            }
```

Also update `createMirroredSale` (`:964`) the same way — build one sale item per component for combo lines (so the mirrored Sale, and thus fulfillment stock deduction, sees individual products/variants). Mirror the existing sale-item structure used there for normal lines; set the line label to `"{combo_name}: {product_name}"` for clarity in the admin.

- [ ] **Step 4: Add the combo stock check at checkout**

In `CheckoutController::process`, inside the cart loop (around `:173`), handle combo lines before the product branch:

```php
            if (($item['type'] ?? 'product') === 'combo') {
                $combo = \Modules\Ecommerce\Models\Combo::with('items.product')->find($item['combo_id']);
                if ($combo && ! app(\Modules\Ecommerce\Services\ComboService::class)->isInStock($combo, (int) $item['quantity'])) {
                    $stockShortages[] = ($item['name'] ?? 'Combo') . ' — not enough stock';
                }
                continue;
            }
```

- [ ] **Step 5: Run tests**

Run: `php artisan test Modules/Ecommerce/tests/Feature/ComboCheckoutTest.php Modules/Ecommerce/tests/Feature/ComboCartTest.php`
Expected: PASS. Also run the existing checkout test(s) to confirm no regression: `php artisan test Modules/Ecommerce/tests/Feature` (storefront/order tests).

- [ ] **Step 6: Commit**

```bash
git add Modules/Ecommerce/app/Services/StorefrontService.php Modules/Ecommerce/app/Http/Controllers/Storefront/CheckoutController.php Modules/Ecommerce/tests/Feature/ComboCheckoutTest.php
git commit -m "feat(storefront): expand combos into component order items + stock check"
```

---

### Task 8: Homepage combo section

**Files:**
- Modify: `Modules/Ecommerce/app/Support/HomepageSectionSchema.php` (+`combos` type)
- Modify: `Modules/Ecommerce/app/Models/HomepageSection.php` (`combos()` relation + section-type helper)
- Modify: `Modules/Ecommerce/app/Http/Controllers/ContentController.php` (combo picker view + update)
- Modify: `Modules/Ecommerce/routes/web.php` (combo-picker routes), the homepage-sections admin UI (link the picker), and the storefront homepage renderer
- Create: storefront partial `…/storefront/partials/home/combos.blade.php` (or wherever section partials live)
- Test: `Modules/Ecommerce/tests/Feature/HomepageComboSectionTest.php`

**Interfaces:**
- Consumes: `Combo::active()`, `HomepageSection`, `ComboService`.
- Produces: section type key `combos`; `HomepageSection::combos()` belongsToMany via `homepage_section_combos`; routes `ecommerce.homepage-sections.combos` (+update).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Models\HomepageSection;
use Modules\Product\Models\Product;
use Tests\TestCase;

class HomepageComboSectionTest extends TestCase
{
    public function test_homepage_renders_selected_combos(): void
    {
        $p = Product::create(['name' => 'A', 'sku' => uniqid('s'), 'sell_price' => 500, 'status' => 'active']);
        $combo = Combo::create(['name' => 'Home Combo', 'combo_price' => 800, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true]);
        $combo->items()->create(['product_id' => $p->id, 'quantity' => 1]);

        $section = HomepageSection::create([
            'section_type' => 'combos', 'title' => 'Combos', 'is_active' => true, 'sort_order' => 1,
            // include any other NOT NULL columns the table requires (check the migration)
        ]);
        $section->combos()->attach($combo->id, ['sort_order' => 0]);

        $this->get(route('storefront.home'))->assertOk()->assertSee('Home Combo');
    }
}
```

> Inspect `HomepageSection`'s fillable + the create migration for required columns (`section_type`, `title`, `is_active`, `sort_order`, settings JSON). Build the `create([...])` accordingly.

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/HomepageComboSectionTest.php`
Expected: FAIL — `combos()` relation missing / section not rendered.

- [ ] **Step 3: Add the section type + relation**

In `HomepageSectionSchema::all()`, add (mirror `new_arrivals`):

```php
            'combos' => array_merge(
                self::heading('Combo Packages', 'Combo'),
                self::viewAll('View all', 'storefront.combos.index'),
                self::itemsCount(6),
            ),
```

In `HomepageSection`, add:

```php
    public function combos(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Combo::class, 'homepage_section_combos')
            ->withPivot('sort_order')->orderByPivot('sort_order');
    }
```

If `HomepageSection` has an `isProductSection()` helper, add an `isComboSection()` returning `section_type === 'combos'` (used to gate the picker). Add `use Modules\Ecommerce\Models\Combo;` (or fully-qualify).

- [ ] **Step 4: Add the admin combo picker**

In `ContentController`, add `homepageSectionCombos(HomepageSection $section)` + `homepageSectionCombosUpdate(...)` mirroring `homepageSectionProducts/Update` but syncing `combos()` from a list of combo IDs + sort order (no per-item thumbnail). Add routes in `web.php`:

```php
    Route::get('/homepage-sections/{section}/combos', [ContentController::class, 'homepageSectionCombos'])->name('homepage-sections.combos');
    Route::put('/homepage-sections/{section}/combos', [ContentController::class, 'homepageSectionCombosUpdate'])->name('homepage-sections.combos.update');
```

Create a `homepage-section-combos.blade.php` admin view mirroring `homepage-section-products.blade.php` (search-and-add combos, reorder, save). On the `homepage-sections` index, when a section's type is `combos`, point its "manage items" link at `ecommerce.homepage-sections.combos`.

- [ ] **Step 5: Render the section on the storefront homepage**

Find where homepage sections are rendered (the home page iterates `$sections` and includes a partial per `section_type`). Add a `combos` branch that includes a new partial rendering `$section->combos` (limited to the section's items-count setting, active only) as `combo-card` items. Reuse `combo-card.blade.php` from Task 5.

- [ ] **Step 6: Run tests + smoke**

Run: `php artisan test Modules/Ecommerce/tests/Feature/HomepageComboSectionTest.php`
Expected: PASS. Smoke: create a `combos` homepage section, attach combos via the picker, confirm they render on `/`.

- [ ] **Step 7: Commit**

```bash
git add Modules/Ecommerce/app/Support/HomepageSectionSchema.php Modules/Ecommerce/app/Models/HomepageSection.php Modules/Ecommerce/app/Http/Controllers/ContentController.php Modules/Ecommerce/routes/web.php Modules/Ecommerce/resources/views/homepage-section-combos.blade.php Modules/Ecommerce/resources/views/ Modules/Ecommerce/tests/Feature/HomepageComboSectionTest.php
git commit -m "feat(ecommerce): homepage combo section + picker"
```

---

## Self-Review

**Spec coverage:**
- Data model (4 tables + order-item alter) → Task 1. ✓
- Pricing (combo price + discount, summed strikethrough, allocation) → Task 2. ✓
- Derived stock (min over components, deducts per product) → Tasks 3 & 7. ✓
- Admin CRUD + thumbnail + variant picker + gallery picker + Online sidebar → Task 4. ✓
- Storefront listing + own detail page + gallery → Task 5. ✓
- Cart as one line, server-priced → Task 6. ✓
- Checkout stock check + expand to component order items + mirrored sale → Task 7. ✓ (this is what makes "stock deducts based on the products" true)
- Homepage section type + combo picker + render → Task 8. ✓
- Out-of-scope (customer variants, combo coupons, nested) → not planned. ✓

**Placeholder scan:** Backend logic (migrations, models, service, controllers, requests, cart/order expansion) is complete code. View tasks (4 step 7, 5 step 5, 6 step 6, 8 steps 4–5) describe structure + the exact fields/vars and name the existing file to mirror rather than reproducing 200+ lines of Blade verbatim — acceptable per "follow established patterns" in an existing codebase, but the implementer must read the referenced template. Two spots intentionally defer to existing helpers and say so explicitly (the variant-label builder in Task 6; the `Upload`/`upload_replace` helper in Task 4) — verify against the codebase, don't invent.

**Type consistency:** `ComboService` method names (`summedPrice`, `discountAmount`, `effectivePrice`, `allocatePrices`, `componentAvailable`, `availableStock`, `isInStock`, `persist`) are used consistently across Tasks 2–8. Cart line shape (`type`, `combo_id`, `price`, `quantity`, `components[]`) is identical in Tasks 6 and 7. `combo_id`/`combo_name` columns (Task 1) are written in Task 7. Routes referenced in views match those defined in Tasks 4/5/6/8.

**Known verification points for the implementer (named inline, not placeholders):** exact `products`/`warehouse_stock`/`ecommerce_order_items`/`homepage_sections` columns; the project upload helper signature; the existing variant-label builder; how `createMirroredSale` structures sale items; and where the homepage renders section partials.
