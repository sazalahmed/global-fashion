<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Accounting\Models\JournalEntry;

class JournalEntriesExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return JournalEntry::query()
            ->when($this->filters['search'] ?? null, function ($q, $s) {
                $q->where('entry_number', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%");
            })
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($this->filters['source_type'] ?? null, fn ($q, $v) => $q->where('source_type', $v))
            ->when($this->filters['date_from'] ?? null, fn ($q, $d) => $q->where('entry_date', '>=', $d))
            ->when($this->filters['date_to'] ?? null, fn ($q, $d) => $q->where('entry_date', '<=', $d))
            ->latest('entry_date');
    }

    public function headings(): array
    {
        return ['Entry #', 'Date', 'Reference', 'Description', 'Source', 'Total Amount', 'Status'];
    }

    public function map($entry): array
    {
        return [
            $entry->entry_number,
            $entry->entry_date?->format('d M Y'),
            $entry->reference ?? '—',
            $entry->description,
            ucfirst(str_replace('_', ' ', $entry->source_type)),
            $entry->total_amount,
            ucfirst($entry->status),
        ];
    }
}
