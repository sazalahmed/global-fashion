<?php

namespace Modules\Accounting\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Accounting\Models\PaymentReceipt;
use Modules\Payment\Models\Payment;

class PaymentReceiptService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return PaymentReceipt::with(['payment', 'creator', 'branch'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['receipt_type'] ?? null, fn ($q, $t) => $q->where('receipt_type', $t))
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->where('receipt_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->where('receipt_date', '<=', $d))
            ->latest('receipt_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getStats(): array
    {
        return [
            'total'          => PaymentReceipt::count(),
            'received_count' => PaymentReceipt::where('receipt_type', 'payment_received')->count(),
            'made_count'     => PaymentReceipt::where('receipt_type', 'payment_made')->count(),
            'net_amount'     => PaymentReceipt::where('receipt_type', 'payment_received')->sum('amount')
                              - PaymentReceipt::where('receipt_type', 'payment_made')->sum('amount'),
        ];
    }

    public function find(int $id): PaymentReceipt
    {
        return PaymentReceipt::with(['payment.paymentAccount', 'creator', 'branch'])
            ->findOrFail($id);
    }

    public function generateReceipt(Payment $payment): PaymentReceipt
    {
        $party = $payment->party();

        return PaymentReceipt::create([
            'receipt_number'  => $this->generateNumber(),
            'payment_id'      => $payment->id,
            'receipt_type'    => $payment->direction === 'receive' ? 'payment_received' : 'payment_made',
            'party_name'      => $party?->name ?? "#{$payment->party_id}",
            'amount'          => $payment->amount,
            'payment_method'  => $payment->payment_method,
            'receipt_date'    => $payment->payment_date,
            'description'     => $payment->note,
            'branch_id'       => $payment->branch_id,
            'created_by'      => auth()->id(),
        ]);
    }

    private function generateNumber(): string
    {
        $year = now()->format('Y');
        $last = PaymentReceipt::withTrashed()->whereYear('created_at', $year)->count() + 1;
        return 'REC-' . $year . '-' . str_pad($last, 4, '0', STR_PAD_LEFT);
    }
}
