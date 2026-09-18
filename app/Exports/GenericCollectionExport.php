<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class GenericCollectionExport implements FromCollection, WithHeadings, WithTitle
{
    use Exportable;

    protected Collection $data;
    protected string $title;

    public function __construct(Collection|iterable $data, string $title = 'Report')
    {
        $this->data = $data instanceof Collection ? $data : collect($data);
        $this->title = $title;
    }

    public function collection(): Collection
    {
        return $this->data->map(function ($row) {
            if (is_object($row)) {
                return collect($row)->toArray();
            }
            return $row;
        });
    }

    public function headings(): array
    {
        $first = $this->data->first();

        if (!$first) {
            return [];
        }

        if (is_object($first)) {
            return array_map(fn ($key) => ucwords(str_replace('_', ' ', $key)), array_keys((array) $first));
        }

        if (is_array($first)) {
            return array_map(fn ($key) => ucwords(str_replace('_', ' ', $key)), array_keys($first));
        }

        return [];
    }

    public function title(): string
    {
        return $this->title;
    }
}
