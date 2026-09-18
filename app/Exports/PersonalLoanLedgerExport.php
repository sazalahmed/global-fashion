<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\Loan\Models\Borrower;
use Modules\Loan\Services\PersonalLoanService;

/**
 * One borrower's personal-loan ledger, mirroring the on-screen table exactly
 * (same PersonalLoanService::getLedger rows and date filters). Two-way ledger:
 * "Money Out" = loan given / paid back, "Money In" = repayment / loan taken,
 * signed running balance (negative = the business owes the person).
 */
class PersonalLoanLedgerExport implements FromCollection, WithHeadings
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        $borrower = Borrower::findOrFail((int) ($this->filters['borrower_id'] ?? 0));

        $ledger = app(PersonalLoanService::class)->getLedger($borrower, [
            'from' => $this->filters['from'] ?? null,
            'to'   => $this->filters['to'] ?? null,
        ]);

        $rows = collect($ledger['entries'])->map(fn (array $e) => [
            $e['date'] ? Carbon::parse($e['date'])->format('d M Y') : '—',
            $e['reference'],
            $e['type'],
            (float) $e['out'],
            (float) $e['in'],
            (float) $e['balance'],
        ]);

        $rows->push([
            '',
            '',
            'TOTAL',
            $rows->sum(fn ($r) => $r[3]),
            $rows->sum(fn ($r) => $r[4]),
            (float) $ledger['currentBalance'],
        ]);

        // Header context row so the sheet says whose ledger it is.
        $rows->prepend([
            'Borrower: ' . $borrower->name . ($borrower->phone ? ' (' . $borrower->phone . ')' : ''),
            '', '', '', '', '',
        ]);

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Reference',
            'Type',
            'Money Out',
            'Money In',
            'Balance',
        ];
    }
}
