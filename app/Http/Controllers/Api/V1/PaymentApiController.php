<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Payment\Models\PaymentAccount;
use Modules\Payment\Models\BalanceTransfer;
use Modules\Payment\Services\PaymentService;

class PaymentApiController extends BaseApiController
{
    public function __construct(
        private readonly PaymentService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $payments = $this->service->list($request->all(), $request->input('per_page', 15));

        return $this->paginatedSuccess($payments, 'Payments retrieved successfully');
    }

    public function show(int $id): JsonResponse
    {
        $payment = $this->service->find($id);

        $partyName = '';
        if ($payment->party_type && $payment->party_id) {
            $party = $this->service->findParty($payment->party_type, $payment->party_id);
            $partyName = $party?->name ?? '';
        }

        return $this->success([
            'id'                   => $payment->id,
            'payment_number'       => $payment->payment_number,
            'direction'            => $payment->direction,
            'party_type'           => $payment->party_type,
            'party_id'             => $payment->party_id,
            'party_name'           => $partyName,
            'payment_type'         => $payment->payment_type,
            'amount'               => (float) $payment->amount,
            'payment_method'       => $payment->payment_method,
            'payment_account_name' => $payment->paymentAccount?->display_name,
            'payment_date'         => $payment->payment_date?->format('Y-m-d'),
            'reference'            => $payment->reference,
            'note'                 => $payment->note,
            'allocations'          => $payment->allocations->map(fn ($a) => [
                'id'               => $a->id,
                'allocatable_type' => class_basename($a->allocatable_type),
                'allocatable_id'   => $a->allocatable_id,
                'reference_number' => $a->allocatable?->invoice_number ?? $a->allocatable?->po_number ?? null,
                'amount'           => (float) $a->amount,
            ]),
            'created_by'           => $payment->creator?->name,
            'created_at'           => $payment->created_at?->toIso8601String(),
        ], 'Payment retrieved');
    }

    public function receive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id'        => 'required|integer|exists:customers,id',
            'amount'             => 'required|numeric|min:0.01',
            'payment_method'     => 'required|in:cash,mobile_banking,card,bank_transfer',
            'payment_account_id' => 'nullable|integer|exists:payment_accounts,id',
            'payment_date'       => 'required|date',
            'reference'          => 'nullable|string|max:255',
            'note'               => 'nullable|string',
            'allocations'        => 'nullable|array',
            'allocations.*.sale_id' => 'required_with:allocations|integer|exists:sales,id',
            'allocations.*.amount'  => 'required_with:allocations|numeric|min:0.01',
        ]);

        $allocations = [];
        foreach ($validated['allocations'] ?? [] as $alloc) {
            $allocations[] = [
                'allocatable_type' => \Modules\Sale\Models\Sale::class,
                'allocatable_id'   => $alloc['sale_id'],
                'amount'           => $alloc['amount'],
            ];
        }

        $payment = $this->service->create([
            'direction'          => 'receive',
            'party_type'         => 'customer',
            'party_id'           => $validated['customer_id'],
            'payment_type'       => 'sale_payment',
            'amount'             => $validated['amount'],
            'payment_method'     => $validated['payment_method'],
            'payment_account_id' => $validated['payment_account_id'] ?? null,
            'payment_date'       => $validated['payment_date'],
            'reference'          => $validated['reference'] ?? null,
            'note'               => $validated['note'] ?? null,
        ], $allocations);

        return $this->success($payment, 'Payment received successfully', 201);
    }

    public function make(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id'        => 'required|integer|exists:suppliers,id',
            'amount'             => 'required|numeric|min:0.01',
            'payment_method'     => 'required|in:cash,mobile_banking,card,bank_transfer',
            'payment_account_id' => 'nullable|integer|exists:payment_accounts,id',
            'payment_date'       => 'required|date',
            'reference'          => 'nullable|string|max:255',
            'note'               => 'nullable|string',
            'allocations'           => 'nullable|array',
            'allocations.*.purchase_id' => 'required_with:allocations|integer|exists:purchases,id',
            'allocations.*.amount'      => 'required_with:allocations|numeric|min:0.01',
        ]);

        $allocations = [];
        foreach ($validated['allocations'] ?? [] as $alloc) {
            $allocations[] = [
                'allocatable_type' => \Modules\Purchase\Models\Purchase::class,
                'allocatable_id'   => $alloc['purchase_id'],
                'amount'           => $alloc['amount'],
            ];
        }

        $payment = $this->service->create([
            'direction'          => 'pay',
            'party_type'         => 'supplier',
            'party_id'           => $validated['supplier_id'],
            'payment_type'       => 'purchase_payment',
            'amount'             => $validated['amount'],
            'payment_method'     => $validated['payment_method'],
            'payment_account_id' => $validated['payment_account_id'] ?? null,
            'payment_date'       => $validated['payment_date'],
            'reference'          => $validated['reference'] ?? null,
            'note'               => $validated['note'] ?? null,
        ], $allocations);

        return $this->success($payment, 'Payment made successfully', 201);
    }

    public function accounts(): JsonResponse
    {
        $accounts = PaymentAccount::active()
            ->orderBy('account_type')
            ->orderBy('name')
            ->get()
            ->map(fn ($a) => [
                'id'           => $a->id,
                'name'         => $a->name,
                'account_type' => $a->account_type,
                'display_name' => $a->display_name,
                'is_default'   => $a->is_default,
                'is_active'    => $a->is_active,
            ]);

        return $this->success($accounts, 'Payment accounts retrieved');
    }

    public function transfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_account_id' => 'required|integer|exists:payment_accounts,id',
            'to_account_id'   => 'required|integer|exists:payment_accounts,id|different:from_account_id',
            'amount'          => 'required|numeric|min:0.01',
            'transfer_date'   => 'required|date',
            'reference'       => 'nullable|string|max:255',
            'note'            => 'nullable|string',
        ]);

        $transfer = BalanceTransfer::create([
            'from_account_id' => $validated['from_account_id'],
            'to_account_id'   => $validated['to_account_id'],
            'amount'          => $validated['amount'],
            'transfer_date'   => $validated['transfer_date'],
            'reference'       => $validated['reference'] ?? null,
            'note'            => $validated['note'] ?? null,
            'created_by'      => auth()->id(),
        ]);

        return $this->success($transfer, 'Balance transferred successfully', 201);
    }

    public function outstandingInvoices(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'party_type' => 'required|in:customer,supplier',
            'party_id'   => 'required|integer',
        ]);

        $invoices = $this->service->getOutstandingInvoices(
            $validated['party_type'],
            $validated['party_id'],
        );

        return $this->success($invoices, 'Outstanding invoices retrieved');
    }
}
