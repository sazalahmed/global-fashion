<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Manufacturing\Models\ProductionOrder;
use Modules\Manufacturing\Services\FabricIssuanceService;
use Modules\Manufacturing\Services\ProductionOrderService;
use Illuminate\Http\Request;

class FabricIssuanceController extends Controller
{
    public function __construct(
        private FabricIssuanceService $issuanceService,
        private ProductionOrderService $orderService
    ) {
    }

    /**
     * Show form to issue fabric for a production order.
     */
    public function create(ProductionOrder $productionOrder)
    {
        bpAuthorize('manufacturing.create');
        $productionOrder->load(['materials.rawMaterial', 'factory']);

        return view('manufacturing::production-orders.issue-fabric', compact('productionOrder'));
    }

    /**
     * Store a fabric issuance.
     */
    public function store(Request $request, ProductionOrder $productionOrder)
    {
        bpAuthorize('manufacturing.create');
        $validated = $request->validate([
            'issuance_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.bom_item_id' => 'nullable|integer',
            'items.*.quantity_issued' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $this->issuanceService->create($productionOrder, $validated);

        return redirect()->route('manufacturing.production-orders.show', $productionOrder)
            ->with('success', __('Fabric issued successfully.'));
    }
}
