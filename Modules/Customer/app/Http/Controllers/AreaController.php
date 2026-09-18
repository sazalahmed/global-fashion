<?php

namespace Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Customer\Models\Area;

class AreaController extends Controller
{
    public function index()
    {
        bpAuthorize('customers.view');
        $areas = Area::with(['children' => fn ($q) => $q->with('children')->orderBy('name')])
            ->roots()
            ->orderBy('name')
            ->get();

        return view('customer::areas.index', compact('areas'));
    }

    public function store(Request $request)
    {
        bpAuthorize('customers.create');
        $validated = $request->validate([
            'name'            => 'required|string|max:150',
            'parent_id'       => 'nullable|exists:areas,id',
            'level'           => 'required|in:division,district,area',
            'delivery_charge' => 'nullable|numeric|min:0',
            'is_active'       => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        Area::create($validated);

        return redirect()->route('customer-areas.index')->with('success', __('Area added successfully.'));
    }

    public function update(Request $request, Area $area)
    {
        bpAuthorize('customers.edit');
        $validated = $request->validate([
            'name'            => 'required|string|max:150',
            'parent_id'       => 'nullable|exists:areas,id',
            'level'           => 'required|in:division,district,area',
            'delivery_charge' => 'nullable|numeric|min:0',
            'is_active'       => 'nullable|boolean',
        ]);

        // A division has no parent; clear any parent when the level is division.
        if (($validated['level'] ?? null) === 'division') {
            $validated['parent_id'] = null;
        }

        // Guard against making an area its own parent.
        if (! empty($validated['parent_id']) && (int) $validated['parent_id'] === $area->id) {
            return back()->with('error', __('An area cannot be its own parent.'));
        }

        $validated['is_active'] = $request->boolean('is_active', true);
        $area->update($validated);

        return redirect()->route('customer-areas.index')->with('success', __('Area updated successfully.'));
    }

    public function toggleStatus(Area $area): JsonResponse
    {
        bpAuthorize('customers.edit');
        $area->update(['is_active' => ! $area->is_active]);

        return response()->json(['success' => true, 'is_active' => $area->is_active, 'message' => __('Status updated.')]);
    }

    public function destroy(Area $area)
    {
        bpAuthorize('customers.delete');
        if ($area->children()->count() > 0) {
            return back()->with('error', __('Cannot delete — this area has sub-areas. Delete children first.'));
        }
        if ($area->customers()->count() > 0) {
            return back()->with('error', __('Cannot delete — customers are assigned to this area.'));
        }

        $area->delete();
        return redirect()->route('customer-areas.index')->with('success', __('Area deleted.'));
    }

    /**
     * Get children of an area (AJAX for cascading select).
     */
    public function children(Area $area): JsonResponse
    {
        bpAuthorize('customers.view');
        $children = $area->children()->active()->orderBy('name')->get(['id', 'name', 'level']);
        return response()->json($children);
    }
}
