<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Manufacturing\Models\ProductionOrder;
use Modules\Manufacturing\Models\ProductionLot;
use Modules\Manufacturing\Services\ProductionLotService;
use Modules\Manufacturing\Services\ProductionOrderService;
use Illuminate\Http\Request;

class ProductionLotController extends Controller
{
    public function __construct(
        private ProductionLotService $lotService,
        private ProductionOrderService $orderService
    ) {
    }

    /**
     * Show form to receive a production lot.
     */
    public function create(ProductionOrder $productionOrder)
    {
        bpAuthorize('manufacturing.create');
        $productionOrder->load(['items.catalog', 'items.color', 'items.size', 'factory']);

        return view('manufacturing::production-orders.receive-lot', compact('productionOrder'));
    }

    /**
     * Store a production lot.
     */
    public function store(Request $request, ProductionOrder $productionOrder)
    {
        bpAuthorize('manufacturing.create');
        $validated = $request->validate([
            'delivery_date' => 'required|date',
            'making_cost' => 'nullable|numeric|min:0',
            'delivery_charge' => 'nullable|numeric|min:0',
            'other_costs' => 'nullable|numeric|min:0',
            'other_costs_note' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.production_order_item_id' => 'required|exists:production_order_items,id',
            'items.*.quantity_received' => 'required|integer|min:0',
            'items.*.quantity_damaged' => 'nullable|integer|min:0',
        ]);

        try {
            $lot = $this->lotService->create($productionOrder, $validated);
            return redirect()->route('manufacturing.production-orders.show', $productionOrder)
                ->with('success', __('Production lot received successfully.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show lot details.
     */
    public function show(ProductionOrder $productionOrder, ProductionLot $lot)
    {
        bpAuthorize('manufacturing.view');
        $lot = $this->lotService->find($lot->id);

        return view('manufacturing::production-orders.lot-details', compact('productionOrder', 'lot'));
    }
}
