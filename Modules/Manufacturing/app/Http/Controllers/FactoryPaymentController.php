<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Manufacturing\Models\Factory;
use Modules\Manufacturing\Services\FactoryPaymentService;

class FactoryPaymentController extends Controller
{
    public function __construct(
        private readonly FactoryPaymentService $paymentService,
    ) {}

    /**
     * List payments for a specific factory (JSON for AJAX).
     */
    public function index(Factory $factory)
    {
        bpAuthorize('manufacturing.view');
        $payments = $this->paymentService->listForFactory($factory);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data'    => $payments,
            ]);
        }

        return back();
    }

    /**
     * Store a new payment for a factory.
     */
    public function store(Request $request, Factory $factory)
    {
        bpAuthorize('manufacturing.create');
        $validated = $request->validate([
            'amount'              => 'required|numeric|min:0.01',
            'payment_method'      => 'required|string|max:50',
            'payment_date'        => 'required|date',
            'payment_type'        => 'required|in:payment,advance',
            'production_order_id' => 'nullable|exists:production_orders,id',
            'payment_account_id'  => 'nullable|exists:payment_accounts,id',
            'deduction_amount'    => 'nullable|numeric|min:0',
            'reference'           => 'nullable|string|max:100',
            'notes'               => 'nullable|string|max:500',
        ]);

        $this->paymentService->recordPayment($factory, $validated);

        return redirect()->back()->with('success', __('Payment recorded successfully.'));
    }
}
