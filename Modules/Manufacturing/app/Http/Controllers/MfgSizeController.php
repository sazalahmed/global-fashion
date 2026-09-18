<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Manufacturing\Models\MfgSize;
use Modules\Manufacturing\Services\MfgSizeService;
use Illuminate\Http\Request;

class MfgSizeController extends Controller
{
    public function __construct(private MfgSizeService $sizeService)
    {
    }

    /**
     * Display a listing of sizes.
     */
    public function index(Request $request)
    {
        bpAuthorize('manufacturing.view');
        $sizes = $this->sizeService->list(
            $request->only(['search', 'is_active'])
        );

        return view('manufacturing::sizes.index', compact('sizes'));
    }

    /**
     * Store a newly created size (AJAX).
     */
    public function store(Request $request)
    {
        bpAuthorize('manufacturing.create');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $size = $this->sizeService->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Size created successfully.',
            'size' => $size,
        ]);
    }

    /**
     * Update the specified size (AJAX).
     */
    public function update(Request $request, MfgSize $size)
    {
        bpAuthorize('manufacturing.edit');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $size = $this->sizeService->update($size, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Size updated successfully.',
            'size' => $size,
        ]);
    }

    /**
     * Toggle the active status of the specified size (AJAX).
     */
    public function toggleStatus(MfgSize $size): \Illuminate\Http\JsonResponse
    {
        bpAuthorize('manufacturing.edit');
        $size->update(['is_active' => ! $size->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $size->is_active,
            'message' => __('Status updated.'),
        ]);
    }

    /**
     * Remove the specified size (AJAX).
     */
    public function destroy(MfgSize $size)
    {
        bpAuthorize('manufacturing.delete');
        $this->sizeService->delete($size);

        return response()->json([
            'success' => true,
            'message' => 'Size deleted successfully.',
        ]);
    }
}
