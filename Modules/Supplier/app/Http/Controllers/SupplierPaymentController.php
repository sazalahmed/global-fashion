<?php

namespace Modules\Supplier\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Services\SupplierService;
use Modules\Supplier\Http\Requests\StoreSupplierPaymentRequest;

class SupplierPaymentController extends Controller
{
    public function __construct(private SupplierService $supplierService)
    {
    }

    /**
     * Store a payment for a supplier.
     */
    public function store(StoreSupplierPaymentRequest $request, Supplier $supplier)
    {
        bpAuthorize('suppliers.edit');
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        $data['branch_id'] = auth()->user()->branch_id ?? null;
        $splits = $data['splits'] ?? [];
        unset($data['splits']);

        // If splits provided, create one payment per split method
        if (!empty($splits)) {
            foreach ($splits as $split) {
                $splitData = $data;
                $splitData['amount'] = (float) $split['amount'];
                $splitData['payment_account_id'] = $split['payment_account_id'];
                // Resolve payment method from account type
                $acctType = \Modules\Payment\Models\PaymentAccount::where('id', $split['payment_account_id'])->value('account_type');
                $splitData['payment_method'] = ucfirst(str_replace('_', ' ', $acctType ?? 'cash'));

                $this->supplierService->recordPayment($supplier, $splitData);
            }

            $total = collect($splits)->sum('amount');
            $count = count($splits);

            return redirect()->route('supplier.show', $supplier)
                ->with('success', "{$count} split payments totalling " . currency_symbol() . " " . number_format($total) . " recorded.");
        }

        $this->supplierService->recordPayment($supplier, $data);

        return redirect()->route('supplier.show', $supplier)
            ->with('success', __('Payment recorded successfully.'));
    }
}
