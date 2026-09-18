<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\Expense\Models\Expense;

class ExpenseLedgerExport implements FromCollection, WithHeadings
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Mirrors ExpenseService::getLedger() filters (excludes rejected, filters by
     * category/date range/branch) so the export matches the on-screen ledger.
     * Ends with a TOTAL row for the Amount column.
     */
    public function collection(): Collection
    {
        $entries = $this->query()->get();

        $rows = $entries->map(fn ($entry) => $this->map($entry));

        $rows->push(['', '', '', 'TOTAL', (float) $entries->sum('total_amount'), '']);

        return $rows;
    }

    public function query()
    {
        return Expense::with(['category', 'approver', 'branch'])
            ->where('status', '!=', 'rejected')
            ->when($this->filters['category'] ?? null, fn ($q, $c) => $q->byCategory((int) $c))
            ->when($this->filters['date_from'] ?? null, fn ($q, $d) => $q->where('expense_date', '>=', $d))
            ->when($this->filters['date_to'] ?? null, fn ($q, $d) => $q->where('expense_date', '<=', $d))
            ->when($this->filters['branch'] ?? null, fn ($q, $b) => $q->where('branch_id', $b))
            ->latest('expense_date');
    }

    public function headings(): array
    {
        return [
            'Date',
            'Reference #',
            'Category',
            'Description',
            'Amount',
            'Approved By',
        ];
    }

    public function map($entry): array
    {
        return [
            $entry->expense_date?->format('d M Y'),
            $entry->expense_number,
            $entry->category->name ?? 'Uncategorized',
            $entry->description,
            (float) $entry->total_amount,
            $entry->approver->name ?? 'N/A',
        ];
    }
}
