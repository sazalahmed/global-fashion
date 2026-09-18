<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Inventory\Models\AdjustmentReason;

class AdjustmentReasonController extends Controller
{
    public function index(Request $request): View
    {
        bpAuthorize('inventory.view');
        $reasons = AdjustmentReason::query()
            ->withCount('adjustments')
            ->when($request->input('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('inventory::adjustment-reasons.index', compact('reasons'));
    }

    public function create(): View
    {
        bpAuthorize('inventory.create');

        return view('inventory::adjustment-reasons.create');
    }

    public function store(Request $request): RedirectResponse
    {
        bpAuthorize('inventory.create');
        $reason = AdjustmentReason::create($this->validated($request));

        return redirect()->route('inventory.adjustment-reasons.index')
            ->with('success', "Adjustment reason \"{$reason->name}\" created.");
    }

    public function edit(AdjustmentReason $adjustmentReason): View
    {
        bpAuthorize('inventory.edit');

        return view('inventory::adjustment-reasons.edit', ['reason' => $adjustmentReason]);
    }

    public function update(Request $request, AdjustmentReason $adjustmentReason): RedirectResponse
    {
        bpAuthorize('inventory.edit');
        $adjustmentReason->update($this->validated($request, $adjustmentReason->id));

        return redirect()->route('inventory.adjustment-reasons.index')
            ->with('success', __('Adjustment reason updated.'));
    }

    public function toggleStatus(AdjustmentReason $adjustmentReason): JsonResponse
    {
        bpAuthorize('inventory.edit');
        $adjustmentReason->update(['is_active' => ! $adjustmentReason->is_active]);

        return response()->json([
            'success'   => true,
            'is_active' => $adjustmentReason->is_active,
            'message'   => __('Status updated.'),
        ]);
    }

    public function destroy(AdjustmentReason $adjustmentReason): RedirectResponse
    {
        bpAuthorize('inventory.delete');

        // Deleting would strip the explanation from historical adjustments, so
        // a reason in use can only be retired.
        if ($adjustmentReason->adjustments()->exists()) {
            return back()->with('error', "Can't delete \"{$adjustmentReason->name}\" — adjustments are using it. Mark it inactive instead.");
        }

        $name = $adjustmentReason->name;
        $adjustmentReason->delete();

        return redirect()->route('inventory.adjustment-reasons.index')
            ->with('success', "Adjustment reason \"{$name}\" deleted.");
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:120', Rule::unique('adjustment_reasons', 'name')->ignore($ignoreId)],
            'is_active'  => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
