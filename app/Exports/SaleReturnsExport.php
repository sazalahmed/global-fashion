<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\SaleReturn\Models\SaleReturn;

class SaleReturnsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return SaleReturn::with(['sale.customer', 'customer', 'creator'])
            ->when($this->filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->byStatus($v))
            ->when($this->filters['date_from'] ?? null, fn ($q, $d) => $q->where('return_date', '>=', $d))
            ->when($this->filters['date_to'] ?? null, fn ($q, $d) => $q->where('return_date', '<=', $d))
            ->latest('return_date');
    }

    public function headings(): array
    {
        return ['Return #', 'Date', 'Invoice #', 'Customer', 'Reason', 'Refund Method', 'Status', 'Subtotal', 'Tax', 'Total'];
    }

    public function map($return): array
    {
        return [
            $return->return_number,
            $return->return_date?->format('d M Y'),
            $return->sale->invoice_number ?? '—',
            $return->customer_display_name,
            ucfirst($return->reason ?? '—'),
            ucwords(str_replace('_', ' ', $return->refund_method ?? '—')),
            ucfirst($return->status),
            $return->subtotal,
            $return->tax_amount,
            $return->total_amount,
        ];
    }
}
