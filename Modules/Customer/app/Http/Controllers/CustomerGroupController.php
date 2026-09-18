<?php

namespace Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Customer\Models\CustomerGroup;

class CustomerGroupController extends Controller
{
    public function index()
    {
        bpAuthorize('customers.view');
        $groups = CustomerGroup::withCount('customers')->latest()->get();

        return view('customer::groups.index', compact('groups'));
    }

    public function store(Request $request)
    {
        bpAuthorize('customers.create');
        $validated = $request->validate([
            'name'                => 'required|string|max:100|unique:customer_groups,name',
            'description'         => 'nullable|string|max:500',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'is_active'           => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        CustomerGroup::create($validated);

        return redirect()->route('customer-groups.index')
            ->with('success', __('Customer group created successfully.'));
    }

    public function update(Request $request, CustomerGroup $group)
    {
        bpAuthorize('customers.edit');
        $validated = $request->validate([
            'name'                => 'required|string|max:100|unique:customer_groups,name,' . $group->id,
            'description'         => 'nullable|string|max:500',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'is_active'           => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $group->update($validated);

        return redirect()->route('customer-groups.index')
            ->with('success', __('Customer group updated successfully.'));
    }

    public function toggleStatus(CustomerGroup $group): \Illuminate\Http\JsonResponse
    {
        bpAuthorize('customers.edit');
        $group->update(['is_active' => ! $group->is_active]);

        return response()->json(['success' => true, 'is_active' => $group->is_active, 'message' => __('Status updated.')]);
    }

    public function destroy(CustomerGroup $group)
    {
        bpAuthorize('customers.delete');
        if ($group->customers()->count() > 0) {
            return back()->with('error', __('Cannot delete — this group has assigned customers. Reassign them first.'));
        }

        $group->delete();

        return redirect()->route('customer-groups.index')
            ->with('success', __('Customer group deleted successfully.'));
    }
}
