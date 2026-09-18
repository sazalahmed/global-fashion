<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Sale\Models\Sale;

class SalesExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return Sale::with(['customer', 'branch', 'creator'])
            ->when($this->filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($this->filters['source'] ?? null, fn ($q, $v) => $q->where('source', $v))
            ->when(
                ($this->filters['status'] ?? null) && $this->filters['status'] !== 'all',
                fn ($q) => $q->where('status', $this->filters['status'])
            )
            ->when($this->filters['payment_status'] ?? null, fn ($q, $v) => $q->where('payment_status', $v))
            ->when($this->filters['date_from'] ?? null, fn ($q, $d) => $q->where('sale_date', '>=', $d))
            ->when($this->filters['date_to'] ?? null, fn ($q, $d) => $q->where('sale_date', '<=', $d))
            ->latest('sale_date');
    }

    public function headings(): array
    {
        return [
            'Invoice #',
            'Date',
            'Customer',
            'Branch',
            'Source',
            'Status',
            'Payment Status',
            'Subtotal',
            'Discount',
            'Tax',
            'Shipping',
            'Grand Total',
            'Paid',
            'Due',
            'Created By',
        ];
    }

    public function map($sale): array
    {
        return [
            $sale->invoice_number,
            $sale->sale_date?->format('d M Y'),
            $sale->customer_display_name,
            $sale->branch->name ?? '—',
            ucfirst($sale->source),
            ucfirst($sale->status),
            ucfirst(str_replace('_', ' ', $sale->payment_status)),
            $sale->subtotal,
            $sale->discount_amount,
            $sale->tax_amount,
            $sale->shipping_charge,
            $sale->grand_total,
            $sale->paid_amount,
            $sale->due_amount,
            $sale->creator->name ?? '—',
        ];
    }
}
