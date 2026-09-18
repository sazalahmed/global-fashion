<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Quotation\Models\Quotation;

class QuotationsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return Quotation::with(['customer'])
            ->when($this->filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($this->filters['date_from'] ?? null, fn ($q, $d) => $q->where('quotation_date', '>=', $d))
            ->when($this->filters['date_to'] ?? null, fn ($q, $d) => $q->where('quotation_date', '<=', $d))
            ->latest('quotation_date');
    }

    public function headings(): array
    {
        return ['Quotation #', 'Date', 'Customer', 'Valid Until', 'Subtotal', 'Discount', 'Tax', 'Total', 'Status'];
    }

    public function map($q): array
    {
        return [
            $q->quotation_number,
            $q->quotation_date?->format('d M Y'),
            $q->customer->name ?? '—',
            $q->valid_until?->format('d M Y') ?? '—',
            $q->subtotal,
            $q->discount_amount,
            $q->tax_amount,
            $q->grand_total,
            ucfirst($q->status),
        ];
    }
}
