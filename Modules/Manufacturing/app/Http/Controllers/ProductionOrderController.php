<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Manufacturing\Models\ProductionOrder;
use Modules\Manufacturing\Services\ProductionOrderService;
use Modules\Manufacturing\Services\FactoryService;
use Modules\Manufacturing\Services\CatalogService;
use Modules\Manufacturing\Services\MfgColorService;
use Modules\Manufacturing\Services\MfgSizeService;
use Modules\Manufacturing\Services\RawMaterialService;
use Modules\Manufacturing\Services\RmStockService;
use Illuminate\Http\Request;

class ProductionOrderController extends Controller
{
    public function __construct(
        private ProductionOrderService $orderService,
        private FactoryService $factoryService,
        private CatalogService $catalogService,
        private MfgColorService $colorService,
        private MfgSizeService $sizeService,
        private RawMaterialService $rawMaterialService
    ) {
    }

    /**
     * Display a listing of production orders.
     */
    public function index(Request $request)
    {
        bpAuthorize('manufacturing.view');
        $orders = $this->orderService->list(
            $request->only(['search', 'factory_id', 'status', 'payment_status', 'from', 'to'])
        );
        $stats = $this->orderService->getStats();
        $factories = $this->factoryService->getActiveFactories();

        return view('manufacturing::production-orders.index', compact('orders', 'stats', 'factories'));
    }

    /**
     * Show the form for creating a new production order.
     */
    public function create()
    {
        bpAuthorize('manufacturing.create');
        $factories = $this->factoryService->getActiveFactories();
        $catalogs = $this->catalogService->getActiveCatalogs();
        $colors = $this->colorService->getActiveColors();
        $sizes = $this->sizeService->getActiveSizes();
        $rawMaterials = $this->rawMaterialService->getActiveRawMaterials();

        return view('manufacturing::production-orders.create', compact(
            'factories', 'catalogs', 'colors', 'sizes', 'rawMaterials'
        ));
    }

    /**
     * Store a newly created production order.
     */
    public function store(Request $request)
    {
        bpAuthorize('manufacturing.create');
        $validated = $request->validate([
            'factory_id' => 'required|exists:factories,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'estimated_making_cost_per_unit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.catalog_id' => 'required|exists:catalogs,id',
            'items.*.color_id' => 'required|exists:mfg_colors,id',
            'items.*.size_id' => 'required|exists:mfg_sizes,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.making_cost_per_unit' => 'nullable|numeric|min:0',
            'items.*.product_id' => 'nullable|integer',
            'items.*.variant_id' => 'nullable|integer',
            'materials' => 'nullable|array',
            'materials.*.raw_material_id' => 'required|exists:raw_materials,id',
            'materials.*.planned_quantity' => 'required|numeric|min:0.0001',
            'materials.*.expected_return_quantity' => 'nullable|numeric|min:0',
            'materials.*.unit_cost' => 'nullable|numeric|min:0',
        ]);

        $validated['status'] = ProductionOrder::STATUS_DRAFT;

        $order = $this->orderService->create($validated);

        return redirect()->route('manufacturing.production-orders.show', $order)
            ->with('success', __('Production Order created successfully.'));
    }

    /**
     * Display the specified production order.
     */
    public function show(int $productionOrder)
    {
        bpAuthorize('manufacturing.view');
        $order = $this->orderService->find($productionOrder);

        return view('manufacturing::production-orders.show', compact('order'));
    }

    /**
     * Show the form for editing the specified production order.
     */
    public function edit(ProductionOrder $productionOrder)
    {
        bpAuthorize('manufacturing.edit');
        if ($productionOrder->status !== ProductionOrder::STATUS_DRAFT) {
            return redirect()->route('manufacturing.production-orders.show', $productionOrder)
                ->with('error', __('Only draft orders can be edited.'));
        }

        $productionOrder->load(['items', 'materials.rawMaterial']);

        $factories = $this->factoryService->getActiveFactories();
        $catalogs = $this->catalogService->getActiveCatalogs();
        $colors = $this->colorService->getActiveColors();
        $sizes = $this->sizeService->getActiveSizes();
        $rawMaterials = $this->rawMaterialService->getActiveRawMaterials();

        return view('manufacturing::production-orders.edit', compact(
            'productionOrder', 'factories', 'catalogs', 'colors', 'sizes', 'rawMaterials'
        ));
    }

    /**
     * Update the specified production order.
     */
    public function update(Request $request, ProductionOrder $productionOrder)
    {
        bpAuthorize('manufacturing.edit');
        $validated = $request->validate([
            'factory_id' => 'required|exists:factories,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.catalog_id' => 'required|exists:catalogs,id',
            'items.*.color_id' => 'required|exists:mfg_colors,id',
            'items.*.size_id' => 'required|exists:mfg_sizes,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.making_cost_per_unit' => 'nullable|numeric|min:0',
            'items.*.product_id' => 'nullable|integer',
            'items.*.variant_id' => 'nullable|integer',
            'materials' => 'nullable|array',
            'materials.*.raw_material_id' => 'required|exists:raw_materials,id',
            'materials.*.planned_quantity' => 'required|numeric|min:0.0001',
            'materials.*.expected_return_quantity' => 'nullable|numeric|min:0',
            'materials.*.unit_cost' => 'nullable|numeric|min:0',
        ]);

        try {
            $this->orderService->update($productionOrder, $validated);
            return redirect()->route('manufacturing.production-orders.show', $productionOrder)
                ->with('success', __('Production Order updated successfully.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified production order.
     */
    public function destroy(ProductionOrder $productionOrder)
    {
        bpAuthorize('manufacturing.delete');
        try {
            $this->orderService->delete($productionOrder);
            return redirect()->route('manufacturing.production-orders.index')
                ->with('success', __('Production Order deleted successfully.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Approve a production order.
     */
    public function approve(ProductionOrder $productionOrder)
    {
        bpAuthorize('manufacturing.edit');
        try {
            $this->orderService->approve($productionOrder);
            return redirect()->route('manufacturing.production-orders.show', $productionOrder)
                ->with('success', __('Production Order approved successfully.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel a production order.
     */
    public function cancel(ProductionOrder $productionOrder, RmStockService $stockService)
    {
        bpAuthorize('manufacturing.edit');
        try {
            $this->orderService->cancel($productionOrder, $stockService);
            return redirect()->route('manufacturing.production-orders.show', $productionOrder)
                ->with('success', __('Production Order cancelled successfully.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Complete a production order.
     */
    public function complete(ProductionOrder $productionOrder)
    {
        bpAuthorize('manufacturing.edit');
        try {
            $this->orderService->complete($productionOrder);
            return redirect()->route('manufacturing.production-orders.show', $productionOrder)
                ->with('success', __('Production Order completed successfully.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
