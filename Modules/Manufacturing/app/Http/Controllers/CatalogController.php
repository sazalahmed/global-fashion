<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Manufacturing\Models\Catalog;
use Modules\Manufacturing\Services\CatalogService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(private CatalogService $catalogService)
    {
    }

    /**
     * Display a listing of catalogs.
     */
    public function index(Request $request)
    {
        bpAuthorize('manufacturing.view');
        $catalogs = $this->catalogService->list(
            $request->only(['search', 'is_active'])
        );

        return view('manufacturing::catalogs.index', compact('catalogs'));
    }

    /**
     * Show the form for creating a new catalog.
     */
    public function create()
    {
        bpAuthorize('manufacturing.create');
        return view('manufacturing::catalogs.create');
    }

    /**
     * Store a newly created catalog.
     */
    public function store(Request $request)
    {
        bpAuthorize('manufacturing.create');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $this->catalogService->create($validated);

        return redirect()->route('manufacturing.catalogs.index')->with('success', __('Catalog created successfully.'));
    }

    /**
     * Show the form for editing the specified catalog.
     */
    public function edit(Catalog $catalog)
    {
        bpAuthorize('manufacturing.edit');
        return view('manufacturing::catalogs.edit', compact('catalog'));
    }

    /**
     * Update the specified catalog.
     */
    public function update(Request $request, Catalog $catalog)
    {
        bpAuthorize('manufacturing.edit');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $this->catalogService->update($catalog, $validated);

        return redirect()->route('manufacturing.catalogs.index')->with('success', __('Catalog updated successfully.'));
    }

    /**
     * Toggle the active status of the specified catalog (AJAX).
     */
    public function toggleStatus(Catalog $catalog): \Illuminate\Http\JsonResponse
    {
        bpAuthorize('manufacturing.edit');
        $catalog->update(['is_active' => ! $catalog->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $catalog->is_active,
            'message' => __('Status updated.'),
        ]);
    }

    /**
     * Remove the specified catalog.
     */
    public function destroy(Catalog $catalog)
    {
        bpAuthorize('manufacturing.delete');
        $this->catalogService->delete($catalog);

        return redirect()->route('manufacturing.catalogs.index')->with('success', __('Catalog deleted successfully.'));
    }
}
