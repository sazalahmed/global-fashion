<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Manufacturing\Models\ProductionOrder;
use Modules\Manufacturing\Services\FabricReturnService;
use Modules\Manufacturing\Services\ProductionOrderService;
use Illuminate\Http\Request;

class FabricReturnController extends Controller
{
    public function __construct(
        private FabricReturnService $returnService,
        private ProductionOrderService $orderService
    ) {
    }

    /**
     * Show form to return fabric for a production order.
     */
    public function create(ProductionOrder $productionOrder)
    {
        bpAuthorize('manufacturing.create');
        $productionOrder->load(['materials.rawMaterial', 'factory']);

        return view('manufacturing::production-orders.return-fabric', compact('productionOrder'));
    }

    /**
     * Store a fabric return.
     */
    public function store(Request $request, ProductionOrder $productionOrder)
    {
        bpAuthorize('manufacturing.create');
        $validated = $request->validate([
            'return_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.bom_item_id' => 'nullable|integer',
            'items.*.quantity_returned' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.condition' => 'required|in:good,damaged,partial_usable',
            'items.*.notes' => 'nullable|string',
        ]);

        $this->returnService->create($productionOrder, $validated);

        return redirect()->route('manufacturing.production-orders.show', $productionOrder)
            ->with('success', __('Fabric return recorded successfully.'));
    }
}
