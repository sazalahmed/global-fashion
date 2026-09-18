<?php

namespace Modules\PurchaseReturn\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PurchaseReturn\Http\Requests\StorePurchaseReturnTypeRequest;
use Modules\PurchaseReturn\Http\Requests\UpdatePurchaseReturnTypeRequest;
use Modules\PurchaseReturn\Models\PurchaseReturnType;

class PurchaseReturnTypeController extends Controller
{
    public function index()
    {
        bpAuthorize('purchases.view');
        $types = PurchaseReturnType::withCount('purchaseReturns')->latest()->get();

        return view('purchasereturn::types.index', compact('types'));
    }

    public function store(StorePurchaseReturnTypeRequest $request)
    {
        bpAuthorize('purchases.create');
        $validated = $request->validated();

        $validated['is_active'] = $request->boolean('is_active', true);
        PurchaseReturnType::create($validated);

        return redirect()->route('purchase-return-types.index')
            ->with('success', __('Return type created successfully.'));
    }

    public function update(UpdatePurchaseReturnTypeRequest $request, PurchaseReturnType $type)
    {
        bpAuthorize('purchases.edit');
        $validated = $request->validated();

        $validated['is_active'] = $request->boolean('is_active', true);
        $type->update($validated);

        return redirect()->route('purchase-return-types.index')
            ->with('success', __('Return type updated successfully.'));
    }

    public function destroy(PurchaseReturnType $type)
    {
        bpAuthorize('purchases.delete');
        if ($type->purchaseReturns()->count() > 0) {
            return back()->with('error', __('Cannot delete — this type is used by existing returns.'));
        }

        $type->delete();

        return redirect()->route('purchase-return-types.index')
            ->with('success', __('Return type deleted successfully.'));
    }

    public function toggleStatus(PurchaseReturnType $type): \Illuminate\Http\JsonResponse
    {
        bpAuthorize('purchases.edit');
        $type->update(['is_active' => ! $type->is_active]);

        return response()->json(['success' => true, 'is_active' => $type->is_active, 'message' => __('Status updated.')]);
    }
}
