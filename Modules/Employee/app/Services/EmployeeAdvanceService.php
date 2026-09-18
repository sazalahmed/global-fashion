<?php

namespace Modules\Employee\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\EmployeeAdvance;

/**
 * Employee salary advances: money the business pays a staff member ahead of
 * salary, recovered later (manually here, or via payroll in E4). Tracked as a
 * running receivable that mirrors employees.advance_balance.
 */
class EmployeeAdvanceService
{
    /** Asset account advances post to (shared "Loans & Advances (Receivable)"). */
    private const RECEIVABLE_CODE = '1015';

    public function __construct(
        private readonly JournalEntryService $journalService,
    ) {}

    /**
     * Give an advance to an employee (money out).
     * DR Loans & Advances (Receivable) / CR Cash-Bank.
     */
    public function recordAdvance(Employee $employee, array $data): EmployeeAdvance
    {
        $amount = (float) $data['amount'];
        if ($amount <= 0) {
            throw new \RuntimeException('Amount must be greater than zero.');
        }

        return DB::transaction(function () use ($employee, $amount, $data) {
            $advance = EmployeeAdvance::create([
                'employee_id'        => $employee->id,
                'advance_number'     => $this->generateNumber(),
                'type'               => 'advance',
                'amount'             => $amount,
                'advance_date'       => $data['advance_date'] ?? now()->toDateString(),
                'payment_account_id' => $data['payment_account_id'] ?? null,
                'reference'          => $data['reference'] ?? null,
                'note'               => $data['note'] ?? null,
                'created_by'         => auth()->id(),
            ]);

            $je = $this->recordJournal(
                'employee_advance', $employee, $advance,
                debitCode: self::RECEIVABLE_CODE,
                creditCode: $this->cashAccountCode($advance->payment_account_id),
                description: "Salary advance: {$employee->name} ({$advance->advance_number})",
            );
            if ($je) {
                $advance->update(['journal_entry_id' => $je->id]);
            }

            $employee->increment('advance_balance', $amount);

            return $advance;
        });
    }

    /**
     * Record a manual advance recovery (employee repays outside payroll).
     * DR Cash-Bank / CR Loans & Advances (Receivable).
     */
    public function recordRecovery(Employee $employee, array $data): EmployeeAdvance
    {
        $amount = (float) $data['amount'];
        $balance = (float) $employee->advance_balance;

        if ($amount <= 0) {
            throw new \RuntimeException('Amount must be greater than zero.');
        }
        if ($amount > $balance + 0.01) {
            throw new \RuntimeException('Recovery (' . currency_symbol() . ' ' . number_format($amount) . ') exceeds the outstanding advance (' . currency_symbol() . ' ' . number_format($balance) . ').');
        }

        return DB::transaction(function () use ($employee, $amount, $data) {
            $recovery = EmployeeAdvance::create([
                'employee_id'        => $employee->id,
                'advance_number'     => $this->generateNumber(),
                'type'               => 'recovery',
                'amount'             => $amount,
                'advance_date'       => $data['advance_date'] ?? now()->toDateString(),
                'payment_account_id' => $data['payment_account_id'] ?? null,
                'reference'          => $data['reference'] ?? null,
                'note'               => $data['note'] ?? null,
                'created_by'         => auth()->id(),
            ]);

            $je = $this->recordJournal(
                'employee_advance_recovery', $employee, $recovery,
                debitCode: $this->cashAccountCode($recovery->payment_account_id),
                creditCode: self::RECEIVABLE_CODE,
                description: "Advance recovery: {$employee->name} ({$recovery->advance_number})",
            );
            if ($je) {
                $recovery->update(['journal_entry_id' => $je->id]);
            }

            $employee->decrement('advance_balance', $amount);

            return $recovery;
        });
    }

    /**
     * Per-employee advance ledger with a running outstanding balance.
     */
    public function getLedger(Employee $employee): array
    {
        $running = 0.0;
        $entries = [];

        $advances = EmployeeAdvance::where('employee_id', $employee->id)
            ->orderBy('advance_date')->orderBy('id')->get();

        foreach ($advances as $adv) {
            $given = $adv->type === 'advance' ? (float) $adv->amount : 0.0;
            $recovered = $adv->type === 'recovery' ? (float) $adv->amount : 0.0;
            $running = round($running + $given - $recovered, 2);
            $entries[] = [
                'date'      => $adv->advance_date?->toDateString(),
                'reference' => $adv->advance_number,
                'type'      => $adv->type === 'advance' ? 'Advance Given' : 'Recovery',
                'given'     => $given,
                'recovered' => $recovered,
                'balance'   => $running,
            ];
        }

        return [
            'entries'        => $entries,
            'totalGiven'     => (float) $advances->where('type', 'advance')->sum('amount'),
            'totalRecovered' => (float) $advances->where('type', 'recovery')->sum('amount'),
            'currentBalance' => (float) $employee->advance_balance,
        ];
    }

    // ── Helpers ──

    private function generateNumber(): string
    {
        $year = now()->format('Y');
        $count = EmployeeAdvance::withTrashed()->whereYear('created_at', $year)->count() + 1;

        return 'EMPADV-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    private function cashAccountCode(?int $paymentAccountId): string
    {
        $typeToCode = ['cash' => '1001', 'mobile_banking' => '1002', 'bank' => '1004', 'card' => '1004'];
        $type = $paymentAccountId
            ? \Modules\Payment\Models\PaymentAccount::where('id', $paymentAccountId)->value('account_type')
            : 'cash';

        return $typeToCode[$type] ?? '1001';
    }

    private function recordJournal(string $sourceType, Employee $employee, EmployeeAdvance $advance, string $debitCode, string $creditCode, string $description): ?\Modules\Accounting\Models\JournalEntry
    {
        $debitId = Account::where('account_code', $debitCode)->value('id');
        $creditId = Account::where('account_code', $creditCode)->value('id');

        if (! $debitId || ! $creditId) {
            Log::warning("Missing accounts for employee advance journal: debit={$debitCode}, credit={$creditCode}");
            return null;
        }

        try {
            return $this->journalService->createFromSource(
                $sourceType,
                $employee->id,
                [
                    ['account_id' => $debitId, 'debit_amount' => $advance->amount, 'credit_amount' => 0, 'description' => $description],
                    ['account_id' => $creditId, 'debit_amount' => 0, 'credit_amount' => $advance->amount, 'description' => $description],
                ],
                $description,
                $advance->reference ?? $advance->advance_number,
                $advance->advance_date,
            );
        } catch (\Throwable $e) {
            Log::warning("Failed to record employee advance journal: {$e->getMessage()}");
            return null;
        }
    }
}
