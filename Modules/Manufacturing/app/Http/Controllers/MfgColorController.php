<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Manufacturing\Models\MfgColor;
use Modules\Manufacturing\Services\MfgColorService;
use Illuminate\Http\Request;

class MfgColorController extends Controller
{
    public function __construct(private MfgColorService $colorService)
    {
    }

    /**
     * Display a listing of colors.
     */
    public function index(Request $request)
    {
        bpAuthorize('manufacturing.view');
        $colors = $this->colorService->list(
            $request->only(['search', 'is_active'])
        );

        return view('manufacturing::colors.index', compact('colors'));
    }

    /**
     * Store a newly created color (AJAX).
     */
    public function store(Request $request)
    {
        bpAuthorize('manufacturing.create');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'hex_code' => 'nullable|string|max:7',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $color = $this->colorService->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Color created successfully.',
            'color' => $color,
        ]);
    }

    /**
     * Update the specified color (AJAX).
     */
    public function update(Request $request, MfgColor $color)
    {
        bpAuthorize('manufacturing.edit');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'hex_code' => 'nullable|string|max:7',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $color = $this->colorService->update($color, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Color updated successfully.',
            'color' => $color,
        ]);
    }

    /**
     * Toggle the active status of the specified color (AJAX).
     */
    public function toggleStatus(MfgColor $color): \Illuminate\Http\JsonResponse
    {
        bpAuthorize('manufacturing.edit');
        $color->update(['is_active' => ! $color->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $color->is_active,
            'message' => __('Status updated.'),
        ]);
    }

    /**
     * Remove the specified color (AJAX).
     */
    public function destroy(MfgColor $color)
    {
        bpAuthorize('manufacturing.delete');
        $this->colorService->delete($color);

        return response()->json([
            'success' => true,
            'message' => 'Color deleted successfully.',
        ]);
    }
}
