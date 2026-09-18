<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AccountLedgerExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    protected $transactions;
    protected string $accountName;

    public function __construct($transactions, string $accountName)
    {
        $this->transactions = $transactions;
        $this->accountName = $accountName;
    }

    public function collection()
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return ['Date', 'Reference', 'Description', 'Type', 'IN (' . currency_symbol() . ')', 'OUT (' . currency_symbol() . ')', 'Balance (' . currency_symbol() . ')'];
    }

    public function map($txn): array
    {
        return [
            $txn->date, $txn->reference, $txn->description, $txn->txn_type,
            $txn->in_amount > 0 ? $txn->in_amount : '',
            $txn->out_amount > 0 ? $txn->out_amount : '',
            $txn->running_balance ?? '',
        ];
    }
}
