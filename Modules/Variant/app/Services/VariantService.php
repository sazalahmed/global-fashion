<?php

namespace Modules\Variant\Services;

use Modules\Variant\Exceptions\VariantHasSalesException;
use Modules\Variant\Models\VariantAttribute;
use Modules\Variant\Models\VariantAttributeValue;
use Modules\Variant\Models\ProductVariant;
use Modules\Product\Models\Product;
use Modules\Inventory\Models\WarehouseStock;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VariantService
{
    // ── Attribute Management ──

    /**
     * Get paginated list of variant attributes with optional filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = VariantAttribute::withCount('values')->ordered();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('display_name', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filters['status']) && in_array($filters['status'], ['active', 'inactive'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Find a single variant attribute with its values.
     */
    public function find(int $id): VariantAttribute
    {
        return VariantAttribute::with('values')->findOrFail($id);
    }

    /**
     * Create a variant attribute with its values.
     */
    public function createAttribute(array $data): VariantAttribute
    {
        return DB::transaction(function () use ($data) {
            $values = $data['values'] ?? [];
            unset($data['values']);

            $attribute = VariantAttribute::create($data);

            foreach ($values as $index => $valueData) {
                $attribute->values()->create([
                    'value'      => $valueData['name'],
                    'color_code' => $valueData['color_code'] ?? null,
                    'sort_order' => $valueData['sort_order'] ?? $index,
                ]);
            }

            return $attribute->load('values');
        });
    }

    /**
     * Update a variant attribute and reconcile its values.
     *
     * Values are matched by id: submitted values carrying an id are updated in
     * place, new ones are created, and existing values absent from the payload
     * are deleted — but ONLY when no product variant references them. Updating
     * in place is critical: it preserves the value ids that product_variant_values
     * pivots point at. The old delete-all-recreate approach reassigned ids and
     * silently orphaned every variant that used this attribute.
     *
     * @throws \RuntimeException If a removed value is still used by a product variant.
     */
    public function updateAttribute(VariantAttribute $attribute, array $data): VariantAttribute
    {
        return DB::transaction(function () use ($attribute, $data) {
            $values = $data['values'] ?? [];
            unset($data['values']);

            $attribute->update($data);

            $keepIds = [];
            foreach ($values as $index => $valueData) {
                $payload = [
                    'value'      => $valueData['name'],
                    'color_code' => $valueData['color_code'] ?? null,
                    'sort_order' => $valueData['sort_order'] ?? $index,
                ];

                $id = $valueData['id'] ?? null;
                $existing = $id ? $attribute->values()->whereKey($id)->first() : null;

                if ($existing) {
                    $existing->update($payload);   // keep id → pivot references stay valid
                    $keepIds[] = $existing->id;
                } else {
                    $keepIds[] = $attribute->values()->create($payload)->id;
                }
            }

            // Delete values the admin removed — but never one still referenced by
            // a product variant, which would orphan that variant's attribute link.
            $removable = $attribute->values()->whereNotIn('id', $keepIds ?: [0])->get();
            foreach ($removable as $val) {
                $inUse = DB::table('product_variant_values')
                    ->where('variant_attribute_value_id', $val->id)->exists();
                if ($inUse) {
                    throw new \RuntimeException(
                        "Can't remove the value \"{$val->value}\" — it is used by existing product variants. Remove it from those products first."
                    );
                }
                $val->delete();
            }

            return $attribute->load('values');
        });
    }

    /**
     * Delete a variant attribute if not used by any product variant.
     *
     * @throws \RuntimeException If the attribute is in use by product variants.
     */
    public function deleteAttribute(VariantAttribute $attribute): bool
    {
        $inUse = ProductVariant::whereHas('attributeValues', function ($q) use ($attribute) {
            $q->where('variant_attribute_id', $attribute->id);
        })->exists();

        if ($inUse) {
            throw new \RuntimeException(
                'Cannot delete this attribute because it is used by existing product variants.'
            );
        }

        // Cascade delete handles values via FK constraint
        return $attribute->delete();
    }

    /**
     * Toggle a variant attribute's active/inactive status.
     */
    public function toggleStatus(VariantAttribute $attribute): VariantAttribute
    {
        $attribute->update(['status' => $attribute->status === 'active' ? 'inactive' : 'active']);

        return $attribute;
    }

    /**
     * Get statistics for the variant attributes index page.
     */
    public function getStats(): array
    {
        return [
            'total_attributes' => VariantAttribute::count(),
            'total_values'     => VariantAttributeValue::count(),
            'products_using'   => ProductVariant::distinct('product_id')->count('product_id'),
        ];
    }

    // ── Variant Generation ──

    /**
     * Generate product variants from attribute value ID combinations.
     *
     * @param Product $product     The parent product.
     * @param array   $attributeValueIds Array of arrays, e.g. [[1,2], [5,6]].
     *                                   Each inner array is a set of value IDs for one attribute.
     */
    public function generateVariants(Product $product, array $attributeValueIds): Collection
    {
        return DB::transaction(function () use ($product, $attributeValueIds) {
            $combinations = $this->cartesianProduct($attributeValueIds);

            // The target matrix is exactly the cartesian product the caller asked
            // for. Hash each target combo so we can both skip existing combos and
            // detect stale ones.
            $targetKeys = [];
            foreach ($combinations as $combo) {
                $valueIds = is_array($combo) ? $combo : [$combo];
                $targetKeys[$this->comboKey($valueIds)] = true;
            }

            // Index every existing variant by its sorted value-id signature so
            // we can skip combos that already exist — repeat calls are now
            // idempotent. This is required because the edit page auto-fires
            // generate every time the admin commits an attribute selection.
            // Load the full attribute values (+ their attribute) rather than just
            // ids: reused variants are returned to the caller and serialized via
            // $value->attribute->name, which is null if variant_attribute_id was
            // never selected.
            $existingByKey = [];
            foreach ($product->variants()->with('attributeValues.attribute')->get() as $v) {
                $key = $this->comboKey($v->attributeValues->pluck('id')->all());
                $existingByKey[$key] = $v;
            }

            // Prune stale combos: any existing variant whose signature is no
            // longer in the target matrix. This fires when the admin adds a new
            // attribute dimension (e.g. Color-only → Color×Size), which turns the
            // old single-value combos into orphans. Best effort — variants with
            // sales/purchase history are preserved to avoid dangling references.
            foreach ($existingByKey as $key => $variant) {
                if (isset($targetKeys[$key]) || $this->variantIsReferenced($variant->id)) {
                    continue;
                }
                WarehouseStock::where('product_id', $product->id)
                    ->where('variant_id', $variant->id)->delete();
                $variant->attributeValues()->detach();
                $variant->forceDelete();
                unset($existingByKey[$key]);
            }

            $needsDefault = !$product->variants()->where('is_default', true)->exists();
            $variants = collect();
            $i = 0;

            foreach ($combinations as $combo) {
                $valueIds = is_array($combo) ? $combo : [$combo];
                $key = $this->comboKey($valueIds);

                if (isset($existingByKey[$key])) {
                    $variants->push($existingByKey[$key]);
                    $i++;
                    continue;
                }

                $attributeValues = VariantAttributeValue::with('attribute')
                    ->whereIn('id', $valueIds)
                    ->orderBy('variant_attribute_id')
                    ->get();

                $sku = $this->generateVariantSku($product, $attributeValues->all());

                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku'        => $sku,
                    'cost_price' => $product->cost_price,
                    'sell_price' => $product->sell_price,
                    'is_active'  => true,
                    'is_default' => $needsDefault && $i === 0,
                ]);

                $variant->attributeValues()->attach($valueIds);
                $existingByKey[$key] = $variant;
                $variants->push($variant);
                $i++;
            }

            // If pruning removed the default and nothing new claimed it, promote
            // a surviving variant so the product is never left without a default.
            $this->reassignDefault($product->id);

            // A product with generated variants is, by definition, variable.
            // Generation runs via its own AJAX call (separate from the main
            // product-form save), so persist the type here — otherwise the
            // product can keep product_type='simple' while owning variants,
            // which makes the edit page show "Simple" for a variable product.
            if ($variants->isNotEmpty() && ! $product->isVariable()) {
                $product->update(['product_type' => 'variable']);
            }

            return $variants;
        });
    }

    /** Sorted value-id signature so [1,3,2] and [3,1,2] hash the same. */
    private function comboKey(array $valueIds): string
    {
        $ids = array_map('intval', $valueIds);
        sort($ids);
        return implode('-', $ids);
    }

    /**
     * Update a single product variant.
     */
    public function updateVariant(ProductVariant $variant, array $data): ProductVariant
    {
        $variant->update($data);

        return $variant;
    }

    /**
     * Whether a variant is referenced by any transactional table that lacks a
     * self-healing FK. Hard-deleting such a variant would dangle these rows,
     * so deletion is blocked when any reference exists. (Tables with
     * nullOnDelete FKs self-heal and are intentionally not checked.)
     *
     * Each item table is joined to its own parent so a soft-deleted sale,
     * purchase, GRN, or purchase return no longer counts as "history" —
     * otherwise a variant could never be removed after its only referencing
     * transaction was deleted, since the item row itself carries no
     * deleted_at and survives its parent's soft delete.
     */
    private function variantIsReferenced(int $variantId): bool
    {
        $tables = [
            'sale_items'            => ['sales', 'sale_id'],
            'purchase_items'        => ['purchases', 'purchase_id'],
            'grn_items'             => ['goods_receive_notes', 'goods_receive_note_id'],
            'purchase_return_items' => ['purchase_returns', 'purchase_return_id'],
        ];

        foreach ($tables as $itemTable => [$parentTable, $foreignKey]) {
            $exists = DB::table($itemTable)
                ->join($parentTable, "{$itemTable}.{$foreignKey}", '=', "{$parentTable}.id")
                ->where("{$itemTable}.variant_id", $variantId)
                ->whereNull("{$parentTable}.deleted_at")
                ->exists();

            if ($exists) {
                return true;
            }
        }
        return false;
    }

    /**
     * Force-delete a product variant + its stock. Blocks if it has sales or purchase history.
     *
     * @throws VariantHasSalesException
     */
    public function deleteVariant(ProductVariant $variant): bool
    {
        $productId  = $variant->product_id;
        $wasDefault = (bool) $variant->is_default;

        return DB::transaction(function () use ($variant, $productId, $wasDefault) {
            if ($this->variantIsReferenced($variant->id)) {
                throw new VariantHasSalesException(
                    "Can't delete {$variant->sku} — it has sales or purchase history. Disable it (uncheck Active) instead."
                );
            }

            WarehouseStock::where('product_id', $productId)
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
     * attribute values. All-or-nothing: if any matched variant has sales or
     * purchase history, throws and deletes nothing. Unrelated variants are
     * untouched.
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

        $productId = $product->id;

        return DB::transaction(function () use ($variants, $productId) {
            $blockedSkus = $variants->filter(fn ($v) => $this->variantIsReferenced($v->id))->pluck('sku')->all();
            if (!empty($blockedSkus)) {
                throw new VariantHasSalesException(
                    "Can't remove " . implode(', ', $blockedSkus) .
                    " — they have sales or purchase history. Disable them (uncheck Active) instead."
                );
            }

            $wasDefault = $variants->contains(fn ($v) => $v->is_default);
            $ids = $variants->pluck('id')->all();

            WarehouseStock::where('product_id', $productId)
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
     * already does; otherwise promotes the first active variant. Relies on the
     * variant being force-deleted (not soft-deleted) before this runs, so the
     * removed row is not seen as a candidate.
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

    /**
     * Get variant matrix for a product's edit UI.
     *
     * Returns each variant with its associated attribute values.
     */
    public function getVariantMatrix(Product $product): array
    {
        $variants = $product->variants()
            ->with(['attributeValues.attribute'])
            ->get()
            // Order variants ascending by the configured sort_order of their
            // attribute values, with a natural (number-aware) tiebreaker so
            // sizes/numbers read S,M,L,XL / 64,128,256 (Bug_80).
            ->sortBy(function (ProductVariant $variant) {
                return $variant->attributeValues
                    ->sortBy('variant_attribute_id')
                    ->map(fn (VariantAttributeValue $v) => str_pad((string) ($v->sort_order ?? 0), 6, '0', STR_PAD_LEFT) . ':' . $v->value)
                    ->implode('|');
            }, SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return $variants->map(function (ProductVariant $variant) {
            return [
                'id'              => $variant->id,
                'sku'             => $variant->sku,
                'barcode'         => $variant->barcode,
                'cost_price'      => $variant->cost_price,
                'sell_price'      => $variant->sell_price,
                'wholesale_price' => $variant->wholesale_price,
                'weight'          => $variant->weight,
                'image_path'      => $variant->image_path,
                'stock'           => $variant->total_stock,
                'is_active'       => $variant->is_active,
                'is_default'      => $variant->is_default,
                'attributes'      => $variant->attributeValues->map(function (VariantAttributeValue $val) {
                    return [
                        'attribute_id'   => $val->variant_attribute_id,
                        'attribute_name' => $val->attribute->name,
                        'value_id'       => $val->id,
                        'value'          => $val->value,
                        'color_code'     => $val->color_code,
                    ];
                })->values()->all(),
            ];
        })->all();
    }

    /**
     * Generate a variant SKU from parent product and attribute values.
     *
     * Format: {PRODUCT_SKU}-{ABBREV1}-{ABBREV2}
     * Abbreviation rules:
     *   - 3 chars or fewer: use as-is, uppercased
     *   - Contains digits (e.g. "128GB"): extract the numeric portion
     *   - Otherwise: first 3 uppercase letters
     */
    public function generateVariantSku(Product $product, array $attributeValues): string
    {
        $baseSku = $product->sku ?? 'PROD';
        $parts = [$baseSku];

        foreach ($attributeValues as $attrValue) {
            $value = $attrValue instanceof VariantAttributeValue
                ? $attrValue->value
                : (string) $attrValue;

            $parts[] = $this->abbreviateValue($value);
        }

        $sku = implode('-', $parts);

        // Ensure uniqueness by appending a counter if needed
        $originalSku = $sku;
        $counter = 1;

        while (ProductVariant::withTrashed()->where('sku', $sku)->exists()) {
            $sku = $originalSku . '-' . $counter++;
        }

        return $sku;
    }

    // ── Private Helpers ──

    /**
     * Abbreviate a variant value for SKU generation.
     */
    private function abbreviateValue(string $value): string
    {
        $trimmed = trim($value);

        // If 3 chars or fewer, use as-is uppercased
        if (mb_strlen($trimmed) <= 3) {
            return strtoupper($trimmed);
        }

        // If contains digits (e.g. "128GB", "256MB"), extract the numeric portion
        if (preg_match('/(\d+)/', $trimmed, $matches)) {
            return $matches[1];
        }

        // Otherwise, first 3 uppercase letters
        return strtoupper(mb_substr($trimmed, 0, 3));
    }

    /**
     * Compute the cartesian product of multiple arrays.
     *
     * @param array $sets Array of arrays, e.g. [[1,2], [5,6]]
     * @return array       e.g. [[1,5], [1,6], [2,5], [2,6]]
     */
    private function cartesianProduct(array $sets): array
    {
        if (empty($sets)) {
            return [];
        }

        $result = [[]];

        foreach ($sets as $set) {
            $append = [];
            foreach ($result as $existing) {
                foreach ($set as $item) {
                    $append[] = array_merge($existing, [$item]);
                }
            }
            $result = $append;
        }

        return $result;
    }
}
