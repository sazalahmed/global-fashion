<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\PurchaseReturn\Models\PurchaseReturn;

class PurchaseReturnsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return PurchaseReturn::with(['purchase', 'supplier'])
            ->when($this->filters['search'] ?? null, function ($q, $s) {
                $q->where('return_number', 'like', "%{$s}%")
                  ->orWhereHas('supplier', fn ($sq) => $sq->where('company_name', 'like', "%{$s}%"));
            })
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($this->filters['date_from'] ?? null, fn ($q, $d) => $q->where('return_date', '>=', $d))
            ->when($this->filters['date_to'] ?? null, fn ($q, $d) => $q->where('return_date', '<=', $d))
            ->latest('return_date');
    }

    public function headings(): array
    {
        return ['Return #', 'Date', 'PO #', 'Supplier', 'Reason', 'Status', 'Subtotal', 'Tax', 'Total'];
    }

    public function map($return): array
    {
        return [
            $return->return_number,
            $return->return_date?->format('d M Y'),
            $return->purchase->po_number ?? '—',
            $return->supplier->company_name ?? '—',
            ucfirst($return->reason ?? '—'),
            ucfirst($return->status),
            $return->subtotal,
            $return->tax_amount,
            $return->total,
        ];
    }
}
