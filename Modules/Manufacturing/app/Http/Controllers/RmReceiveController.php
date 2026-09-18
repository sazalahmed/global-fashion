<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Manufacturing\Models\RmPurchaseOrder;
use Modules\Manufacturing\Services\RmReceiveService;
use Illuminate\Http\Request;

class RmReceiveController extends Controller
{
    public function __construct(private RmReceiveService $receiveService)
    {
    }

    /**
     * Show the form to receive goods for an RM purchase order.
     */
    public function create(RmPurchaseOrder $rmPurchase)
    {
        bpAuthorize('manufacturing.create');
        if (!$rmPurchase->canBeReceived()) {
            return redirect()->route('manufacturing.rm-purchases.show', $rmPurchase)
                ->with('error', __('This purchase order cannot receive goods.'));
        }

        $rmPurchase->load([
            'items.rawMaterial',
            'supplier',
        ]);

        return view('manufacturing::rm-purchases.receive', compact('rmPurchase'));
    }

    /**
     * Store a new GRN (receive goods).
     */
    public function store(Request $request, RmPurchaseOrder $rmPurchase)
    {
        bpAuthorize('manufacturing.create');
        $validated = $request->validate([
            'receive_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.purchase_item_id' => 'required|exists:rm_purchase_items,id',
            'items.*.quantity_received' => 'required|numeric|min:0',
            'items.*.quantity_damaged' => 'nullable|numeric|min:0',
            'items.*.damage_notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->receiveService->create($rmPurchase, $validated);
        } catch (\RuntimeException $e) {
            return redirect()->route('manufacturing.rm-purchases.show', $rmPurchase)
                ->with('error', $e->getMessage());
        }

        return redirect()->route('manufacturing.rm-purchases.show', $rmPurchase)
            ->with('success', __('Goods received successfully.'));
    }
}
