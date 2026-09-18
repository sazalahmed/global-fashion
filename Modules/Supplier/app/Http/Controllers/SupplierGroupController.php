<?php

namespace Modules\Supplier\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Supplier\Models\SupplierGroup;

class SupplierGroupController extends Controller
{
    public function index()
    {
        bpAuthorize('suppliers.view');
        $groups = SupplierGroup::withCount('suppliers')->latest()->get();

        return view('supplier::groups.index', compact('groups'));
    }

    public function store(Request $request)
    {
        bpAuthorize('suppliers.create');
        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:supplier_groups,name',
            'description' => 'nullable|string|max:500',
            'is_active'   => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        SupplierGroup::create($validated);

        return redirect()->route('supplier-groups.index')
            ->with('success', __('Supplier group created successfully.'));
    }

    public function update(Request $request, SupplierGroup $group)
    {
        bpAuthorize('suppliers.edit');
        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:supplier_groups,name,' . $group->id,
            'description' => 'nullable|string|max:500',
            'is_active'   => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $group->update($validated);

        return redirect()->route('supplier-groups.index')
            ->with('success', __('Supplier group updated successfully.'));
    }

    public function toggleStatus(SupplierGroup $group): \Illuminate\Http\JsonResponse
    {
        bpAuthorize('suppliers.edit');
        $group->update(['is_active' => ! $group->is_active]);

        return response()->json([
            'success'   => true,
            'is_active' => $group->is_active,
            'message'   => __('Status updated.'),
        ]);
    }

    public function destroy(SupplierGroup $group)
    {
        bpAuthorize('suppliers.delete');
        if ($group->suppliers()->count() > 0) {
            return back()->with('error', __('Cannot delete — this group has assigned suppliers. Reassign them first.'));
        }

        $group->delete();

        return redirect()->route('supplier-groups.index')
            ->with('success', __('Supplier group deleted successfully.'));
    }
}
