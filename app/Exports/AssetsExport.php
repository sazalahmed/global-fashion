<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Asset\Models\Asset;

class AssetsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function query()
    {
        return Asset::query()->with('category:id,name')->orderBy('name');
    }

    public function headings(): array
    {
        return ['Code', 'Name', 'Category', 'Purchase Date', 'Cost', 'Depreciation', 'Current Value', 'Status'];
    }

    public function map($asset): array
    {
        return [
            $asset->asset_code, $asset->name, $asset->category?->name,
            $asset->purchase_date?->format('d M Y'), $asset->purchase_price,
            $asset->accumulated_depreciation, $asset->current_value, ucfirst($asset->status),
        ];
    }
}
