<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Category\Models\Category;
use Modules\Category\Services\CategoryService;

class CategoryApiController extends BaseApiController
{
    public function __construct(
        private readonly CategoryService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $categories = $this->service->list($request->all(), $request->input('per_page', 15));

        return $this->paginatedSuccess($categories, 'Categories retrieved successfully');
    }

    public function show(int $id): JsonResponse
    {
        $category = Category::with('parent')
            ->withCount('products')
            ->findOrFail($id);

        return $this->success([
            'id'            => $category->id,
            'name'          => $category->name,
            'slug'          => $category->slug,
            'parent_id'     => $category->parent_id,
            'parent_name'   => $category->parent?->name,
            'image'         => $category->image,
            'description'   => $category->description,
            'sort_order'    => $category->sort_order,
            'status'        => $category->status,
            'product_count' => $category->products_count,
        ], 'Category retrieved');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'image'     => 'nullable|image|max:2048',
            'status'    => 'nullable|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $category = $this->service->create($validated);

        return $this->success($category, 'Category created successfully', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name'      => 'sometimes|required|string|max:255',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'image'     => 'nullable|image|max:2048',
            'status'    => 'nullable|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $category = $this->service->update($category, $validated);

        return $this->success($category, 'Category updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $this->service->delete($category);

        return $this->success(null, 'Category deleted successfully');
    }
}
