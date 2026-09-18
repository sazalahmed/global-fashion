<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Manufacturing\Models\RawMaterial;
use Modules\Manufacturing\Services\RawMaterialService;
use Modules\Manufacturing\Services\RawMaterialSupplierService;
use Illuminate\Http\Request;

class RawMaterialController extends Controller
{
    public function __construct(
        private RawMaterialService $rawMaterialService,
        private RawMaterialSupplierService $supplierService
    ) {
    }

    /**
     * Display a listing of raw materials.
     */
    public function index(Request $request)
    {
        bpAuthorize('manufacturing.view');
        $rawMaterials = $this->rawMaterialService->list(
            $request->only(['search', 'category', 'is_active'])
        );

        $categories = RawMaterial::getCategories();

        return view('manufacturing::raw-materials.index', compact('rawMaterials', 'categories'));
    }

    /**
     * Show the form for creating a new raw material.
     */
    public function create()
    {
        bpAuthorize('manufacturing.create');
        $categories = RawMaterial::getCategories();
        $units = RawMaterial::getUnits();
        $suppliers = $this->supplierService->getActiveSuppliers();

        return view('manufacturing::raw-materials.create', compact('categories', 'units', 'suppliers'));
    }

    /**
     * Store a newly created raw material.
     */
    public function store(Request $request)
    {
        bpAuthorize('manufacturing.create');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:fabric,thread,button,zipper,other',
            'unit' => 'required|in:yard,meter,kg,piece,roll',
            'cost_price' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $this->rawMaterialService->create($validated);

        return redirect()->route('manufacturing.raw-materials.index')->with('success', __('Raw material created successfully.'));
    }

    /**
     * Display the specified raw material.
     */
    public function show(RawMaterial $rawMaterial)
    {
        bpAuthorize('manufacturing.view');
        return view('manufacturing::raw-materials.show', compact('rawMaterial'));
    }

    /**
     * Show the form for editing the specified raw material.
     */
    public function edit(RawMaterial $rawMaterial)
    {
        bpAuthorize('manufacturing.edit');
        $categories = RawMaterial::getCategories();
        $units = RawMaterial::getUnits();
        $suppliers = $this->supplierService->getActiveSuppliers();

        return view('manufacturing::raw-materials.edit', compact('rawMaterial', 'categories', 'units', 'suppliers'));
    }

    /**
     * Update the specified raw material.
     */
    public function update(Request $request, RawMaterial $rawMaterial)
    {
        bpAuthorize('manufacturing.edit');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:fabric,thread,button,zipper,other',
            'unit' => 'required|in:yard,meter,kg,piece,roll',
            'cost_price' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $this->rawMaterialService->update($rawMaterial, $validated);

        return redirect()->route('manufacturing.raw-materials.index')->with('success', __('Raw material updated successfully.'));
    }

    /**
     * Toggle the active status of the specified raw material (AJAX).
     */
    public function toggleStatus(RawMaterial $rawMaterial): \Illuminate\Http\JsonResponse
    {
        bpAuthorize('manufacturing.edit');
        $rawMaterial->update(['is_active' => ! $rawMaterial->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $rawMaterial->is_active,
            'message' => __('Status updated.'),
        ]);
    }

    /**
     * Remove the specified raw material.
     */
    public function destroy(RawMaterial $rawMaterial)
    {
        bpAuthorize('manufacturing.delete');
        $this->rawMaterialService->delete($rawMaterial);

        return redirect()->route('manufacturing.raw-materials.index')->with('success', __('Raw material deleted successfully.'));
    }
}
