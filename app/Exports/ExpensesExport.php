<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\Expense\Models\Expense;

class ExpensesExport implements FromCollection, WithHeadings
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Mirrors the filters used by the expense list (correct keys: category,
     * status, payment_account_id, search, date range) so a filtered export
     * matches what the user sees on screen. Rows are pre-mapped arrays and the
     * final row is a TOTAL of the Amount column across all filtered records.
     */
    public function collection(): Collection
    {
        $expenses = $this->query()->get();

        $rows = $expenses->map(fn ($expense) => $this->map($expense));

        // Totals row aligned to the Amount (5th) column.
        $rows->push(['', '', '', 'TOTAL', (float) $expenses->sum('total_amount'), '', '', '']);

        return $rows;
    }

    /**
     * Filtered query — also consumed by the centralized PDF/print export path
     * as a fallback, and by collection() above.
     */
    public function query()
    {
        return Expense::with(['category', 'branch', 'creator'])
            ->when($this->filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($this->filters['category'] ?? null, fn ($q, $c) => $q->byCategory((int) $c))
            ->when($this->filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($this->filters['payment_account_id'] ?? null, fn ($q, $v) => $q->where('payment_account_id', $v))
            ->when($this->filters['date_from'] ?? null, fn ($q, $d) => $q->where('expense_date', '>=', $d))
            ->when($this->filters['date_to'] ?? null, fn ($q, $d) => $q->where('expense_date', '<=', $d))
            ->latest('expense_date');
    }

    public function headings(): array
    {
        return [
            'Reference',
            'Date',
            'Category',
            'Description',
            'Amount',
            'Payment Method',
            'Branch',
            'Created By',
        ];
    }

    public function map($expense): array
    {
        return [
            $expense->expense_number ?? $expense->reference,
            $expense->expense_date?->format('d M Y'),
            $expense->category->name ?? '—',
            $expense->description,
            (float) $expense->total_amount,
            ucfirst(str_replace('_', ' ', $expense->payment_method ?? '—')),
            $expense->branch->name ?? '—',
            $expense->creator->name ?? '—',
        ];
    }
}
