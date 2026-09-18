<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Accounting\Models\CreditNote;

class CreditNotesExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return CreditNote::query()
            ->when($this->filters['search'] ?? null, fn ($q, $s) => $q->where('credit_note_number', 'like', "%{$s}%"))
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($this->filters['date_from'] ?? null, fn ($q, $d) => $q->where('issue_date', '>=', $d))
            ->when($this->filters['date_to'] ?? null, fn ($q, $d) => $q->where('issue_date', '<=', $d))
            ->latest('issue_date');
    }

    public function headings(): array
    {
        return ['Credit Note #', 'Date', 'Customer', 'Return #', 'Amount', 'Applied', 'Balance', 'Status'];
    }

    public function map($cn): array
    {
        return [
            $cn->credit_note_number,
            $cn->issue_date?->format('d M Y'),
            $cn->customer_id ?? '—',
            $cn->sale_return_id ?? '—',
            $cn->total_amount,
            $cn->applied_amount ?? 0,
            ($cn->total_amount ?? 0) - ($cn->applied_amount ?? 0),
            ucfirst($cn->status),
        ];
    }
}
