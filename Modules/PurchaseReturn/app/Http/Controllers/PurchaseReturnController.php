<?php

namespace Modules\PurchaseReturn\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\PurchaseReturn\Models\PurchaseReturn;
use Modules\PurchaseReturn\Services\PurchaseReturnService;
use Modules\Supplier\Services\SupplierService;
use Modules\Purchase\Services\PurchaseService;
use Illuminate\Http\Request;
use Modules\PurchaseReturn\Http\Requests\StorePurchaseReturnRequest;
use Modules\PurchaseReturn\Http\Requests\UpdatePurchaseReturnRequest;

class PurchaseReturnController extends Controller
{
    public function __construct(
        private PurchaseReturnService $returnService,
        private SupplierService $supplierService
    ) {
    }

    /**
     * Display a listing of purchase returns.
     */
    public function index(Request $request)
    {
        bpAuthorize('purchases.view');
        $returns = $this->returnService->list($request->only(['search', 'status', 'supplier_id', 'date_from', 'date_to']));
        $stats = $this->returnService->getStats();
        $suppliers = \Modules\Supplier\Models\Supplier::orderBy('company_name')->get(['id', 'company_name']);

        return view('purchasereturn::index', compact('returns', 'stats', 'suppliers'));
    }

    /**
     * Show the form for creating a new purchase return.
     */
    public function create(Request $request)
    {
        bpAuthorize('purchases.create');
        $suppliers = $this->supplierService->getActiveSuppliers();
        $purchases = \Modules\Purchase\Models\Purchase::with('supplier')
            ->whereNotIn('status', ['cancelled'])
            ->latest()
            ->get(['id', 'po_number', 'supplier_id', 'grand_total', 'po_date']);
        $selectedPurchaseId = $request->query('purchase_id');
        $paymentAccounts = \Modules\Payment\Models\PaymentAccount::active()->get();

        return view('purchasereturn::create', compact('suppliers', 'purchases', 'selectedPurchaseId', 'paymentAccounts'));
    }

    /**
     * Store a newly created purchase return.
     */
    public function store(StorePurchaseReturnRequest $request)
    {
        bpAuthorize('purchases.create');
        $validated = $request->validated();

        $return = $this->returnService->create($validated);

        return redirect()->route('purchase-returns.show', $return)
            ->with('success', __('Purchase return created successfully.'));
    }

    /**
     * Display the specified purchase return.
     */
    public function show(PurchaseReturn $purchaseReturn)
    {
        bpAuthorize('purchases.view');
        $purchaseReturn->load(['purchase', 'supplier', 'branch', 'items.product', 'items.variant', 'createdBy']);

        return view('purchasereturn::show', compact('purchaseReturn'));
    }

    /**
     * Show the form for editing the specified purchase return.
     */
    public function edit(PurchaseReturn $purchaseReturn)
    {
        bpAuthorize('purchases.edit');
        if ($purchaseReturn->status !== PurchaseReturn::STATUS_DRAFT) {
            return redirect()->route('purchase-returns.show', $purchaseReturn)
                ->with('error', __('Only draft returns can be edited.'));
        }

        $purchaseReturn->load(['purchase', 'supplier', 'items.product', 'items.variant.attributeValues.attribute']);
        $suppliers = $this->supplierService->getActiveSuppliers();

        return view('purchasereturn::edit', compact('purchaseReturn', 'suppliers'));
    }

    /**
     * Update the specified purchase return.
     */
    public function update(UpdatePurchaseReturnRequest $request, PurchaseReturn $purchaseReturn)
    {
        bpAuthorize('purchases.edit');
        if ($purchaseReturn->status !== PurchaseReturn::STATUS_DRAFT) {
            return redirect()->route('purchase-returns.show', $purchaseReturn)
                ->with('error', __('Only draft returns can be edited.'));
        }

        $validated = $request->validated();

        $return = $this->returnService->update($purchaseReturn, $validated);

        return redirect()->route('purchase-returns.show', $return)
            ->with('success', __('Purchase return updated successfully.'));
    }

    /**
     * Complete a purchase return (deduct stock, credit supplier).
     */
    public function complete(PurchaseReturn $purchaseReturn)
    {
        bpAuthorize('purchases.edit');
        $this->returnService->complete($purchaseReturn);

        return redirect()->route('purchase-returns.show', $purchaseReturn)
            ->with('success', __('Purchase return completed. Stock deducted and supplier credited.'));
    }

    /**
     * Cancel a purchase return (reverse stock if completed).
     */
    public function cancel(PurchaseReturn $purchaseReturn)
    {
        bpAuthorize('purchases.edit');
        try {
            $this->returnService->cancel($purchaseReturn);

            return redirect()->route('purchase-returns.show', $purchaseReturn)
                ->with('success', __('Purchase return cancelled successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Print view for a purchase return.
     */
    public function print(PurchaseReturn $purchaseReturn)
    {
        bpAuthorize('purchases.view');
        $purchaseReturn->load(['purchase.supplier', 'supplier', 'items.product', 'items.variant']);

        return view('purchasereturn::print', ['return' => $purchaseReturn]);
    }

    /**
     * Download purchase return as PDF.
     */
    public function pdf(PurchaseReturn $purchaseReturn)
    {
        bpAuthorize('purchases.view');
        $purchaseReturn->load(['purchase.supplier', 'supplier', 'items.product', 'items.variant']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('purchasereturn::print', [
            'return' => $purchaseReturn,
            'isPdf' => true,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("PurchaseReturn-{$purchaseReturn->return_number}.pdf");
    }

    /**
     * Get purchase items as JSON (for AJAX on create/edit forms).
     */
    public function purchaseItems(\Modules\Purchase\Models\Purchase $purchase)
    {
        bpAuthorize('purchases.view');
        $purchase->load(['items.product', 'items.variant.attributeValues.attribute']);

        $items = $purchase->items->map(function ($item) {
            $variantName = $item->variant ? $item->variant->variant_name : null;

            return [
                'purchase_item_id' => $item->id,
                'product_id'       => $item->product_id,
                'variant_id'       => $item->variant_id,
                'product_name'     => $item->product->name ?? 'Unknown',
                'variant_name'     => $variantName,
                'sku'              => $item->variant->sku ?? $item->product->sku ?? '',
                'quantity'         => (float) $item->quantity,
                'received_qty'     => (float) $item->received_quantity,
                'unit_price'       => (float) $item->unit_price,
                'tax_rate'         => (float) $item->tax_rate,
                'tax_amount'       => (float) $item->tax_amount,
            ];
        });

        return response()->json(['success' => true, 'data' => $items]);
    }

    /**
     * Remove the specified purchase return (only if draft).
     */
    public function destroy(PurchaseReturn $purchaseReturn)
    {
        bpAuthorize('purchases.delete');
        if ($purchaseReturn->status !== PurchaseReturn::STATUS_DRAFT) {
            return redirect()->route('purchase-returns.index')
                ->with('error', __('Only draft returns can be deleted.'));
        }

        $purchaseReturn->items()->delete();
        $purchaseReturn->delete();

        return redirect()->route('purchase-returns.index')
            ->with('success', __('Purchase return deleted successfully.'));
    }
}
