<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Inventory\Models\StockLedger;

class StockLedgerExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return StockLedger::with(['product', 'variant'])
            ->when($this->filters['product_id'] ?? null, fn ($q, $v) => $q->where('product_id', $v))
            ->when($this->filters['source_type'] ?? null, fn ($q, $v) => $q->where('source_type', $v))
            ->when($this->filters['date_from'] ?? null, fn ($q, $d) => $q->where('created_at', '>=', $d))
            ->when($this->filters['date_to'] ?? null, fn ($q, $d) => $q->where('created_at', '<=', $d))
            ->latest();
    }

    public function headings(): array
    {
        return ['Date', 'Product', 'Variant', 'Source Type', 'Qty Before', 'Change', 'Qty After', 'Unit Cost', 'Description'];
    }

    public function map($entry): array
    {
        return [
            $entry->created_at?->format('d M Y H:i'),
            $entry->product->name ?? '—',
            $entry->variant->variant_name ?? '—',
            ucfirst(str_replace('_', ' ', $entry->source_type)),
            $entry->quantity_before,
            $entry->quantity_change,
            $entry->quantity_after,
            $entry->unit_cost,
            $entry->description ?? '—',
        ];
    }
}
