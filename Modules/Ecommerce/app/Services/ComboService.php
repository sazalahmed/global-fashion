<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Models\ComboItem;
use Modules\Inventory\Services\InventoryService;

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
        $alloc = [];
        foreach ($this->allocateLineTotals($combo, $comboQty) as $itemId => $lineTotal) {
            $item = $combo->items->firstWhere('id', $itemId);
            $lineQty = $item ? $item->quantity * $comboQty : 0;
            $alloc[$itemId] = $lineQty > 0 ? round($lineTotal / $lineQty, 2) : 0.0;
        }

        return $alloc;
    }

    /**
     * Distribute the combo's effective total across components at the LINE
     * level so the per-component line totals sum EXACTLY to
     * effectivePrice × comboQty (the last component absorbs the remainder).
     * Unlike allocatePrices(), this is free of per-unit rounding drift and is
     * what order/sale expansion stores as each component's subtotal.
     *
     * @return array<int, float> comboItemId => line total
     */
    public function allocateLineTotals(Combo $combo, int $comboQty): array
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

        $lineTotals = [];
        $runningLineTotal = 0.0;
        $lastId = $items->last()?->id;

        foreach ($items as $item) {
            if ($item->id === $lastId) {
                // Last component absorbs the rounding remainder so the totals
                // sum exactly to the combo's effective price.
                $lineTotal = round($target - $runningLineTotal, 2);
            } else {
                $share = $weightTotal > 0 ? $weights[$item->id] / $weightTotal : 1 / max($items->count(), 1);
                $lineTotal = round($target * $share, 2);
                $runningLineTotal += $lineTotal;
            }
            $lineTotals[$item->id] = $lineTotal;
        }

        return $lineTotals;
    }

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

    // ── Customer-selected combo size ──────────────────────────────────────

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
                    // First active variant for a label wins.
                    $map[$av->value] ??= $variant;
                }
            }
        }

        return $map;
    }

    /** The component product's active variant for a chosen size label, or null. */
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

    /** Max combos buildable for a chosen size; null = unconstrained. */
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

    /**
     * Ordered list of sizes selectable for the combo — only the size labels
     * present on EVERY component product (intersection), each flagged in/out of
     * stock for the whole combo.
     *
     * @return array<int, array{value: string, in_stock: bool}>
     */
    public function availableSizes(Combo $combo): array
    {
        $perComponent = [];
        foreach ($combo->items as $item) {
            if (! $item->product) {
                return []; // can't guarantee any size if a product is missing
            }
            $perComponent[] = $this->productSizeVariants($item->product);
        }
        if (empty($perComponent)) {
            return [];
        }

        $common = array_keys($perComponent[0]);
        foreach ($perComponent as $map) {
            $common = array_values(array_intersect($common, array_keys($map)));
        }
        if (empty($common)) {
            return [];
        }

        // Order by the size attribute value sort_order.
        $order = \Modules\Variant\Models\VariantAttributeValue::whereIn('value', $common)
            ->orderBy('sort_order')->orderBy('id')
            ->pluck('value')->unique()->values()->all();
        $ordered = array_values(array_filter($order, fn ($l) => in_array($l, $common, true)));
        foreach ($common as $l) {
            if (! in_array($l, $ordered, true)) {
                $ordered[] = $l;
            }
        }

        return array_map(fn ($label) => [
            'value'    => $label,
            'in_stock' => $this->isInStockForSize($combo, $label, 1),
        ], $ordered);
    }

    /**
     * Create or update a combo with its items and gallery in one transaction.
     * $data keys: name, combo_price, discount_type, discount_value, is_active,
     * sort_order, description, thumbnail (?string path already stored),
     * items[] (product_id, variant_id?, quantity), gallery[] (image_path).
     */
    /**
     * Persist a new display order for combos. The given id order becomes the
     * sort_order (0-based), which drives both the admin list default order and
     * the storefront combos listing.
     *
     * @param  array<int, int|string>  $ids
     */
    public function reorder(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            foreach (array_values($ids) as $i => $id) {
                Combo::whereKey((int) $id)->update(['sort_order' => $i]);
            }
        });
    }

    public function persist(array $data, ?Combo $combo = null): Combo
    {
        return DB::transaction(function () use ($data, $combo) {
            $combo ??= new Combo();
            $combo->fill([
                'name'           => $data['name'],
                'description'    => $data['description'] ?? null,
                'combo_price'    => $data['combo_price'],
                'size_required'  => (bool) ($data['size_required'] ?? false),
                // Combos no longer carry a discount — the admin sets the combo
                // price directly, so effective price == combo price. Force the
                // legacy columns to neutral values regardless of any input.
                'discount_type'  => 'none',
                'discount_value' => 0,
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
                if (! $path) {
                    continue;
                }
                $combo->galleryImages()->create(['image_path' => $path, 'sort_order' => $i]);
            }

            $combo->categories()->sync(array_map('intval', $data['categories'] ?? []));

            return $combo->fresh(['items', 'galleryImages', 'categories']);
        });
    }
}
