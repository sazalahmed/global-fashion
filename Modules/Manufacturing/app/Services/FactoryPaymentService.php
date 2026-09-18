<?php

namespace Modules\Manufacturing\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Manufacturing\Models\Factory;
use Modules\Manufacturing\Models\FactoryPayment;
use Modules\Manufacturing\Models\ProductionOrder;

class FactoryPaymentService
{
    public function __construct(
        private readonly ManufacturingAccountingService $accountingService,
    ) {}

    /**
     * Record a payment to a factory.
     *
     * Handles advance payments, payments against production orders, and
     * damage deductions. Creates the factory payment record, updates
     * balances, and creates the journal entry.
     */
    public function recordPayment(Factory $factory, array $data): FactoryPayment
    {
        return DB::transaction(function () use ($factory, $data) {
            $amount = (float) $data['amount'];
            $deductionAmount = (float) ($data['deduction_amount'] ?? 0);
            $netAmount = $amount - $deductionAmount;
            $paymentType = $data['payment_type'] ?? 'payment';
            $isAdvance = $paymentType === 'advance';

            // Create the factory payment record
            $payment = FactoryPayment::create([
                'factory_id'          => $factory->id,
                'production_order_id' => $data['production_order_id'] ?? null,
                'payment_date'        => $data['payment_date'],
                'amount'              => $amount,
                'payment_method'      => $data['payment_method'],
                'payment_account_id'  => $data['payment_account_id'] ?? null,
                'payment_type'        => $paymentType,
                'deduction_amount'    => $deductionAmount,
                'net_amount'          => $netAmount,
                'reference'           => $data['reference'] ?? null,
                'notes'               => $data['notes'] ?? null,
                'created_by'          => auth()->id(),
            ]);

            // Update factory balances
            if ($isAdvance) {
                $factory->increment('advance_balance', $amount);
            } else {
                $factory->increment('total_paid', $amount);
                $factory->decrement('due_balance', min($amount, (float) $factory->due_balance));
            }

            // Update production order payment status if paying against an order
            if (!$isAdvance && !empty($data['production_order_id'])) {
                $order = ProductionOrder::find($data['production_order_id']);
                if ($order) {
                    $order->increment('paid_amount', $amount);
                    $order->decrement('due_amount', min($amount, (float) $order->due_amount));

                    // Track deduction separately
                    if ($deductionAmount > 0) {
                        $order->increment('advance_deducted', $deductionAmount);
                    }

                    $freshOrder = $order->fresh();
                    if ($freshOrder->due_amount <= 0) {
                        $order->update(['payment_status' => ProductionOrder::PAYMENT_PAID]);
                    } elseif ($freshOrder->paid_amount > 0) {
                        $order->update(['payment_status' => ProductionOrder::PAYMENT_PARTIAL]);
                    }
                }
            }

            // Create journal entry via the accounting service
            try {
                $this->accountingService->recordFactoryPayment(
                    $payment->payment_number,
                    $netAmount,
                    $paymentType,
                    $data['payment_date'],
                    $deductionAmount,
                );
            } catch (\Throwable $e) {
                Log::warning('Failed to create journal entry for factory payment', [
                    'payment_id' => $payment->id,
                    'factory_id' => $factory->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // TODO: Integrate with Payment module when it supports party_type='factory'
            // $this->paymentService->create([
            //     'direction'      => 'pay',
            //     'party_type'     => 'factory',
            //     'party_id'       => $factory->id,
            //     'payment_type'   => $isAdvance ? 'advance_payment' : 'order_payment',
            //     'amount'         => $netAmount,
            //     'payment_method' => $data['payment_method'],
            //     'payment_date'   => $data['payment_date'],
            //     'reference'      => $payment->payment_number,
            //     'note'           => $data['notes'] ?? null,
            // ]);

            return $payment;
        });
    }

    /**
     * Get paginated payments for a specific factory.
     */
    public function listForFactory(Factory $factory, int $perPage = 15)
    {
        return FactoryPayment::where('factory_id', $factory->id)
            ->with(['productionOrder', 'creator'])
            ->latest('payment_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get paginated payments for a specific production order.
     */
    public function listForProductionOrder(ProductionOrder $order, int $perPage = 15)
    {
        return FactoryPayment::where('production_order_id', $order->id)
            ->with(['factory', 'creator'])
            ->latest('payment_date')
            ->paginate($perPage)
            ->withQueryString();
    }
}
