<?php

namespace Modules\Product\Http\Controllers;

use App\Helpers\Upload;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Product\Http\Requests\StoreProductRequest;
use Modules\Product\Http\Requests\UpdateProductRequest;
use Modules\Product\Models\Product;
use Modules\Product\Services\ProductService;
use Modules\Unit\Models\Unit;
use Modules\Variant\Exceptions\VariantHasSalesException;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $service,
        private readonly \Modules\Product\Services\CatalogService $catalog,
    ) {}

    /**
     * Persist a new interleaved order of products + combos for the given
     * context (a category, or the global bucket when category_id is null).
     */
    public function reorderCatalog(Request $request): JsonResponse
    {
        bpAuthorize('products.edit');

        $validated = $request->validate([
            'category_id'  => ['nullable', 'integer', 'exists:categories,id'],
            'items'        => ['required', 'array'],
            'items.*.type' => ['required', 'in:product,combo'],
            'items.*.id'   => ['required', 'integer', 'min:1'],
        ]);

        $this->catalog->reorder($validated['category_id'] ?? null, $validated['items']);

        return response()->json(['success' => true]);
    }

    /**
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        bpAuthorize('products.view');
        $stats = $this->service->getStats();

        // Unified catalog: products AND combos, interleaved by the per-category
        // (or global) ordering. Stats stay product-only.
        $filters = $request->only([
            'search', 'category', 'brand', 'status', 'product_type', 'stock', 'type',
        ]);

        // Whitelist per_page against fixed options so a crafted query string
        // can't force an unbounded page load.
        $perPageOptions = [15, 25, 50, 100];
        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = 15;
        }

        $products = $this->catalog->list($filters, $perPage);

        $categories = Category::active()->ordered()->get();
        $brands = Brand::active()->ordered()->get();

        return view('product::index', [
            'products'           => $products,
            'categories'         => $categories,
            'brands'             => $brands,
            'filters'            => $filters,
            'perPage'            => $perPage,
            'perPageOptions'     => $perPageOptions,
            'totalProducts'      => $stats['total'],
            'activeProducts'     => $stats['active'],
            'lowStockProducts'   => $stats['low_stock'],
            'outOfStockProducts' => $stats['out_of_stock'],
        ]);
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        bpAuthorize('products.create');
        return view('product::create', [
            'categories' => Category::active()->ordered()->with('children', 'children.children')->whereNull('parent_id')->get(),
            'brands'     => Brand::active()->ordered()->get(),
            'units'      => Unit::active()->ordered()->get(),
            'suppliers'  => collect(),
        ]);
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(StoreProductRequest $request)
    {
        bpAuthorize('products.create');
        $data = $request->validated();

        $images = $data['images'] ?? [];
        unset($data['images']);

        $imageOrder = $data['image_order'] ?? [];
        unset($data['image_order']);

        $thumbnailFile = $data['thumbnail'] ?? null;
        unset($data['thumbnail']);

        $seoImageFile = $data['seo_image'] ?? null;
        unset($data['seo_image']);

        $tags = $data['tags'] ?? null;
        unset($data['tags']);

        $stockData = $data['stock'] ?? [];
        unset($data['stock']);

        $categoriesData = $data['categories'] ?? [];
        unset($data['categories']);

        $variantsData = $data['variants'] ?? [];
        unset($data['variants']);

        $defaultVariantIndex = isset($data['default_variant_index']) ? (int) $data['default_variant_index'] : 0;
        unset($data['default_variant_index']);

        $data['created_by'] = auth()->id();
        // Status priority: explicit form value (e.g. Save as Draft) > is_active
        // toggle > existing $data > default 'active'. Mirrors the update path
        // so the active/inactive switch actually persists.
        if ($request->input('action') === 'draft') {
            $data['status'] = 'draft';
        } elseif (empty($data['status'])) {
            $data['status'] = $request->boolean('is_active') ? 'active' : 'inactive';
        }

        // Handle thumbnail upload
        if ($thumbnailFile) {
            $data['thumbnail'] = Upload::store($thumbnailFile, 'products/thumbnails');
        }

        // Handle SEO image upload
        if ($seoImageFile) {
            $data['seo_image'] = Upload::store($seoImageFile, 'products/seo');
        }

        $product = $this->service->create($data, $images, $tags, $imageOrder);

        // Sync many-to-many categories. First selected = primary (matches product.category_id).
        if (!empty($categoriesData)) {
            $primaryId = (int) $data['category_id'];
            $sync = [];
            foreach ($categoriesData as $catId) {
                $sync[(int) $catId] = ['is_primary' => (int) $catId === $primaryId];
            }
            $product->categories()->sync($sync);
        }

        // Save opening stock (simple products only)
        if (!$product->isVariable()) {
            foreach ($stockData as $stock) {
                $qty = (int) ($stock['quantity'] ?? 0);
                if ($qty > 0) {
                    \Modules\Inventory\Models\WarehouseStock::create([
                        'product_id'    => $product->id,
                        'variant_id'    => null,
                        'quantity'      => $qty,
                        'reorder_level' => (int) ($stock['min_alert'] ?? 0),
                    ]);
                }
            }
        }

        if ($product->isVariable() && !empty($variantsData)) {
            $variantService = app(\Modules\Variant\Services\VariantService::class);
            \Illuminate\Support\Facades\DB::transaction(function () use ($product, $variantsData, $variantService, $defaultVariantIndex) {
                $validDefault = array_key_exists($defaultVariantIndex, $variantsData);
                if (!$validDefault) {
                    $defaultVariantIndex = array_key_first($variantsData);
                }

                foreach ($variantsData as $i => $vData) {
                    $valueIds = array_values(array_unique(array_map('intval', $vData['attribute_value_ids'] ?? [])));
                    if (empty($valueIds)) continue;

                    $values = \Modules\Variant\Models\VariantAttributeValue::whereIn('id', $valueIds)
                        ->orderBy('variant_attribute_id')->get();

                    $sku = !empty($vData['sku'])
                        ? trim($vData['sku'])
                        : $variantService->generateVariantSku($product, $values->all());

                    $variant = \Modules\Variant\Models\ProductVariant::create([
                        'product_id' => $product->id,
                        'sku'        => $sku,
                        'barcode'    => $vData['barcode'] ?? null,
                        'cost_price' => $vData['cost_price'] ?? $product->cost_price,
                        'sell_price' => $vData['sell_price'] ?? $product->sell_price,
                        'is_active'  => (bool) ($vData['is_active'] ?? true),
                        'is_default' => $i === $defaultVariantIndex,
                    ]);

                    $variant->attributeValues()->attach($valueIds);

                    $variantQty = (int) (is_array($vData['stock'] ?? null)
                        ? array_sum(array_map('intval', $vData['stock']))
                        : ($vData['stock'] ?? 0));
                    if ($variantQty > 0) {
                        \Modules\Inventory\Models\WarehouseStock::create([
                            'product_id' => $product->id,
                            'variant_id' => $variant->id,
                            'quantity'   => $variantQty,
                        ]);
                    }
                }
            });

            return redirect()->route('products.index')
                ->with('success', __(':count variant(s) created for :name.', ['count' => count($variantsData), 'name' => $product->name]));
        }

        if ($product->isVariable()) {
            return redirect()->route('products.edit', $product)
                ->with('success', __('Product created. Now configure variants below.'));
        }

        return redirect()->route('products.index')
            ->with('success', __('Product created successfully.'));
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        bpAuthorize('products.view');
        $product = $this->service->find($product->id);

        $warehouseStocks = \Modules\Inventory\Models\WarehouseStock::where('product_id', $product->id)
            ->whereNull('variant_id')
            ->get();

        $variants = collect();
        if ($product->isVariable()) {
            $variantService = app(\Modules\Variant\Services\VariantService::class);
            $variants = collect($variantService->getVariantMatrix($product));
        }

        // Lifetime sales aggregates — only revenue-recognised statuses count,
        // matching the convention used across the reporting services.
        $salesAgg = \Modules\Sale\Models\SaleItem::query()
            ->where('sale_items.product_id', $product->id)
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereIn('sales.status', ['confirmed', 'delivered'])
            ->whereNull('sales.deleted_at')
            ->selectRaw('COALESCE(SUM(sale_items.quantity), 0) as qty, COALESCE(SUM(sale_items.subtotal), 0) as revenue')
            ->first();

        $costPrice     = (float) $product->cost_price;
        $totalSold     = (int) ($salesAgg->qty ?? 0);
        $totalRevenue  = (float) ($salesAgg->revenue ?? 0);
        $totalProfit   = $totalRevenue - ($totalSold * $costPrice);

        // Supplier shown on the product view: the product's own supplier if set,
        // otherwise the supplier from the most recent purchase of this product.
        $supplier = null;
        $lastPurchaseItem = \Modules\Purchase\Models\PurchaseItem::where('product_id', $product->id)
            ->whereHas('purchase')
            ->with('purchase.supplier')
            ->latest('id')
            ->first();
        $supplier = $lastPurchaseItem?->purchase?->supplier;

        // Bangladesh lakh formatting — drop ".00" on whole amounts.
        $fmt = static fn ($v): string => bd_number_group((float) $v, fmod((float) $v, 1.0) === 0.0 ? 0 : 2);

        return view('product::show', [
            'product'           => $product,
            'branchStocks'      => $warehouseStocks,
            'variants'          => $variants,
            'salesHistory'      => collect(),
            'stockHistory'      => collect(),
            'purchaseHistory'   => collect(),
            'unitsSoldMonth'    => 0,
            'unitsSoldChange'   => '+0',
            'totalSold'         => $totalSold,
            'purchasePriceFmt'  => $fmt($costPrice),
            'supplier'          => $supplier,
            'totalProfit'       => $totalProfit,
            'totalProfitFmt'    => $fmt($totalProfit),
        ]);
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product)
    {
        bpAuthorize('products.edit');
        $product->load(['category', 'categories', 'brand', 'unit', 'images', 'tags', 'sizeChartOverrides']);

        $variantMatrix = [];
        if ($product->isVariable()) {
            $variantService = app(\Modules\Variant\Services\VariantService::class);
            $variantMatrix = $variantService->getVariantMatrix($product);

            // Attach total stock quantity to each variant
            $stockMap = \Modules\Inventory\Models\WarehouseStock::where('product_id', $product->id)
                ->whereNotNull('variant_id')
                ->get()
                ->groupBy('variant_id');

            foreach ($variantMatrix as &$variant) {
                $variantStock = $stockMap->get($variant['id']) ?? collect();
                $variant['stock'] = (int) $variantStock->sum('quantity');
            }
            unset($variant);
        }

        return view('product::edit', [
            'product'       => $product,
            'categories'    => Category::active()->ordered()->with('children', 'children.children')->whereNull('parent_id')->get(),
            'brands'        => Brand::active()->ordered()->get(),
            'units'         => Unit::active()->ordered()->get(),
            'suppliers'     => collect(),
            'variantMatrix' => $variantMatrix,
        ]);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        bpAuthorize('products.edit');
        $data = $request->validated();

        $images = $data['images'] ?? [];
        unset($data['images']);

        $thumbnailFile = $data['thumbnail'] ?? null;
        unset($data['thumbnail']);

        $seoImageFile = $data['seo_image'] ?? null;
        unset($data['seo_image']);

        $tags = $data['tags'] ?? null;
        unset($data['tags']);

        $removeImages = $data['remove_images'] ?? [];
        unset($data['remove_images']);

        $imageOrder = $data['image_order'] ?? [];
        unset($data['image_order']);

        $stockData = $data['stock'] ?? [];
        unset($data['stock']);

        $categoriesData = $data['categories'] ?? [];
        unset($data['categories']);

        if ($request->input('action') === 'draft') {
            $data['status'] = 'draft';
        }

        // Translate the "Product Active" checkbox into the `status` column.
        // Checkbox sends "1" when on, nothing when off — but `status` is what
        // actually gets persisted. Skip this when the user clicked "Save as
        // Draft" (which already wrote 'draft' into $data['status'] above).
        if (!isset($data['status']) || $data['status'] !== 'draft') {
            $data['status'] = $request->boolean('is_active') ? 'active' : 'inactive';
        }

        // Handle thumbnail upload / removal
        if ($thumbnailFile) {
            // Delete old thumbnail if exists, then store the new one.
            $data['thumbnail'] = Upload::replace($product->thumbnail, $thumbnailFile, 'products/thumbnails');
        } elseif ($request->boolean('remove_thumbnail')) {
            Upload::delete($product->thumbnail);
            $data['thumbnail'] = null;
        }

        // Handle SEO image upload
        if ($seoImageFile) {
            $data['seo_image'] = Upload::replace($product->seo_image, $seoImageFile, 'products/seo');
        }

        $this->service->update($product, $data, $images, $tags, $removeImages, $imageOrder);

        // Sync many-to-many categories. First selected = primary (matches product.category_id).
        if (!empty($categoriesData)) {
            $primaryId = (int) $data['category_id'];
            $sync = [];
            foreach ($categoriesData as $catId) {
                $sync[(int) $catId] = ['is_primary' => (int) $catId === $primaryId];
            }
            $product->categories()->sync($sync);
        }

        // Save stock (simple products only)
        if (!$product->isVariable()) {
            foreach ($stockData as $stock) {
                \Modules\Inventory\Models\WarehouseStock::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'variant_id' => null,
                    ],
                    [
                        'quantity'      => (int) ($stock['quantity'] ?? 0),
                        'reorder_level' => (int) ($stock['min_alert'] ?? 0),
                    ]
                );
            }
        }

        return redirect()->route('products.show', $product)
            ->with('success', __('Product updated successfully.'));
    }

    /**
     * Toggle a product's active/inactive status (AJAX).
     */
    public function toggleStatus(Product $product): JsonResponse
    {
        bpAuthorize('products.edit');
        $product = $this->service->toggleStatus($product);

        return response()->json([
            'success'   => true,
            'status'    => $product->status,
            'is_active' => $product->is_active,
            'message'   => __('Product status updated.'),
        ]);
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        bpAuthorize('products.delete');
        try {
            $this->service->delete($product);

            return redirect()->route('products.index')
                ->with('success', __('Product deleted successfully.'));
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Persist a new ordering. Body: { "ordered_ids": [3, 1, 2, ...] }
     */
    public function reorder(Request $request): JsonResponse
    {
        bpAuthorize('products.edit');
        $validated = $request->validate([
            'ordered_ids'   => 'required|array',
            'ordered_ids.*' => 'integer|exists:products,id',
        ]);

        $this->service->reorder($validated['ordered_ids']);

        return response()->json(['success' => true]);
    }

    /**
     * Duplicate a product.
     */
    public function duplicate(Product $product)
    {
        bpAuthorize('products.create');
        $copy = $this->service->duplicate($product);

        return redirect()->route('products.edit', $copy)
            ->with('success', __('Product duplicated as draft. Review and update as needed.'));
    }

    /**
     * Generate a unique SKU via AJAX.
     */
    public function generateSku(Request $request): JsonResponse
    {
        bpAuthorize('products.create');
        $categoryId = $request->input('category_id') ? (int) $request->input('category_id') : null;
        $sku = $this->service->generateSku($categoryId);

        return response()->json(['sku' => $sku]);
    }

    /**
     * Generate a unique barcode via AJAX.
     */
    public function generateBarcode(): JsonResponse
    {
        bpAuthorize('products.create');
        $barcode = $this->service->generateBarcode();

        return response()->json(['barcode' => $barcode]);
    }

    /**
     * Get variant attributes with values for the attribute builder (AJAX).
     */
    public function getVariantAttributes(): JsonResponse
    {
        bpAuthorize('products.view');
        $attributes = \Modules\Variant\Models\VariantAttribute::active()
            ->ordered()
            ->with(['values' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->withCount(['chartRows as active_chart_rows_count' => fn ($q) => $q->where('is_active', true)])
            ->get();

        return response()->json([
            'attributes' => $attributes->map(function ($attr) {
                return [
                    'id'           => $attr->id,
                    'name'         => $attr->name,
                    'display_type' => $attr->display_type,
                    'has_chart'    => $attr->active_chart_rows_count > 0,
                    'values'       => $attr->values->map(function ($val) {
                        return [
                            'id'         => $val->id,
                            'value'      => $val->value,
                            'color_code' => $val->color_code,
                        ];
                    }),
                ];
            }),
        ]);
    }

    /**
     * Quick-add a new value to an existing variant attribute (AJAX).
     */
    public function storeVariantAttributeValue(Request $request, int $attribute): JsonResponse
    {
        bpAuthorize('products.create');
        $validated = $request->validate([
            'value'      => 'required|string|max:100',
            'color_code' => 'nullable|string|max:20',
        ]);

        $attr = \Modules\Variant\Models\VariantAttribute::findOrFail($attribute);
        $maxSort = $attr->values()->max('sort_order') ?? -1;

        $value = $attr->values()->create([
            'value'      => $validated['value'],
            'color_code' => $validated['color_code'] ?? null,
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json([
            'id'         => $value->id,
            'value'      => $value->value,
            'color_code' => $value->color_code,
        ]);
    }

    /**
     * Returns the chart rows + default cell values for the Size attribute.
     * Powers the per-product Size Chart override accordion on create/edit.
     * If the Size attribute or its chart is missing, returns an empty payload
     * so the JS can decide to hide the section entirely.
     */
    public function sizeChartDefaults(Request $request): JsonResponse
    {
        bpAuthorize('products.view');
        // Prefer the explicitly requested attribute (a product may use any
        // chart-bearing attribute, e.g. "Size (Pant)"); fall back to the
        // canonical "Size" attribute for backward compatibility.
        $attributeId = $request->integer('attribute_id');
        $attr = $attributeId
            ? \Modules\Variant\Models\VariantAttribute::find($attributeId)
            : \Modules\Variant\Models\VariantAttribute::where('name', 'Size')->first();

        if (!$attr) {
            return response()->json(['attribute_id' => null, 'rows' => [], 'defaults' => new \stdClass()]);
        }

        $rows = $attr->chartRows()->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')->get(['id', 'label']);

        // defaults[value_id][row_id] = string  — keyed by value first so the
        // JS can hand a single row dict to each size when rendering.
        $defaults = [];
        if ($rows->isNotEmpty()) {
            $cells = \Modules\Variant\Models\VariantAttributeChartValue::whereIn(
                'variant_attribute_chart_row_id', $rows->pluck('id')
            )->get();
            foreach ($cells as $c) {
                $defaults[(int) $c->variant_attribute_value_id][(int) $c->variant_attribute_chart_row_id] = $c->value;
            }
        }

        return response()->json([
            'attribute_id' => $attr->id,
            'rows'         => $rows,
            'defaults'     => (object) $defaults, // object so empty payload encodes as {}
        ]);
    }

    /**
     * Generate variants for a product (AJAX).
     */
    public function generateVariants(Request $request, Product $product): JsonResponse
    {
        bpAuthorize('products.create');
        $request->validate([
            'attribute_value_ids'   => 'required|array|min:1',
            'attribute_value_ids.*' => 'array|min:1',
            'attribute_value_ids.*.*' => 'integer|exists:variant_attribute_values,id',
        ]);

        $variantService = app(\Modules\Variant\Services\VariantService::class);
        $variants = $variantService->generateVariants($product, $request->input('attribute_value_ids'));

        return response()->json([
            'variants' => $variants->map(function ($variant) {
                return [
                    'id'          => $variant->id,
                    'sku'         => $variant->sku,
                    'barcode'     => $variant->barcode,
                    'cost_price'  => $variant->cost_price,
                    'sell_price'  => $variant->sell_price,
                    'is_active'   => $variant->is_active,
                    'is_default'  => $variant->is_default,
                    'variant_name'=> $variant->variant_name,
                    'values'      => $variant->attributeValues->map(fn($v) => [
                        'attribute' => $v->attribute->name,
                        'value'     => $v->value,
                        'color_code'=> $v->color_code,
                        'value_id'  => $v->id,
                    ]),
                ];
            }),
            'count' => $variants->count(),
        ]);
    }

    /**
     * Bulk update variants for a product (AJAX).
     */
    public function bulkUpdateVariants(Request $request, Product $product): JsonResponse
    {
        bpAuthorize('products.edit');
        $request->validate([
            'variants'                => 'required|array',
            'variants.*.id'           => 'required|integer|exists:product_variants,id',
            'variants.*.sku'          => 'nullable|string|max:100',
            'variants.*.barcode'      => 'nullable|string|max:100',
            'variants.*.cost_price'   => 'nullable|numeric|min:0',
            'variants.*.sell_price'   => 'nullable|numeric|min:0',
            'variants.*.is_active'    => 'nullable|boolean',
            'variants.*.is_default'   => 'nullable|boolean',
            'stock'                   => 'nullable|array',
            'stock.*.variant_id'      => 'required|integer|exists:product_variants,id',
            'stock.*.quantity'        => 'required|integer|min:0',
        ]);

        $variantService = app(\Modules\Variant\Services\VariantService::class);
        $variantsInput = $request->input('variants');

        // Enforce single-default per product: pick the first row marked is_default=1.
        $defaultId = null;
        foreach ($variantsInput as $variantData) {
            if (!empty($variantData['is_default'])) {
                $defaultId = (int) $variantData['id'];
                break;
            }
        }

        foreach ($variantsInput as $variantData) {
            $variant = \Modules\Variant\Models\ProductVariant::where('id', $variantData['id'])
                ->where('product_id', $product->id)
                ->first();
            if ($variant) {
                if ($defaultId !== null) {
                    $variantData['is_default'] = ((int) $variantData['id'] === $defaultId);
                } else {
                    unset($variantData['is_default']);
                }
                $variantService->updateVariant($variant, $variantData);
            }
        }

        // Update stock if provided
        if ($request->has('stock')) {
            foreach ($request->input('stock') as $stockData) {
                \Modules\Inventory\Models\WarehouseStock::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'variant_id' => $stockData['variant_id'],
                    ],
                    [
                        'quantity' => (int) ($stockData['quantity'] ?? 0),
                    ]
                );
            }
        }

        return response()->json(['success' => true, 'message' => 'Variants updated successfully.']);
    }

    /**
     * Toggle a single variant's active state instantly (AJAX).
     *
     * Inactive variants are hidden from the storefront (the product page,
     * variant picker, shop facets and product cards all filter is_active),
     * so this takes effect immediately without a full save.
     */
    public function setVariantActive(Request $request, Product $product, int $variant): JsonResponse
    {
        bpAuthorize('variants.edit');
        $request->validate(['is_active' => 'required|boolean']);

        $variantModel = \Modules\Variant\Models\ProductVariant::where('id', $variant)
            ->where('product_id', $product->id)
            ->firstOrFail();

        $variantModel->update(['is_active' => $request->boolean('is_active')]);

        return response()->json([
            'success'   => true,
            'is_active' => $variantModel->is_active,
        ]);
    }

    /**
     * Delete a single variant (AJAX).
     */
    public function destroyVariant(Product $product, int $variant): JsonResponse
    {
        bpAuthorize('products.delete');
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

    /**
     * Remove all of a product's variants that contain any of the given
     * attribute values. Blocks (422) if any matched variant is referenced by
     * sales or purchase history.
     */
    public function removeVariantValues(Request $request, Product $product): JsonResponse
    {
        bpAuthorize('products.delete');
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
}
