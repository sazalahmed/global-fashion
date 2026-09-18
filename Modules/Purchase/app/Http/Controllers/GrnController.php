<?php

namespace Modules\Purchase\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Purchase\Models\Purchase;
use Modules\Purchase\Services\GrnService;
use Illuminate\Http\Request;

class GrnController extends Controller
{
    public function __construct(private GrnService $grnService)
    {
    }

    /**
     * Show the form to receive stock for a purchase order.
     */
    public function create(Purchase $purchase)
    {
        bpAuthorize('purchases.view');
        if (!$purchase->canBeReceived()) {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', __('This purchase order cannot receive stock.'));
        }

        $purchase->load([
            'items.product', 'items.variant.attributeValues.attribute',
            'supplier', 'branch',
        ]);

        return view('purchase::receive', compact('purchase'));
    }

    /**
     * Store a new GRN (receive stock).
     */
    public function store(Request $request, Purchase $purchase)
    {
        bpAuthorize('purchases.create');
        $validated = $request->validate([
            'received_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.purchase_item_id' => 'required|exists:purchase_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|integer',
            'items.*.quantity_received' => 'required|numeric|min:0',
            'items.*.quantity_accepted' => 'nullable|numeric|min:0',
            'items.*.quantity_rejected' => 'nullable|numeric|min:0',
            'items.*.reject_reason' => 'nullable|string|max:500',
        ]);

        $this->grnService->create($purchase, $validated);

        return redirect()->route('purchases.show', $purchase)
            ->with('success', __('Stock received successfully.'));
    }
}
