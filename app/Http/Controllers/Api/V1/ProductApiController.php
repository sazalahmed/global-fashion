<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Inventory\Models\WarehouseStock;
use Modules\Product\Models\Product;
use Modules\Product\Services\ProductService;
use Modules\Unit\Models\Unit;

class ProductApiController extends BaseApiController
{
    public function __construct(
        private readonly ProductService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $products = $this->service->list($request->all(), $request->input('per_page', 15));

        return $this->paginatedSuccess($products, 'Products retrieved successfully');
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->service->find($id);

        return $this->success(new ProductResource($product), 'Product retrieved');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'sku'                => 'nullable|string|max:100|unique:products,sku',
            'barcode'            => 'nullable|string|max:100',
            'sell_price'         => 'required|numeric|min:0',
            'cost_price'         => 'nullable|numeric|min:0',
            'vat_rate'           => 'nullable|numeric|min:0|max:100',
            'product_type'       => 'required|in:simple,variable,service',
            'category_id'        => 'nullable|integer|exists:categories,id',
            'brand_id'           => 'nullable|integer|exists:brands,id',
            'unit_id'            => 'nullable|integer|exists:units,id',
            'description'        => 'nullable|string',
            'status'             => 'nullable|in:active,inactive',
            'track_stock'        => 'nullable|boolean',
            'min_stock_alert'    => 'nullable|integer|min:0',
            'images'             => 'nullable|array',
            'images.*'           => 'image|max:2048',
            'tags'               => 'nullable|string',
        ]);

        $images = $request->file('images', []);
        $tags = $validated['tags'] ?? null;
        unset($validated['images'], $validated['tags']);

        $validated['created_by'] = auth()->id();

        $product = $this->service->create($validated, $images, $tags);

        return $this->success(new ProductResource($product->load(['category', 'brand'])), 'Product created successfully', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name'               => 'sometimes|required|string|max:255',
            'sku'                => 'nullable|string|max:100|unique:products,sku,' . $id,
            'barcode'            => 'nullable|string|max:100',
            'sell_price'         => 'sometimes|required|numeric|min:0',
            'cost_price'         => 'nullable|numeric|min:0',
            'vat_rate'           => 'nullable|numeric|min:0|max:100',
            'product_type'       => 'nullable|in:simple,variable,service',
            'category_id'        => 'nullable|integer|exists:categories,id',
            'brand_id'           => 'nullable|integer|exists:brands,id',
            'unit_id'            => 'nullable|integer|exists:units,id',
            'description'        => 'nullable|string',
            'status'             => 'nullable|in:active,inactive',
            'track_stock'        => 'nullable|boolean',
            'min_stock_alert'    => 'nullable|integer|min:0',
            'images'             => 'nullable|array',
            'images.*'           => 'image|max:2048',
            'remove_images'      => 'nullable|array',
            'remove_images.*'    => 'integer',
            'tags'               => 'nullable|string',
        ]);

        $images = $request->file('images', []);
        $removeImages = $validated['remove_images'] ?? [];
        $tags = $validated['tags'] ?? null;
        unset($validated['images'], $validated['remove_images'], $validated['tags']);

        $product = $this->service->update($product, $validated, $images, $tags, $removeImages);

        return $this->success(new ProductResource($product->load(['category', 'brand'])), 'Product updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $this->service->delete($product);

        return $this->success(null, 'Product deleted successfully');
    }

    public function stock(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $stocks = WarehouseStock::where('product_id', $id)
            ->get()
            ->map(fn ($s) => [
                'quantity'          => (int) $s->quantity,
                'reserved_quantity' => (int) $s->reserved_quantity,
                'available'         => (int) ($s->quantity - $s->reserved_quantity),
                'reorder_level'     => (int) ($s->reorder_level ?? 0),
            ]);

        return $this->success($stocks, 'Stock levels retrieved');
    }

    public function formOptions(): JsonResponse
    {
        return $this->success([
            'categories' => Category::active()->ordered()->get(['id', 'name', 'parent_id']),
            'brands'     => Brand::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'units'      => Unit::where('status', 'active')->orderBy('name')->get(['id', 'name', 'short_name']),
        ], 'Form options retrieved');
    }
}
