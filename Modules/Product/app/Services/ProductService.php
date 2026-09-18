<?php

namespace Modules\Product\Services;

use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Modules\Product\Models\ProductSizeChartValue;
use Modules\Product\Models\Tag;
use Modules\Category\Models\Category;
use App\Helpers\Upload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * Paginated product list with filters.
     * Eager load: category, brand, unit, images
     * Filters: search, category (category_id), brand (brand_id), status, stock, product_type
     */
    public function list(array $filters = [], int $perPage = 15)
    {
        $query = Product::with(['category', 'brand', 'unit', 'images'])
            ->withSum('warehouseStock', 'quantity')
            ->orderByRaw('CASE WHEN position > 0 THEN 0 ELSE 1 END')
            ->orderBy('position')
            ->orderBy('created_at', 'desc');

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['category'])) {
            $query->byCategory((int) $filters['category']);
        }

        if (!empty($filters['brand'])) {
            $query->byBrand((int) $filters['brand']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['product_type'])) {
            $query->byType($filters['product_type']);
        }

        if (!empty($filters['stock'])) {
            match ($filters['stock']) {
                'in_stock'     => $query->inStock(),
                'low_stock'    => $query->lowStock(),
                'out_of_stock' => $query->outOfStock(),
                default        => null,
            };
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Find a single product with all relations.
     */
    public function find(int $id): Product
    {
        return Product::with(['category', 'brand', 'unit', 'images', 'tags', 'creator'])
            ->findOrFail($id);
    }

    /**
     * Create a product with images and tags.
     */
    public function create(array $data, array $images = [], ?string $tags = null, array $imageOrder = []): Product
    {
        return DB::transaction(function () use ($data, $images, $tags, $imageOrder) {
            // Auto-generate SKU if not provided
            if (empty($data['sku'])) {
                $data['sku'] = $this->generateSku($data['category_id'] ?? null);
            }

            // Auto-generate barcode if not provided
            if (empty($data['barcode'])) {
                $data['barcode'] = $this->generateBarcode();
            }

            // Handle boolean fields from checkboxes
            $data = $this->processBooleanFields($data);

            // Default nullable integer fields to avoid null constraint errors
            $data['min_stock_alert'] = $data['min_stock_alert'] ?? 0;
            $data['max_stock_level'] = $data['max_stock_level'] ?? null;

            $product = Product::create($data);

            // Reorder positions if a position was specified
            if (!empty($data['position']) && $data['position'] > 0) {
                $this->reorderPositions($product);
            }

            // Upload images, then apply the gallery order from the form.
            if (!empty($images)) {
                $newImages = $this->uploadImages($product, $images);
                $this->applyImageOrder($product, $imageOrder, $newImages);
            }

            // Sync tags
            if (!empty($tags)) {
                $this->syncTags($product, $tags);
            }

            // Persist any per-product size-chart overrides keyed by value_id → row_id.
            if (!empty($data['size_chart_overrides']) && is_array($data['size_chart_overrides'])) {
                $this->syncSizeChartOverrides($product, $data['size_chart_overrides']);
            }

            return $product;
        });
    }

    /**
     * Update a product with images and tags.
     */
    public function update(Product $product, array $data, array $images = [], ?string $tags = null, array $removeImages = [], array $imageOrder = []): Product
    {
        return DB::transaction(function () use ($product, $data, $images, $tags, $removeImages, $imageOrder) {
            // Handle slug regeneration if name changed
            if (isset($data['name']) && $data['name'] !== $product->name) {
                $data['slug'] = Product::generateUniqueSlug($data['name'], $product->id);
            }

            // Handle boolean fields
            $data = $this->processBooleanFields($data);

            $product->update($data);

            // Reorder positions if position changed
            if (isset($data['position']) && $data['position'] > 0) {
                $this->reorderPositions($product);
            }

            // Remove specified images
            foreach ($removeImages as $imageId) {
                $image = ProductImage::where('id', $imageId)
                    ->where('product_id', $product->id)
                    ->first();
                if ($image) {
                    $this->deleteImage($image);
                }
            }

            // Upload new images
            $newImages = !empty($images) ? $this->uploadImages($product, $images) : [];

            // Apply the gallery order (existing + new) from the form. The first
            // image becomes the storefront's primary/main image.
            $this->applyImageOrder($product, $imageOrder, $newImages);

            // Sync tags
            if ($tags !== null) {
                $this->syncTags($product, $tags);
            }

            // Size-chart overrides — replace this product's set wholesale.
            if (array_key_exists('size_chart_overrides', $data)) {
                $this->syncSizeChartOverrides($product, (array) $data['size_chart_overrides']);
            }

            return $product->fresh(['category', 'brand', 'unit', 'images', 'tags']);
        });
    }

    /**
     * Replace the product's size-chart override set.
     * $payload shape: [ value_id => [ row_id => "string" ] ].
     * Empty/blank cell strings are skipped — admin clearing a cell falls
     * back to the default value from the variant attribute chart.
     */
    private function syncSizeChartOverrides(Product $product, array $payload): void
    {
        $product->sizeChartOverrides()->delete();

        if (empty($payload)) {
            return;
        }

        $rows = [];
        foreach ($payload as $valueId => $byRow) {
            if (!is_array($byRow)) continue;
            foreach ($byRow as $rowId => $value) {
                if ($value === null || trim((string) $value) === '') continue;
                $rows[] = [
                    'product_id'                     => $product->id,
                    'variant_attribute_chart_row_id' => (int) $rowId,
                    'variant_attribute_value_id'     => (int) $valueId,
                    'value'                          => mb_substr(trim((string) $value), 0, 64),
                    'created_at'                     => now(),
                    'updated_at'                     => now(),
                ];
            }
        }

        if (!empty($rows)) {
            \Modules\Product\Models\ProductSizeChartValue::insert($rows);
        }
    }

    /**
     * Toggle a product's active state between 'active' and 'inactive'.
     * Drafts (or any non-active status) flip to active. Returns the product.
     */
    public function toggleStatus(Product $product): Product
    {
        $product->update([
            'status' => $product->status === 'active' ? 'inactive' : 'active',
        ]);

        return $product;
    }

    /**
     * Soft delete a product and clean up images.
     */
    public function delete(Product $product): bool
    {
        // Prevent deletion if product is used in active sales
        $activeSaleItems = \Modules\Sale\Models\SaleItem::where('product_id', $product->id)
            ->whereHas('sale', fn ($q) => $q->whereNotIn('status', ['cancelled']))
            ->count();

        if ($activeSaleItems > 0) {
            throw new \RuntimeException("Cannot delete product — it is used in {$activeSaleItems} active sale item(s).");
        }

        // Prevent deletion if product is used in active purchases
        $activePurchaseItems = \Modules\Purchase\Models\PurchaseItem::where('product_id', $product->id)
            ->whereHas('purchase', fn ($q) => $q->whereNotIn('status', ['cancelled']))
            ->count();

        if ($activePurchaseItems > 0) {
            throw new \RuntimeException("Cannot delete product — it is used in {$activePurchaseItems} active purchase item(s).");
        }

        // Prevent deletion if product still has stock on hand
        $totalStock = \Modules\Inventory\Models\WarehouseStock::where('product_id', $product->id)
            ->where('quantity', '>', 0)
            ->sum('quantity');

        if ($totalStock > 0) {
            throw new \RuntimeException("Cannot delete product — {$totalStock} units still in stock.");
        }

        return DB::transaction(function () use ($product) {
            // Delete all images from storage
            foreach ($product->images as $image) {
                Upload::delete($image->image_path);
            }

            $product->delete();
            return true;
        });
    }

    /**
     * Auto-generate SKU from category prefix + padded number.
     * Format: {CATEGORY_PREFIX}-{PADDED_ID}
     * Example: ELE-00045
     */
    public function generateSku(?int $categoryId): string
    {
        $category = Category::find($categoryId);
        $prefix = $category
            ? strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $category->name), 0, 3))
            : 'PRD';

        if (strlen($prefix) < 3) {
            $prefix = str_pad($prefix, 3, 'X');
        }

        // Find the highest existing number for this prefix
        $lastProduct = Product::withTrashed()
            ->where('sku', 'like', $prefix . '-%')
            ->orderByRaw("CAST(SUBSTRING(sku, ?) AS UNSIGNED) DESC", [strlen($prefix) + 2])
            ->first();

        $nextNumber = 1;
        if ($lastProduct) {
            $lastNumber = (int) substr($lastProduct->sku, strlen($prefix) + 1);
            $nextNumber = $lastNumber + 1;
        }

        return $prefix . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a unique EAN-13 barcode (880 = Bangladesh).
     */
    public function generateBarcode(): string
    {
        do {
            // 880 prefix (Bangladesh) + 9 random digits
            $code = '880' . str_pad(random_int(0, 999999999), 9, '0', STR_PAD_LEFT);

            // Calculate EAN-13 check digit
            $sum = 0;
            for ($i = 0; $i < 12; $i++) {
                $sum += (int) $code[$i] * ($i % 2 === 0 ? 1 : 3);
            }
            $checkDigit = (10 - ($sum % 10)) % 10;
            $barcode = $code . $checkDigit;

        } while (Product::withTrashed()->where('barcode', $barcode)->exists());

        return $barcode;
    }

    /**
     * Upload images for a product. Returns the created ProductImage records in
     * the order the files were given, so callers can map "new image" tokens from
     * the gallery-order payload back to the persisted rows.
     *
     * @return ProductImage[]
     */
    public function uploadImages(Product $product, array $files): array
    {
        $hasExistingImages = $product->images()->count() > 0;
        $sortOrder = $product->images()->max('sort_order') ?? -1;
        $created = [];

        foreach ($files as $index => $file) {
            $sortOrder++;
            $path = Upload::store($file, 'products/' . $product->id);

            $created[] = $product->images()->create([
                'image_path' => $path,
                'alt_text'   => $product->name,
                'sort_order' => $sortOrder,
                'is_primary' => !$hasExistingImages && $index === 0,
            ]);
        }

        return $created;
    }

    /**
     * Apply the gallery order submitted by the edit/create form.
     *
     * $order is a list of tokens: "e<id>" for an existing image, "n<k>" for the
     * k-th newly uploaded image (index into $newImages). Each image's sort_order
     * is set to its position, and the first image is marked primary (the
     * storefront uses is_primary for the main/detail image). Any image not named
     * in the order is appended at the end and never primary.
     *
     * @param ProductImage[] $newImages
     */
    private function applyImageOrder(Product $product, array $order, array $newImages): void
    {
        if (empty($order)) {
            return;
        }

        $position = 0;
        $orderedIds = [];

        foreach ($order as $token) {
            $token = (string) $token;
            $image = null;

            if (str_starts_with($token, 'e')) {
                $image = $product->images()->whereKey((int) substr($token, 1))->first();
            } elseif (str_starts_with($token, 'n')) {
                $image = $newImages[(int) substr($token, 1)] ?? null;
            }

            if (!$image) {
                continue;
            }

            $image->update(['sort_order' => $position, 'is_primary' => $position === 0]);
            $orderedIds[] = $image->id;
            $position++;
        }

        // Safety net: any image the payload didn't mention goes to the end.
        $product->images()->whereNotIn('id', $orderedIds ?: [0])->get()
            ->each(function (ProductImage $image) use (&$position) {
                $image->update(['sort_order' => $position++, 'is_primary' => false]);
            });
    }

    /**
     * Delete a single product image.
     */
    public function deleteImage(ProductImage $image): void
    {
        $wasPrimary = $image->is_primary;
        $productId = $image->product_id;

        Upload::delete($image->image_path);
        $image->delete();

        // If deleted image was primary, set next image as primary
        if ($wasPrimary) {
            $nextImage = ProductImage::where('product_id', $productId)
                ->orderBy('sort_order')
                ->first();
            if ($nextImage) {
                $nextImage->update(['is_primary' => true]);
            }
        }
    }

    /**
     * Sync tags from a comma-separated string.
     * Creates new tags on-the-fly if they don't exist.
     */
    public function syncTags(Product $product, string $tagString): void
    {
        $tagNames = array_filter(array_map('trim', explode(',', $tagString)));
        $tagIds = [];

        foreach ($tagNames as $name) {
            if (empty($name)) {
                continue;
            }

            $tag = Tag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
            $tagIds[] = $tag->id;
        }

        $product->tags()->sync($tagIds);
    }

    /**
     * Get stats for the product index page.
     */
    public function getStats(): array
    {
        return [
            'total'        => Product::count(),
            'active'       => Product::where('status', 'active')->count(),
            'low_stock'    => Product::lowStock()->count(),
            'out_of_stock' => Product::outOfStock()->count(),
        ];
    }

    /**
     * Duplicate a product (copy all fields except SKU/barcode, copy images & tags).
     */
    public function duplicate(Product $product): Product
    {
        return DB::transaction(function () use ($product) {
            $newData = $product->replicate()->toArray();
            $newData['name'] = $product->name . ' (Copy)';
            $newData['slug'] = Product::generateUniqueSlug($newData['name']);
            $newData['sku'] = $this->generateSku($product->category_id);
            // Generate a fresh barcode for the copy rather than leaving it blank (Bug_86).
            $newData['barcode'] = $this->generateBarcode();
            $newData['status'] = 'draft';

            $newProduct = Product::create($newData);

            // Copy images (create new files)
            foreach ($product->images as $image) {
                $newPath = Upload::copy($image->image_path, 'products/' . $newProduct->id);
                if ($newPath) {
                    $newProduct->images()->create([
                        'image_path' => $newPath,
                        'alt_text'   => $image->alt_text,
                        'sort_order' => $image->sort_order,
                        'is_primary' => $image->is_primary,
                    ]);
                }
            }

            // Copy the variant matrix (new SKU/barcode per variant) WITHOUT stock —
            // the copy starts at zero so the user sets the initial stock for each
            // variant on the edit page they're redirected to (Bug_85).
            foreach ($product->variants as $variant) {
                $newVariant = $newProduct->variants()->create([
                    'sku'             => $this->generateVariantSkuForCopy($newProduct->sku, $variant->sku, $product->sku),
                    'barcode'         => $this->generateBarcode(),
                    'cost_price'      => $variant->cost_price,
                    'sell_price'      => $variant->sell_price,
                    'wholesale_price' => $variant->wholesale_price,
                    'weight'          => $variant->weight,
                    'image_path'      => $variant->image_path,
                    'is_active'       => $variant->is_active,
                    'is_default'      => $variant->is_default,
                ]);
                $newVariant->attributeValues()->attach($variant->attributeValues->pluck('id')->all());
            }

            // Copy the per-product size chart overrides. Without them the copy
            // falls back to the global chart, so a product whose collar/chest/
            // length/sleeve had been measured for it specifically came back
            // showing the generic defaults.
            //
            // Keyed on the chart row and the attribute value (the size), both of
            // which are global — so the source product's rows carry over to the
            // copy untouched apart from the product id.
            $overrides = $product->sizeChartOverrides->map(fn ($o) => [
                'product_id'                     => $newProduct->id,
                'variant_attribute_chart_row_id' => $o->variant_attribute_chart_row_id,
                'variant_attribute_value_id'     => $o->variant_attribute_value_id,
                'value'                          => $o->value,
                'created_at'                     => now(),
                'updated_at'                     => now(),
            ])->all();

            if (! empty($overrides)) {
                ProductSizeChartValue::insert($overrides);
            }

            // Copy tags
            $newProduct->tags()->sync($product->tags->pluck('id'));

            // Copy category assignments (many-to-many pivot incl. is_primary).
            // replicate() only copies the single category_id column, leaving the
            // category_product pivot empty — so the parent (and any other selected)
            // categories were lost on duplication. Mirror the original's pivot.
            $categorySync = $product->categories->mapWithKeys(fn ($cat) => [
                $cat->id => ['is_primary' => (bool) $cat->pivot->is_primary],
            ])->all();
            if (! empty($categorySync)) {
                $newProduct->categories()->sync($categorySync);
            }

            return $newProduct;
        });
    }

    /**
     * Build a variant SKU for a duplicated product: keep the original variant's
     * attribute suffix but swap the old product SKU prefix for the new one
     * (e.g. POL-00001-M with new product POL-00002 -> POL-00002-M).
     */
    private function generateVariantSkuForCopy(string $newProductSku, ?string $oldVariantSku, ?string $oldProductSku): string
    {
        $oldVariantSku = (string) $oldVariantSku;
        $oldProductSku = (string) $oldProductSku;

        if ($oldProductSku !== '' && str_starts_with($oldVariantSku, $oldProductSku)) {
            return $newProductSku . substr($oldVariantSku, strlen($oldProductSku));
        }

        // Fallback: prefix the new product SKU so it stays unique to the copy.
        return $oldVariantSku !== '' ? $newProductSku . '-' . $oldVariantSku : $newProductSku;
    }

    /**
     * Reorder product positions to avoid conflicts.
     * Shifts other products at the same position and above by +1.
     */
    private function reorderPositions(Product $product): void
    {
        Product::where('id', '!=', $product->id)
            ->where('position', '>=', $product->position)
            ->where('position', '>', 0)
            ->increment('position');
    }

    /**
     * Persist a new ordering. Each id's position in the array becomes its position value.
     */
    public function reorder(array $orderedIds): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $id) {
                Product::where('id', $id)->update(['position' => $index + 1]);
            }
        });
    }

    /**
     * Process boolean fields from form checkboxes/toggles.
     * Checkbox fields are absent when unchecked, so we need to default them to false.
     */
    private function processBooleanFields(array $data): array
    {
        $boolFields = ['show_in_pos', 'track_stock', 'allow_negative_stock', 'ecom_sync', 'ecom_visible'];

        foreach ($boolFields as $field) {
            $data[$field] = !empty($data[$field]);
        }

        return $data;
    }
}
