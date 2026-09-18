<?php

namespace Modules\Loan\Services;

use App\Helpers\Upload;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Loan\Models\Borrower;
use Modules\Loan\Models\PersonalLoanTransaction;

/**
 * Personal loans: a two-way running account per person. The business can
 * lend money out (disbursement / repayment — receivable side) and borrow
 * from the same person (loan_taken / loan_return — payable side). One signed
 * balance: positive = they owe us, negative = we owe them. Flexible amounts,
 * no schedule or interest.
 *
 * Bookkeeping stays gross ("keep it simple" rule): loans given post to the
 * receivable account, loans taken post to the payable account — no automatic
 * set-off between the two sides. Repayments are validated against the gross
 * side they settle, not the signed net.
 */
class PersonalLoanService
{
    /** Asset account personal-loan receivables post to. */
    private const RECEIVABLE_CODE = '1015';

    /** Liability account personal loans taken from people post to. */
    private const PAYABLE_CODE = '2025';

    public function __construct(
        private readonly JournalEntryService $journalService,
    ) {}

    // ── Party CRUD ──

    public function listBorrowers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Borrower::query()
            ->withCount('transactions')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when(($filters['has_outstanding'] ?? null) === '1', fn ($q) => $q->hasOutstanding())
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createBorrower(array $data): Borrower
    {
        $data['created_by'] = auth()->id();
        $data['opening_balance'] = $this->signedOpening($data);
        $data['outstanding_balance'] = $data['opening_balance'];
        unset($data['opening_type']);

        if (isset($data['photo']) && $data['photo']) {
            $data['photo'] = Upload::store($data['photo'], 'borrowers');
        }

        return Borrower::create($data);
    }

    public function updateBorrower(Borrower $borrower, array $data): Borrower
    {
        if (isset($data['photo']) && $data['photo']) {
            if ($borrower->photo) {
                Upload::delete($borrower->photo);
            }
            $data['photo'] = Upload::store($data['photo'], 'borrowers');
        } else {
            unset($data['photo']);
        }

        $data['opening_balance'] = $this->signedOpening($data);
        unset($data['opening_type']);

        $borrower->update($data);
        $this->recomputeOutstanding($borrower);

        return $borrower->fresh();
    }

    public function deleteBorrower(Borrower $borrower): void
    {
        if (abs((float) $borrower->outstanding_balance) > 0.009) {
            throw new \RuntimeException('Cannot delete — unsettled balance of ' . currency_symbol() . ' ' . number_format(abs($borrower->outstanding_balance)) . ' exists.');
        }

        // A party with loan history is a financial record — deleting them
        // would orphan the ledger and its journal entries. Keep the history;
        // mark the record inactive instead.
        $txnCount = $borrower->transactions()->count();
        if ($txnCount > 0) {
            throw new \RuntimeException(trans_choice(
                'Cannot delete — :count loan transaction exists. Mark the record inactive instead.|Cannot delete — :count loan transactions exist. Mark the record inactive instead.',
                $txnCount,
                ['count' => $txnCount]
            ));
        }

        if ($borrower->photo) {
            Upload::delete($borrower->photo);
        }

        $borrower->delete();
    }

    // ── Transactions: lending side (we give) ──

    /**
     * Give money to the person (money leaves the business).
     * DR Loans & Advances (Receivable) / CR Cash-Bank.
     */
    public function recordDisbursement(Borrower $borrower, array $data): PersonalLoanTransaction
    {
        $amount = $this->validAmount($data);

        return DB::transaction(function () use ($borrower, $amount, $data) {
            $txn = $this->createTxn($borrower, 'disbursement', $amount, $data);

            $je = $this->recordJournal(
                'personal_loan_disbursement', $borrower, $txn,
                debitCode: self::RECEIVABLE_CODE,
                creditCode: $this->cashAccountCode($txn->payment_account_id),
                description: "Personal loan given: {$borrower->name} ({$txn->txn_number})",
            );
            if ($je) {
                $txn->update(['journal_entry_id' => $je->id]);
            }

            $borrower->increment('total_lent', $amount);
            $this->recomputeOutstanding($borrower->fresh());

            return $txn;
        });
    }

    /**
     * Receive a repayment from the person (money returns to the business).
     * DR Cash-Bank / CR Loans & Advances (Receivable). Validated against the
     * gross receivable side — legitimate even while we also owe them.
     */
    public function recordRepayment(Borrower $borrower, array $data): PersonalLoanTransaction
    {
        $amount = $this->validAmount($data, capTo: $borrower->receivable_outstanding);

        return DB::transaction(function () use ($borrower, $amount, $data) {
            $txn = $this->createTxn($borrower, 'repayment', $amount, $data);

            $je = $this->recordJournal(
                'personal_loan_repayment', $borrower, $txn,
                debitCode: $this->cashAccountCode($txn->payment_account_id),
                creditCode: self::RECEIVABLE_CODE,
                description: "Personal loan repayment: {$borrower->name} ({$txn->txn_number})",
            );
            if ($je) {
                $txn->update(['journal_entry_id' => $je->id]);
            }

            $borrower->increment('total_recovered', $amount);
            $this->recomputeOutstanding($borrower->fresh());

            return $txn;
        });
    }

    // ── Transactions: borrowing side (we take) ──

    /**
     * Take a loan from the person (money enters the business).
     * DR Cash-Bank / CR Personal Loans (Payable).
     */
    public function recordTaken(Borrower $borrower, array $data): PersonalLoanTransaction
    {
        $amount = $this->validAmount($data);

        return DB::transaction(function () use ($borrower, $amount, $data) {
            $txn = $this->createTxn($borrower, 'loan_taken', $amount, $data);

            $je = $this->recordJournal(
                'personal_loan_taken', $borrower, $txn,
                debitCode: $this->cashAccountCode($txn->payment_account_id),
                creditCode: self::PAYABLE_CODE,
                description: "Personal loan taken from: {$borrower->name} ({$txn->txn_number})",
            );
            if ($je) {
                $txn->update(['journal_entry_id' => $je->id]);
            }

            $borrower->increment('total_taken', $amount);
            $this->recomputeOutstanding($borrower->fresh());

            return $txn;
        });
    }

    /**
     * Pay back a loan taken from the person (money leaves the business).
     * DR Personal Loans (Payable) / CR Cash-Bank. Validated against the
     * gross payable side.
     */
    public function recordReturn(Borrower $borrower, array $data): PersonalLoanTransaction
    {
        $amount = $this->validAmount($data, capTo: $borrower->payable_outstanding);

        return DB::transaction(function () use ($borrower, $amount, $data) {
            $txn = $this->createTxn($borrower, 'loan_return', $amount, $data);

            $je = $this->recordJournal(
                'personal_loan_return', $borrower, $txn,
                debitCode: self::PAYABLE_CODE,
                creditCode: $this->cashAccountCode($txn->payment_account_id),
                description: "Personal loan paid back to: {$borrower->name} ({$txn->txn_number})",
            );
            if ($je) {
                $txn->update(['journal_entry_id' => $je->id]);
            }

            $borrower->increment('total_returned', $amount);
            $this->recomputeOutstanding($borrower->fresh());

            return $txn;
        });
    }

    // ── Ledger & stats ──

    /**
     * Running-balance ledger over all four transaction types. "Money Out"
     * (loan given / paid back) raises the signed balance, "Money In"
     * (repayment / loan taken) lowers it. Positive balance = they owe us,
     * negative = we owe them.
     */
    public function getLedger(Borrower $borrower, array $filters = []): array
    {
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        $opening = (float) $borrower->opening_balance;
        $running = $opening;
        $entries = [];

        $entries[] = [
            'date'        => $borrower->created_at?->toDateString(),
            'reference'   => '—',
            'type'        => 'Opening Balance',
            'out'         => max(0.0, $opening),
            'in'          => max(0.0, -$opening),
            'balance'     => $running,
            'is_opening'  => true,
        ];

        $typeLabels = [
            'disbursement' => 'Loan Given',
            'repayment'    => 'Repayment',
            'loan_taken'   => 'Loan Taken',
            'loan_return'  => 'Paid Back',
        ];

        $txns = $borrower->transactions()
            ->when($from, fn ($q) => $q->where('txn_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('txn_date', '<=', $to))
            ->get();

        foreach ($txns as $txn) {
            $out = in_array($txn->type, ['disbursement', 'loan_return'], true) ? (float) $txn->amount : 0.0;
            $in = in_array($txn->type, ['repayment', 'loan_taken'], true) ? (float) $txn->amount : 0.0;
            $running = round($running + $out - $in, 2);
            $entries[] = [
                'date'      => $txn->txn_date?->toDateString(),
                'reference' => $txn->txn_number,
                'type'      => $typeLabels[$txn->type] ?? $txn->type,
                'out'       => $out,
                'in'        => $in,
                'balance'   => $running,
                'is_opening' => false,
            ];
        }

        return [
            'entries'        => $entries,
            'totalGiven'     => (float) $borrower->total_lent,
            'totalRepaid'    => (float) $borrower->total_recovered,
            'totalTaken'     => (float) $borrower->total_taken,
            'totalReturned'  => (float) $borrower->total_returned,
            'currentBalance' => (float) $borrower->outstanding_balance,
        ];
    }

    public function getStats(): array
    {
        return [
            'total_borrowers'   => Borrower::count(),
            'active'            => Borrower::active()->count(),
            'total_lent'        => (float) Borrower::sum('total_lent'),
            'total_recovered'   => (float) Borrower::sum('total_recovered'),
            'total_taken'       => (float) Borrower::sum('total_taken'),
            'total_returned'    => (float) Borrower::sum('total_returned'),
            // Net positions across parties: what people owe us vs what we owe.
            'total_receivable'  => (float) Borrower::where('outstanding_balance', '>', 0)->sum('outstanding_balance'),
            'total_payable'     => (float) abs(Borrower::where('outstanding_balance', '<', 0)->sum('outstanding_balance')),
        ];
    }

    // ── Helpers ──

    /**
     * The opening balance as entered on the form, signed by which side owes:
     * 'they_owe' (default) keeps it positive, 'we_owe' stores it negative.
     */
    private function signedOpening(array $data): float
    {
        $amount = abs((float) ($data['opening_balance'] ?? 0));

        return ($data['opening_type'] ?? 'they_owe') === 'we_owe' ? -$amount : $amount;
    }

    private function validAmount(array $data, ?float $capTo = null): float
    {
        $amount = (float) $data['amount'];
        if ($amount <= 0) {
            throw new \RuntimeException('Amount must be greater than zero.');
        }
        if ($capTo !== null && $amount > $capTo + 0.01) {
            throw new \RuntimeException('Payment (' . currency_symbol() . ' ' . number_format($amount) . ') exceeds the outstanding balance (' . currency_symbol() . ' ' . number_format($capTo) . ').');
        }

        return $amount;
    }

    private function createTxn(Borrower $borrower, string $type, float $amount, array $data): PersonalLoanTransaction
    {
        return PersonalLoanTransaction::create([
            'borrower_id'        => $borrower->id,
            'txn_number'         => $this->generateTxnNumber(),
            'type'               => $type,
            'amount'             => $amount,
            'txn_date'           => $data['txn_date'] ?? now()->toDateString(),
            'payment_account_id' => $data['payment_account_id'] ?? null,
            'reference'          => $data['reference'] ?? null,
            'note'               => $data['note'] ?? null,
            'created_by'         => auth()->id(),
        ]);
    }

    private function recomputeOutstanding(Borrower $borrower): void
    {
        $outstanding = (float) $borrower->opening_balance
            + (float) $borrower->total_lent - (float) $borrower->total_recovered
            - (float) $borrower->total_taken + (float) $borrower->total_returned;

        $borrower->update(['outstanding_balance' => round($outstanding, 2)]);
    }

    private function generateTxnNumber(): string
    {
        $year = now()->format('Y');
        $count = PersonalLoanTransaction::withTrashed()->whereYear('created_at', $year)->count() + 1;

        return 'PL-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    private function cashAccountCode(?int $paymentAccountId): string
    {
        $typeToCode = ['cash' => '1001', 'mobile_banking' => '1002', 'bank' => '1004', 'card' => '1004'];
        $type = $paymentAccountId
            ? \Modules\Payment\Models\PaymentAccount::where('id', $paymentAccountId)->value('account_type')
            : 'cash';

        return $typeToCode[$type] ?? '1001';
    }

    private function recordJournal(string $sourceType, Borrower $borrower, PersonalLoanTransaction $txn, string $debitCode, string $creditCode, string $description): ?\Modules\Accounting\Models\JournalEntry
    {
        $debitId = Account::where('account_code', $debitCode)->value('id');
        $creditId = Account::where('account_code', $creditCode)->value('id');

        if (! $debitId || ! $creditId) {
            Log::warning("Missing accounts for personal loan journal: debit={$debitCode}, credit={$creditCode}");
            return null;
        }

        try {
            return $this->journalService->createFromSource(
                $sourceType,
                $borrower->id,
                [
                    ['account_id' => $debitId, 'debit_amount' => $txn->amount, 'credit_amount' => 0, 'description' => $description],
                    ['account_id' => $creditId, 'debit_amount' => 0, 'credit_amount' => $txn->amount, 'description' => $description],
                ],
                $description,
                $txn->reference ?? $txn->txn_number,
                $txn->txn_date,
            );
        } catch (\Throwable $e) {
            Log::warning("Failed to record personal loan journal: {$e->getMessage()}");
            return null;
        }
    }
}
