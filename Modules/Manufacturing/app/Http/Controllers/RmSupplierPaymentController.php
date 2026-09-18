<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Manufacturing\Models\RawMaterialSupplier;
use Modules\Manufacturing\Services\RmSupplierPaymentService;

class RmSupplierPaymentController extends Controller
{
    public function __construct(
        private readonly RmSupplierPaymentService $paymentService,
    ) {}

    /**
     * List payments for a specific RM supplier (JSON for AJAX).
     */
    public function index(RawMaterialSupplier $supplier)
    {
        bpAuthorize('manufacturing.view');
        $payments = $this->paymentService->listForSupplier($supplier);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data'    => $payments,
            ]);
        }

        return back();
    }

    /**
     * Store a new payment for an RM supplier.
     */
    public function store(Request $request, RawMaterialSupplier $supplier)
    {
        bpAuthorize('manufacturing.create');
        $validated = $request->validate([
            'amount'            => 'required|numeric|min:0.01',
            'payment_method'    => 'required|string|max:50',
            'payment_date'      => 'required|date',
            'payment_type'      => 'required|in:payment,advance',
            'purchase_order_id' => 'nullable|exists:rm_purchase_orders,id',
            'payment_account_id' => 'required|exists:payment_accounts,id',
            'reference'         => 'nullable|string|max:100',
            'notes'             => 'nullable|string|max:500',
        ]);

        $this->paymentService->recordPayment($supplier, $validated);

        return redirect()->back()->with('success', __('Payment recorded successfully.'));
    }
}
