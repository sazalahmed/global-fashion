<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Brand\Models\Brand;
use Modules\Brand\Services\BrandService;

class BrandApiController extends BaseApiController
{
    public function __construct(
        private readonly BrandService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $brands = $this->service->list($request->all(), $request->input('per_page', 15));

        return $this->paginatedSuccess($brands, 'Brands retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'   => 'required|string|max:255',
            'logo'   => 'nullable|image|max:2048',
            'status' => 'nullable|in:active,inactive',
        ]);

        $brand = $this->service->create($validated);

        return $this->success($brand, 'Brand created successfully', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $brand = Brand::findOrFail($id);

        $validated = $request->validate([
            'name'   => 'sometimes|required|string|max:255',
            'logo'   => 'nullable|image|max:2048',
            'status' => 'nullable|in:active,inactive',
        ]);

        $brand = $this->service->update($brand, $validated);

        return $this->success($brand, 'Brand updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $brand = Brand::findOrFail($id);
        $this->service->delete($brand);

        return $this->success(null, 'Brand deleted successfully');
    }
}
