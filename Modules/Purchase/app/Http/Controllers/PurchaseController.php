<?php

namespace Modules\Purchase\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Purchase\Models\Purchase;
use Modules\Purchase\Services\PurchaseService;
use Modules\Purchase\Http\Requests\StorePurchaseRequest;
use Modules\Purchase\Http\Requests\UpdatePurchaseRequest;
use Modules\Supplier\Services\SupplierService;
use Modules\Branch\Services\BranchService;
use Modules\Product\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    public function __construct(
        private PurchaseService $purchaseService,
        private SupplierService $supplierService,
        private BranchService $branchService
    ) {
    }

    /**
     * Display a listing of purchase orders.
     */
    public function index(Request $request)
    {
        bpAuthorize('purchases.view');
        $filters = $request->only(['search', 'status', 'payment_status', 'supplier_id', 'from', 'to']);
        $purchases = $this->purchaseService->list($filters);
        $stats = $this->purchaseService->getStats($filters);
        $suppliers = $this->supplierService->getActiveSuppliers();

        return view('purchase::index', compact('purchases', 'stats', 'suppliers'));
    }

    /**
     * Show the form for creating a new purchase order.
     */
    public function create(Request $request)
    {
        bpAuthorize('purchases.create');
        $suppliers = $this->supplierService->getActiveSuppliers();
        $branches = $this->branchService->getActiveBranches();
        $products = Product::active()
            ->orderBy('name')
            ->select('id', 'name', 'sku', 'model', 'barcode', 'cost_price', 'sell_price', 'vat_rate', 'product_type')
            ->with(['variants' => fn ($q) => $q->with('attributeValues.attribute')])
            ->get();
        $paymentAccounts = \Modules\Payment\Models\PaymentAccount::active()->get();

        // When converting an approved requisition, pre-fill the item rows.
        $requisition = null;
        if ($request->filled('requisition_id')) {
            $requisition = \Modules\Purchase\Models\Requisition::with('items.product')
                ->where('status', \Modules\Purchase\Models\Requisition::STATUS_APPROVED)
                ->find($request->input('requisition_id'));
        }

        return view('purchase::create', compact('suppliers', 'branches', 'products', 'paymentAccounts', 'requisition'));
    }

    /**
     * Store a newly created purchase order.
     */
    public function store(StorePurchaseRequest $request)
    {
        bpAuthorize('purchases.create');
        $data = $request->validated();

        $payments = $data['payments'] ?? [];
        unset($data['payments']);

        $action = $request->input('action', 'approve');

        $requisitionId = $data['requisition_id'] ?? null;
        unset($data['requisition_id']);

        // POs converted from a requisition must go through the approval flow —
        // never auto-approve them, regardless of which button was clicked.
        if ($requisitionId && $action === 'approve') {
            $action = 'submit';
        }

        $data['status'] = match ($action) {
            'submit' => Purchase::STATUS_PENDING,
            'draft' => Purchase::STATUS_DRAFT,
            default => Purchase::STATUS_APPROVED,
        };

        $purchase = $this->purchaseService->create($data, $payments);

        if ($action === 'approve') {
            $this->purchaseService->approve($purchase, Auth::id());
        }

        // Link back to the source requisition (marks it Ordered).
        if ($requisitionId) {
            $requisition = \Modules\Purchase\Models\Requisition::find($requisitionId);
            if ($requisition) {
                app(\Modules\Purchase\Services\RequisitionService::class)->linkPurchase($requisition, $purchase);
            }
        }

        return redirect()->route('purchases.show', $purchase)->with('success', __('Purchase order created successfully.'));
    }

    /**
     * Display the specified purchase order.
     */
    public function show(Purchase $purchase)
    {
        bpAuthorize('purchases.view');
        $purchase->load([
            'supplier', 'branch',
            'items.product', 'items.variant.attributeValues.attribute',
            'grns.receivedBy', 'grns.items.product', 'grns.items.variant',
            'payments.creator', 'createdBy', 'approvedBy',
        ]);

        return view('purchase::show', compact('purchase'));
    }

    /**
     * Show the form for editing a purchase order.
     */
    public function edit(Purchase $purchase)
    {
        bpAuthorize('purchases.edit');
        if ($purchase->status === Purchase::STATUS_CANCELLED) {
            return redirect()->route('purchases.show', $purchase)->with('error', __('Cancelled purchase orders cannot be edited.'));
        }

        // Editing a PO with existing returns is blocked — reverse the returns first.
        if (\Modules\PurchaseReturn\Models\PurchaseReturn::where('purchase_id', $purchase->id)->where('status', '!=', 'cancelled')->exists()) {
            return redirect()->route('purchases.show', $purchase)->with('error', __('This purchase has returns. Reverse the returns before editing.'));
        }

        $purchase->load(['items.product', 'items.variant.attributeValues.attribute', 'grns']);

        $suppliers = $this->supplierService->getActiveSuppliers();
        $branches = $this->branchService->getActiveBranches();
        $products = Product::active()
            ->orderBy('name')
            ->select('id', 'name', 'sku', 'model', 'barcode', 'cost_price', 'sell_price', 'vat_rate', 'product_type')
            ->with(['variants' => fn ($q) => $q->with('attributeValues.attribute')])
            ->get();
        $paymentAccounts = \Modules\Payment\Models\PaymentAccount::active()->get();

        return view('purchase::edit', compact('purchase', 'suppliers', 'branches', 'products', 'paymentAccounts'));
    }

    /**
     * Update the specified purchase order.
     */
    public function update(UpdatePurchaseRequest $request, Purchase $purchase)
    {
        bpAuthorize('purchases.edit');
        $data = $request->validated();
        $payments = $data['payments'] ?? [];
        unset($data['payments']);

        $this->purchaseService->update($purchase, $data, $payments);

        return redirect()->route('purchases.show', $purchase)->with('success', __('Purchase order updated successfully.'));
    }

    /**
     * Remove the specified purchase order.
     */
    public function destroy(Purchase $purchase)
    {
        bpAuthorize('purchases.delete');
        try {
            $this->purchaseService->delete($purchase);
        } catch (\Throwable $e) {
            \Log::error("Failed to delete purchase {$purchase->po_number}: {$e->getMessage()}");

            return redirect()->route('purchases.index')->with('error', __('Failed to delete the purchase order. Please try again.'));
        }

        return redirect()->route('purchases.index')->with('success', __('Purchase order and all related records deleted successfully.'));
    }

    /**
     * Approve a purchase order.
     */
    public function approve(Purchase $purchase)
    {
        bpAuthorize('purchases.approve');
        if (!$purchase->canBeApproved()) {
            return redirect()->route('purchases.show', $purchase)->with('error', __('This purchase order cannot be approved.'));
        }

        $this->purchaseService->approve($purchase, Auth::id());

        return redirect()->route('purchases.show', $purchase)->with('success', __('Purchase order approved successfully.'));
    }

    /**
     * Cancel a purchase order.
     */
    public function cancel(Purchase $purchase)
    {
        bpAuthorize('purchases.edit');
        if (!$purchase->canBeCancelled()) {
            return redirect()->route('purchases.show', $purchase)->with('error', __('This purchase order cannot be cancelled.'));
        }

        $this->purchaseService->cancel($purchase);

        return redirect()->route('purchases.show', $purchase)->with('success', __('Purchase order cancelled.'));
    }

    /**
     * Print purchase order invoice.
     */
    public function print(Purchase $purchase)
    {
        bpAuthorize('purchases.view');

        return view('purchase::print', [
            'purchase' => $this->loadForDocument($purchase),
            'settings' => $this->documentSettings(),
        ]);
    }

    /** Relations the printable document needs. */
    private function loadForDocument(Purchase $purchase): Purchase
    {
        return $purchase->load([
            'supplier', 'branch', 'items.product', 'items.variant.attributeValues.attribute',
            'createdBy', 'approvedBy',
        ]);
    }

    /**
     * Letterhead details for the printable document. The logo is passed through
     * only when Settings holds one AND "show logo" is on, so the template never
     * has to make that decision.
     */
    private function documentSettings(): array
    {
        $get = fn (string $group, string $key, $default = '') => \Modules\Setting\Models\Setting::get($group, $key, $default);

        $logo = $get('business', 'logo');
        $showLogo = filter_var($get('invoice', 'show_logo', '1'), FILTER_VALIDATE_BOOLEAN);

        return [
            'company_name' => $get('business', 'company_name', 'BizPOS Pro'),
            'address'      => $get('business', 'address'),
            'phone'        => $get('business', 'phone'),
            'email'        => $get('business', 'email'),
            'logo_url'     => $logo && $showLogo ? \App\Helpers\Upload::url($logo) : null,
        ];
    }


    /**
     * Quick-add a supplier from purchase create page (AJAX).
     */
    public function quickAddSupplier(Request $request)
    {
        bpAuthorize('purchases.view');
        $validated = $request->validate([
            'company_name'   => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone'          => 'required|string|max:20',
            'email'          => 'nullable|email|max:255',
            'address'        => 'nullable|string|max:500',
        ]);

        $supplier = \Modules\Supplier\Models\Supplier::create($validated);

        return response()->json([
            'supplier' => [
                'id'           => $supplier->id,
                'company_name' => $supplier->company_name,
                'phone'        => $supplier->phone,
            ],
        ]);
    }
}
