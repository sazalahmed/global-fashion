<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Http\Requests\StoreAdjustmentRequest;
use Modules\Inventory\Models\AdjustmentReason;
use Modules\Inventory\Models\StockAdjustment;
use Modules\Inventory\Services\InventoryService;
use Modules\Inventory\Services\ReconciliationService;
use Modules\Product\Models\Product;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $service,
        private readonly ReconciliationService $reconciliationService,
    ) {}

    /**
     * Display the stock overview.
     */
    public function index(Request $request)
    {
        bpAuthorize('inventory.view');
        $stats = $this->service->getStats();
        $filters = $request->only(['search', 'stock_status']);
        $stockLevels = $this->service->getStockLevels($filters);
        $stockTotals = $this->service->getStockLevelTotals($filters);

        return view('inventory::index', compact('stats', 'stockLevels', 'stockTotals'));
    }

    /**
     * Display stock alerts (low stock, out of stock).
     */
    public function alerts()
    {
        bpAuthorize('inventory.view');
        $alerts = $this->service->getStockAlerts();

        return view('inventory::alerts', [
            'outOfStockItems'   => $alerts['outOfStock'],
            'belowReorderItems' => $alerts['lowStock'],
            'totalAlerts'       => $alerts['total'],
        ]);
    }

    /**
     * Display the stock ledger.
     */
    public function ledger(Request $request)
    {
        bpAuthorize('inventory.view');
        $products = Product::orderBy('name')->get(['id', 'name', 'sku']);
        $history = null;
        $product = null;

        if ($request->filled('product_id')) {
            $product = Product::findOrFail($request->product_id);
            $history = $this->service->getStockHistory(
                $product->id,
                null,
                $request->only(['source_type']),
            );
        }

        return view('inventory::ledger', compact('products', 'history', 'product'));
    }

    /**
     * Display the stock ledger (product-wise standalone page).
     */
    public function stockLedger(Request $request)
    {
        bpAuthorize('inventory.view');
        $products = Product::orderBy('name')->get(['id', 'name', 'sku']);
        $history = null;
        $product = null;
        $openingStock = $totalIn = $totalOut = $closingStock = 0;

        if ($request->filled('product_id')) {
            $product = Product::findOrFail($request->product_id);
            $ledger = $this->service->getStockLedger(
                $product->id,
                $request->only(['date_from', 'date_to', 'movement_type']),
            );
            $history = $ledger['history'];
            $openingStock = $ledger['opening'];
            $totalIn = $ledger['in'];
            $totalOut = $ledger['out'];
            $closingStock = $ledger['closing'];
        }

        return view('inventory::stock-ledger', compact(
            'products', 'history', 'product',
            'openingStock', 'totalIn', 'totalOut', 'closingStock',
        ));
    }

    // -------------------------------------------------------
    // Stock Adjustments
    // -------------------------------------------------------

    /**
     * Display the stock adjustments list.
     */
    public function adjustments(Request $request)
    {
        bpAuthorize('inventory.view');
        $adjustments = $this->service->listAdjustments(
            $request->only(['search', 'status', 'type', 'reason', 'date_from', 'date_to']),
        );

        return view('inventory::adjustments', compact('adjustments'));
    }

    /**
     * Show the form for creating a new stock adjustment.
     */
    public function createAdjustment(Request $request)
    {
        bpAuthorize('inventory.create');
        $products = Product::orderBy('name')
            ->select('id', 'name', 'sku', 'cost_price', 'product_type')
            ->with(['variants' => fn ($q) => $q->where('is_active', true)->select('id', 'product_id', 'sku', 'cost_price')->with('attributeValues.attribute')])
            ->get();

        // Optional pre-selection when arriving from the Inventory list "Adjust" action.
        $preselectProduct = $request->integer('product_id') ?: null;
        $preselectVariant = $request->integer('variant_id') ?: null;
        $adjustmentReasons = AdjustmentReason::active()->ordered()->get();

        return view('inventory::adjustments-create', compact('products', 'preselectProduct', 'preselectVariant', 'adjustmentReasons'));
    }

    /**
     * Store a new stock adjustment.
     */
    public function storeAdjustment(StoreAdjustmentRequest $request)
    {
        bpAuthorize('inventory.create');
        $adjustment = $this->service->createAdjustment(
            $request->validated(),
            $request->input('items'),
        );

        return redirect()->route('inventory.adjustments.show', $adjustment)
            ->with('success', "Adjustment {$adjustment->adjustment_number} created.");
    }

    /**
     * Display a stock adjustment.
     */
    public function showAdjustment(StockAdjustment $adjustment)
    {
        bpAuthorize('inventory.view');
        $adjustment = $this->service->findAdjustment($adjustment->id);

        return view('inventory::adjustments-show', compact('adjustment'));
    }

    /**
     * Approve a stock adjustment.
     */
    public function approveAdjustment(StockAdjustment $adjustment)
    {
        bpAuthorize('inventory.edit');
        try {
            $this->service->approveAdjustment($adjustment);
            return back()->with('success', "Adjustment {$adjustment->adjustment_number} approved. Stock updated.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Edit a draft stock adjustment.
     */
    public function editAdjustment(StockAdjustment $adjustment)
    {
        bpAuthorize('inventory.edit');
        if ($adjustment->status !== 'draft') {
            return redirect()->route('inventory.adjustments.show', $adjustment)->with('error', __('Only draft adjustments can be edited.'));
        }

        $adjustment = $this->service->findAdjustment($adjustment->id);
        $products = Product::orderBy('name')
            ->select('id', 'name', 'sku', 'cost_price', 'product_type')
            ->with(['variants' => fn ($q) => $q->where('is_active', true)->select('id', 'product_id', 'sku', 'cost_price')->with('attributeValues.attribute')])
            ->get();
        $adjustmentReasons = AdjustmentReason::active()->ordered()->get();

        return view('inventory::adjustments-edit', compact('adjustment', 'products', 'adjustmentReasons'));
    }

    /**
     * Update a draft stock adjustment.
     */
    public function updateAdjustment(StoreAdjustmentRequest $request, StockAdjustment $adjustment)
    {
        bpAuthorize('inventory.edit');
        try {
            $this->service->updateAdjustment($adjustment, $request->validated(), $request->input('items'));
            return redirect()->route('inventory.adjustments.show', $adjustment)->with('success', "Adjustment {$adjustment->adjustment_number} updated.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel a stock adjustment (reverse stock if approved).
     */
    public function cancelAdjustment(StockAdjustment $adjustment)
    {
        bpAuthorize('inventory.edit');
        try {
            $this->service->cancelAdjustment($adjustment);
            return back()->with('success', "Adjustment {$adjustment->adjustment_number} cancelled.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete a draft stock adjustment.
     */
    public function destroyAdjustment(StockAdjustment $adjustment)
    {
        bpAuthorize('inventory.delete');
        try {
            $this->service->deleteAdjustment($adjustment);
            return redirect()->route('inventory.adjustments')->with('success', __('Adjustment deleted.'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Print view for a stock adjustment.
     */
    public function printAdjustment(StockAdjustment $adjustment)
    {
        bpAuthorize('inventory.view');
        $adjustment = $this->service->findAdjustment($adjustment->id);

        return view('inventory::adjustments-print', compact('adjustment'));
    }

    /**
     * Display the stock reconciliation page.
     */
    public function reconciliation(Request $request)
    {
        bpAuthorize('inventory.view');
        $stats = $this->reconciliationService->getStats();
        $discrepancies = collect();

        if ($request->has('run')) {
            $discrepancies = $this->reconciliationService->reconcile(
                $request->input('product_id') ? (int) $request->input('product_id') : null,
            );
        }

        return view('inventory::reconciliation', compact('stats', 'discrepancies'));
    }
}
