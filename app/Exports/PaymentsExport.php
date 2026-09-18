<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Payment\Models\Payment;

class PaymentsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return Payment::with(['paymentAccount', 'creator'])
            ->when($this->filters['search'] ?? null, fn ($q, $s) => $q->where('payment_number', 'like', "%{$s}%"))
            ->when($this->filters['direction'] ?? null, fn ($q, $v) => $q->where('direction', $v))
            ->when($this->filters['party_type'] ?? null, fn ($q, $v) => $q->where('party_type', $v))
            ->when($this->filters['payment_account_id'] ?? null, fn ($q, $v) => $q->where('payment_account_id', $v))
            ->when($this->filters['date_from'] ?? null, fn ($q, $d) => $q->where('payment_date', '>=', $d))
            ->when($this->filters['date_to'] ?? null, fn ($q, $d) => $q->where('payment_date', '<=', $d))
            ->latest('payment_date');
    }

    public function headings(): array
    {
        return ['Date', 'Reference #', 'Direction', 'Party Type', 'Amount (' . currency_symbol() . ')', 'Method', 'Account', 'Reference', 'Created By'];
    }

    public function map($payment): array
    {
        return [
            $payment->payment_date?->format('d M Y'),
            $payment->payment_number,
            ucfirst($payment->direction),
            ucfirst($payment->party_type),
            $payment->amount,
            ucwords(str_replace('_', ' ', $payment->payment_method ?? '')),
            $payment->paymentAccount->display_name ?? '—',
            $payment->reference ?? '—',
            $payment->creator->name ?? '—',
        ];
    }
}
