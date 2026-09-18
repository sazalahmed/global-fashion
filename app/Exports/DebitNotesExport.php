<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Accounting\Models\DebitNote;

class DebitNotesExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return DebitNote::query()
            ->when($this->filters['search'] ?? null, fn ($q, $s) => $q->where('debit_note_number', 'like', "%{$s}%"))
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($this->filters['date_from'] ?? null, fn ($q, $d) => $q->where('issue_date', '>=', $d))
            ->when($this->filters['date_to'] ?? null, fn ($q, $d) => $q->where('issue_date', '<=', $d))
            ->latest('issue_date');
    }

    public function headings(): array
    {
        return ['Debit Note #', 'Date', 'Supplier', 'Purchase #', 'Amount', 'Status'];
    }

    public function map($dn): array
    {
        return [
            $dn->debit_note_number,
            $dn->issue_date?->format('d M Y'),
            $dn->supplier_id ?? '—',
            $dn->purchase_id ?? '—',
            $dn->total_amount,
            ucfirst($dn->status),
        ];
    }
}
