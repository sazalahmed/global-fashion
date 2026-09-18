<?php

namespace Modules\Brand\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Brand\Models\Brand;
use Modules\Brand\Services\BrandService;
use Modules\Brand\Http\Requests\StoreBrandRequest;
use Modules\Brand\Http\Requests\UpdateBrandRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BrandController extends Controller
{
    public function __construct(private BrandService $brandService)
    {
    }

    /**
     * Display a listing of brands.
     */
    public function index(Request $request)
    {
        bpAuthorize('brands.view');
        $brands = $this->brandService->list($request->only(['search', 'status']));
        $stats = $this->brandService->getStats();

        return view('brand::index', compact('brands', 'stats'));
    }

    /**
     * Show the form for creating a new brand.
     */
    public function create()
    {
        bpAuthorize('brands.create');
        return view('brand::create');
    }

    /**
     * Store a newly created brand.
     */
    public function store(StoreBrandRequest $request)
    {
        bpAuthorize('brands.create');
        $this->brandService->create($request->validated());

        return redirect()->route('brands.index')->with('success', __('Brand created successfully.'));
    }

    /**
     * Quick-store a brand via AJAX (from product create page).
     */
    public function quickStore(Request $request): JsonResponse
    {
        bpAuthorize('brands.create');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $brand = $this->brandService->create([
            'name'   => $validated['name'],
            'status' => 'active',
        ]);

        return response()->json([
            'id'   => $brand->id,
            'name' => $brand->name,
        ]);
    }

    /**
     * Show the form for editing the specified brand.
     */
    public function edit(Brand $brand)
    {
        bpAuthorize('brands.edit');
        return view('brand::edit', compact('brand'));
    }

    /**
     * Update the specified brand.
     */
    public function update(UpdateBrandRequest $request, Brand $brand)
    {
        bpAuthorize('brands.edit');
        $this->brandService->update($brand, $request->validated());

        return redirect()->route('brands.index')->with('success', __('Brand updated successfully.'));
    }

    /**
     * Toggle the active/inactive status of the specified brand.
     */
    public function toggleStatus(Brand $brand): JsonResponse
    {
        bpAuthorize('brands.edit');
        $this->brandService->toggleStatus($brand);

        return response()->json([
            'success'   => true,
            'is_active' => $brand->status === 'active',
            'message'   => __('Status updated.'),
        ]);
    }

    /**
     * Remove the specified brand.
     */
    public function destroy(Brand $brand)
    {
        bpAuthorize('brands.delete');
        $this->brandService->delete($brand);

        return redirect()->route('brands.index')->with('success', __('Brand deleted successfully.'));
    }
}
