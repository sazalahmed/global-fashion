<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Purchase\Models\Purchase;

class PurchasesExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return Purchase::with(['supplier', 'branch', 'createdBy'])
            ->when($this->filters['search'] ?? null, fn ($q, $s) => $q->where('po_number', 'like', "%{$s}%"))
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($this->filters['date_from'] ?? null, fn ($q, $d) => $q->where('po_date', '>=', $d))
            ->when($this->filters['date_to'] ?? null, fn ($q, $d) => $q->where('po_date', '<=', $d))
            ->latest('po_date');
    }

    public function headings(): array
    {
        return [
            'PO #',
            'Date',
            'Supplier',
            'Branch',
            'Status',
            'Subtotal',
            'Tax',
            'Discount',
            'Grand Total',
            'Paid',
            'Due',
            'Created By',
        ];
    }

    public function map($purchase): array
    {
        return [
            $purchase->po_number,
            $purchase->po_date?->format('d M Y'),
            $purchase->supplier->company_name ?? '—',
            $purchase->branch->name ?? '—',
            ucfirst($purchase->status),
            $purchase->subtotal,
            $purchase->tax_amount,
            $purchase->discount_amount,
            $purchase->grand_total,
            $purchase->paid_amount,
            $purchase->due_amount,
            $purchase->createdBy->name ?? '—',
        ];
    }
}
