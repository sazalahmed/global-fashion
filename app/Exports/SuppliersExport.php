<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Supplier\Models\Supplier;

class SuppliersExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function query()
    {
        return Supplier::query()->orderBy('company_name');
    }

    public function headings(): array
    {
        return ['Company', 'Contact Person', 'Phone', 'Email', 'Total Purchase', 'Total Paid', 'Due', 'Advance', 'Status'];
    }

    public function map($s): array
    {
        return [
            $s->company_name, $s->contact_person, $s->phone, $s->email,
            $s->total_purchase, $s->total_paid, $s->due_balance, $s->advance_balance,
            ucfirst($s->status),
        ];
    }
}
