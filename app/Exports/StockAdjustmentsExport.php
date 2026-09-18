<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Inventory\Models\StockAdjustment;

class StockAdjustmentsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return StockAdjustment::query()
            ->when($this->filters['search'] ?? null, fn ($q, $s) => $q->where('adjustment_number', 'like', "%{$s}%"))
            ->when($this->filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($this->filters['date_from'] ?? null, fn ($q, $d) => $q->where('created_at', '>=', $d))
            ->when($this->filters['date_to'] ?? null, fn ($q, $d) => $q->where('created_at', '<=', $d))
            ->latest();
    }

    public function headings(): array
    {
        return ['Adjustment #', 'Date', 'Type', 'Reason', 'Reference', 'Status'];
    }

    public function map($adj): array
    {
        return [
            $adj->adjustment_number,
            $adj->created_at?->format('d M Y'),
            ucfirst($adj->type),
            ucfirst($adj->reason ?? '—'),
            $adj->reference ?? '—',
            ucfirst($adj->status),
        ];
    }
}
