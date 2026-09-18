<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Supplier\Models\Supplier;

/**
 * Supplier payables (outstanding balances owed to suppliers) — mirrors the
 * Suppliers → Payable list, including its optional search / group filters.
 */
class PayablesExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        private ?string $search = null,
        private ?int $supplierGroupId = null,
    ) {}

    public function query()
    {
        $query = Supplier::query()
            ->with('supplierGroup:id,name')
            ->hasDue()
            ->ordered();

        if (!empty($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', '%' . $search . '%')
                    ->orWhere('contact_person', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        if (!empty($this->supplierGroupId)) {
            $query->where('supplier_group_id', $this->supplierGroupId);
        }

        return $query;
    }

    public function headings(): array
    {
        return ['Supplier', 'Contact Person', 'Phone', 'Group', 'Total Purchase', 'Total Paid', 'Payable'];
    }

    public function map($supplier): array
    {
        return [
            $supplier->company_name,
            $supplier->contact_person,
            $supplier->phone,
            $supplier->supplierGroup?->name,
            $supplier->total_purchase,
            $supplier->total_paid,
            $supplier->due_balance,
        ];
    }
}
