<?php

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entry_date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'description' => 'required|string|max:2000',
            'notes' => 'nullable|string|max:2000',
            'attachment' => 'nullable|file|mimes:jpg,png,pdf,webp|max:5120',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.description' => 'nullable|string|max:500',
            'lines.*.debit_amount' => 'nullable|numeric|min:0',
            'lines.*.credit_amount' => 'nullable|numeric|min:0',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $lines = $this->input('lines', []);
            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $i => $line) {
                $debit = (float) ($line['debit_amount'] ?? 0);
                $credit = (float) ($line['credit_amount'] ?? 0);

                $totalDebit += $debit;
                $totalCredit += $credit;

                if ($debit > 0 && $credit > 0) {
                    $validator->errors()->add("lines.{$i}", 'A line cannot have both debit and credit amounts.');
                }

                if ($debit == 0 && $credit == 0) {
                    $validator->errors()->add("lines.{$i}", 'Each line must have either a debit or credit amount.');
                }
            }

            if (bccomp((string) $totalDebit, (string) $totalCredit, 2) !== 0) {
                $validator->errors()->add('lines', 'Total debits (' . currency_symbol() . ' ' . number_format($totalDebit, 2) . ') must equal total credits (' . currency_symbol() . ' ' . number_format($totalCredit, 2) . ').');
            }
        });
    }
}
