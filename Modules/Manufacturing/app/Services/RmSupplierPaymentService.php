<?php

namespace Modules\Manufacturing\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Manufacturing\Models\RmPurchaseOrder;
use Modules\Manufacturing\Models\RmSupplierPayment;
use Modules\Manufacturing\Models\RawMaterialSupplier;
use Modules\Payment\Services\PaymentService;

class RmSupplierPaymentService
{
    public function __construct(
        private readonly ManufacturingAccountingService $accountingService,
    ) {}

    /**
     * Record a payment to a raw material supplier.
     *
     * Handles both advance payments and payments against purchase orders.
     * Creates the RM supplier payment record, updates balances, and creates
     * the journal entry via ManufacturingAccountingService.
     */
    public function recordPayment(RawMaterialSupplier $supplier, array $data): RmSupplierPayment
    {
        return DB::transaction(function () use ($supplier, $data) {
            $amount = (float) $data['amount'];
            $paymentType = $data['payment_type'] ?? 'payment';
            $isAdvance = $paymentType === 'advance';

            // Create the RM supplier payment record
            $payment = RmSupplierPayment::create([
                'supplier_id'       => $supplier->id,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'payment_date'      => $data['payment_date'],
                'amount'            => $amount,
                'payment_method'    => $data['payment_method'],
                'payment_account_id' => $data['payment_account_id'] ?? null,
                'payment_type'      => $paymentType,
                'reference'         => $data['reference'] ?? null,
                'notes'             => $data['notes'] ?? null,
                'created_by'        => auth()->id(),
            ]);

            // Update supplier balances
            if ($isAdvance) {
                $supplier->increment('advance_balance', $amount);
            } else {
                $supplier->increment('total_paid', $amount);
                $supplier->decrement('due_balance', min($amount, (float) $supplier->due_balance));
            }

            // Update purchase order payment status if paying against a PO
            if (!$isAdvance && !empty($data['purchase_order_id'])) {
                $po = RmPurchaseOrder::find($data['purchase_order_id']);
                if ($po) {
                    $po->increment('paid_amount', $amount);
                    $po->decrement('due_amount', min($amount, (float) $po->due_amount));

                    $freshPo = $po->fresh();
                    if ($freshPo->due_amount <= 0) {
                        $po->update(['payment_status' => RmPurchaseOrder::PAYMENT_PAID]);
                    } elseif ($freshPo->paid_amount > 0) {
                        $po->update(['payment_status' => RmPurchaseOrder::PAYMENT_PARTIAL]);
                    }
                }
            }

            // Create journal entry via the accounting service
            try {
                $this->accountingService->recordRmSupplierPayment(
                    $payment->payment_number,
                    $amount,
                    $paymentType,
                    $data['payment_date'],
                );
            } catch (\Throwable $e) {
                Log::warning('Failed to create journal entry for RM supplier payment', [
                    'payment_id' => $payment->id,
                    'supplier_id' => $supplier->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // TODO: Integrate with Payment module when it supports party_type='rm_supplier'
            // $this->paymentService->create([
            //     'direction'      => 'pay',
            //     'party_type'     => 'rm_supplier',
            //     'party_id'       => $supplier->id,
            //     'payment_type'   => $isAdvance ? 'advance_payment' : 'purchase_payment',
            //     'amount'         => $amount,
            //     'payment_method' => $data['payment_method'],
            //     'payment_date'   => $data['payment_date'],
            //     'reference'      => $payment->payment_number,
            //     'note'           => $data['notes'] ?? null,
            // ]);

            return $payment;
        });
    }

    /**
     * Get paginated payments for a specific supplier.
     */
    public function listForSupplier(RawMaterialSupplier $supplier, int $perPage = 15)
    {
        return RmSupplierPayment::where('supplier_id', $supplier->id)
            ->with(['purchaseOrder', 'creator'])
            ->latest('payment_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get paginated payments for a specific purchase order.
     */
    public function listForPurchaseOrder(RmPurchaseOrder $po, int $perPage = 15)
    {
        return RmSupplierPayment::where('purchase_order_id', $po->id)
            ->with(['supplier', 'creator'])
            ->latest('payment_date')
            ->paginate($perPage)
            ->withQueryString();
    }
}
