<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Helpers\Upload;
use App\Http\Controllers\Controller;
use Modules\Manufacturing\Models\RmPurchaseOrder;
use Modules\Manufacturing\Services\RmPurchaseService;
use Modules\Manufacturing\Services\RawMaterialService;
use Modules\Manufacturing\Services\RawMaterialSupplierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RmPurchaseController extends Controller
{
    public function __construct(
        private RmPurchaseService $purchaseService,
        private RawMaterialService $rawMaterialService,
        private RawMaterialSupplierService $supplierService
    ) {
    }

    /**
     * Display a listing of RM purchase orders.
     */
    public function index(Request $request)
    {
        bpAuthorize('manufacturing.view');
        $purchases = $this->purchaseService->list(
            $request->only(['search', 'supplier_id', 'status', 'payment_status', 'from', 'to'])
        );
        $stats = $this->purchaseService->getStats();
        $suppliers = $this->supplierService->getActiveSuppliers();

        return view('manufacturing::rm-purchases.index', compact('purchases', 'stats', 'suppliers'));
    }

    /**
     * Show the form for creating a new RM purchase order.
     */
    public function create()
    {
        bpAuthorize('manufacturing.create');
        $suppliers = $this->supplierService->getActiveSuppliers();
        $rawMaterials = $this->rawMaterialService->getActiveRawMaterials();

        return view('manufacturing::rm-purchases.create', compact('suppliers', 'rawMaterials'));
    }

    /**
     * Store a newly created RM purchase order.
     */
    public function store(Request $request)
    {
        bpAuthorize('manufacturing.create');
        $validated = $request->validate([
            'po_date' => 'required|date',
            'supplier_id' => 'required|exists:raw_material_suppliers,id',
            'expected_delivery_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx|max:5120',
            'shipping_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Handle file uploads for each item
        $itemFiles = $request->file('items', []);
        foreach ($validated['items'] as $idx => &$item) {
            if (isset($itemFiles[$idx]['attachment'])) {
                $file = $itemFiles[$idx]['attachment'];
                $item['attachment_path'] = \App\Helpers\Upload::store($file, 'rm-purchase-attachments');
                $item['attachment_name'] = $file->getClientOriginalName();
            }
        }
        unset($item);

        $action = $request->input('action', 'draft');
        $validated['status'] = match ($action) {
            'submit' => RmPurchaseOrder::STATUS_PENDING,
            'approve' => RmPurchaseOrder::STATUS_APPROVED,
            default => RmPurchaseOrder::STATUS_DRAFT,
        };

        $purchase = $this->purchaseService->create($validated);

        if ($action === 'approve') {
            $this->purchaseService->approve($purchase);
        }

        return redirect()->route('manufacturing.rm-purchases.show', $purchase)
            ->with('success', __('RM Purchase Order created successfully.'));
    }

    /**
     * Display the specified RM purchase order.
     */
    public function show(RmPurchaseOrder $rmPurchase)
    {
        bpAuthorize('manufacturing.view');
        $rmPurchase->load([
            'supplier',
            'items.rawMaterial',
            'receives.items.rawMaterial',
            'receives.receiver',
            'creator',
            'approver',
        ]);

        return view('manufacturing::rm-purchases.show', compact('rmPurchase'));
    }

    /**
     * Show the form for editing an RM purchase order.
     */
    public function edit(RmPurchaseOrder $rmPurchase)
    {
        bpAuthorize('manufacturing.edit');
        if (!in_array($rmPurchase->status, [RmPurchaseOrder::STATUS_DRAFT, RmPurchaseOrder::STATUS_PENDING])) {
            return redirect()->route('manufacturing.rm-purchases.show', $rmPurchase)
                ->with('error', __('Only draft or pending purchase orders can be edited.'));
        }

        $rmPurchase->load('items.rawMaterial');
        $suppliers = $this->supplierService->getActiveSuppliers();
        $rawMaterials = $this->rawMaterialService->getActiveRawMaterials();

        return view('manufacturing::rm-purchases.edit', compact('rmPurchase', 'suppliers', 'rawMaterials'));
    }

    /**
     * Update the specified RM purchase order.
     */
    public function update(Request $request, RmPurchaseOrder $rmPurchase)
    {
        bpAuthorize('manufacturing.edit');
        $validated = $request->validate([
            'po_date' => 'required|date',
            'supplier_id' => 'required|exists:raw_material_suppliers,id',
            'expected_delivery_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'shipping_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->purchaseService->update($rmPurchase, $validated);
        } catch (\RuntimeException $e) {
            return redirect()->route('manufacturing.rm-purchases.show', $rmPurchase)
                ->with('error', $e->getMessage());
        }

        return redirect()->route('manufacturing.rm-purchases.show', $rmPurchase)
            ->with('success', __('RM Purchase Order updated successfully.'));
    }

    /**
     * Remove the specified RM purchase order.
     */
    public function destroy(RmPurchaseOrder $rmPurchase)
    {
        bpAuthorize('manufacturing.delete');
        try {
            $this->purchaseService->delete($rmPurchase);
        } catch (\RuntimeException $e) {
            return redirect()->route('manufacturing.rm-purchases.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('manufacturing.rm-purchases.index')
            ->with('success', __('RM Purchase Order deleted successfully.'));
    }

    /**
     * Approve an RM purchase order.
     */
    public function approve(RmPurchaseOrder $rmPurchase)
    {
        bpAuthorize('manufacturing.edit');
        try {
            $this->purchaseService->approve($rmPurchase);
        } catch (\RuntimeException $e) {
            return redirect()->route('manufacturing.rm-purchases.show', $rmPurchase)
                ->with('error', $e->getMessage());
        }

        return redirect()->route('manufacturing.rm-purchases.show', $rmPurchase)
            ->with('success', __('RM Purchase Order approved successfully.'));
    }

    /**
     * Cancel an RM purchase order.
     */
    public function cancel(RmPurchaseOrder $rmPurchase)
    {
        bpAuthorize('manufacturing.edit');
        try {
            $stockService = app(\Modules\Manufacturing\Services\RmStockService::class);
            $this->purchaseService->cancel($rmPurchase, $stockService);
        } catch (\RuntimeException $e) {
            return redirect()->route('manufacturing.rm-purchases.show', $rmPurchase)
                ->with('error', $e->getMessage());
        }

        return redirect()->route('manufacturing.rm-purchases.show', $rmPurchase)
            ->with('success', __('RM Purchase Order cancelled.'));
    }
}
