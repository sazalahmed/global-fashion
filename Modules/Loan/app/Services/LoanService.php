<?php

namespace Modules\Loan\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Helpers\Upload;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Loan\Models\Lender;
use Modules\Loan\Models\Loan;
use Modules\Loan\Models\LoanSchedule;

class LoanService
{
    public function __construct(
        private readonly JournalEntryService $journalService,
    ) {}

    // ══════════════════════════════════════
    //  LENDER OPERATIONS
    // ══════════════════════════════════════

    public function listLenders(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Lender::query()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when(($filters['has_outstanding'] ?? null) === '1', fn ($q) => $q->hasOutstanding())
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createLender(array $data): Lender
    {
        $data['created_by'] = auth()->id();
        $data['outstanding_balance'] = (float) ($data['opening_balance'] ?? 0);

        if (isset($data['photo']) && $data['photo']) {
            $data['photo'] = Upload::store($data['photo'], 'lenders');
        }

        return Lender::create($data);
    }

    public function updateLender(Lender $lender, array $data): Lender
    {
        if (isset($data['photo']) && $data['photo']) {
            if ($lender->photo) {
                Upload::delete($lender->photo);
            }
            $data['photo'] = Upload::store($data['photo'], 'lenders');
        } else {
            unset($data['photo']);
        }

        $lender->update($data);

        return $lender;
    }

    public function deleteLender(Lender $lender): void
    {
        $activeLoans = $lender->loans()->whereNotIn('status', ['completed', 'cancelled'])->count();

        if ($activeLoans > 0) {
            throw new \RuntimeException("Cannot delete lender — {$activeLoans} active loan(s) exist.");
        }

        if ((float) $lender->outstanding_balance > 0) {
            throw new \RuntimeException('Cannot delete lender — outstanding balance of ' . currency_symbol() . ' ' . number_format($lender->outstanding_balance) . ' exists.');
        }

        if ($lender->photo) {
            Upload::delete($lender->photo);
        }

        $lender->delete();
    }

    public function getLenderLedger(Lender $lender, array $filters = []): array
    {
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        // Disbursements (loans taken — we owe more)
        $disbursements = Loan::where('lender_id', $lender->id)
            ->whereNotIn('status', ['cancelled'])
            ->whereNull('deleted_at')
            ->when($from, fn ($q) => $q->where('disbursement_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('disbursement_date', '<=', $to))
            ->get()
            ->map(fn ($loan) => [
                'date'        => $loan->disbursement_date,
                'reference'   => $loan->loan_number,
                'type'        => 'Loan Disbursement',
                'debit'       => 0,
                'credit'      => (float) $loan->principal_amount,
                'description' => "Loan taken: {$loan->loan_number}",
            ]);

        // Repayments (payments made — we owe less)
        $repayments = LoanSchedule::whereHas('loan', fn ($q) => $q->where('lender_id', $lender->id)->whereNull('deleted_at'))
            ->where('paid_amount', '>', 0)
            ->when($from, fn ($q) => $q->where('paid_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('paid_date', '<=', $to))
            ->with('loan')
            ->get()
            ->map(fn ($schedule) => [
                'date'        => $schedule->paid_date,
                'reference'   => $schedule->loan->loan_number . ' #' . $schedule->installment_number,
                'type'        => 'Loan Repayment',
                'debit'       => (float) $schedule->paid_amount,
                'credit'      => 0,
                'description' => "Installment #{$schedule->installment_number}",
            ]);

        $entries = $disbursements->merge($repayments)->sortBy('date')->values();

        $totalDebit = $entries->sum('debit');
        $totalCredit = $entries->sum('credit');

        return [
            'entries'        => $entries,
            'totalDebit'     => $totalDebit,
            'totalCredit'    => $totalCredit,
            'currentBalance' => (float) $lender->outstanding_balance,
        ];
    }

    public function getLenderStats(): array
    {
        return [
            'total_lenders'     => Lender::count(),
            'active'            => Lender::active()->count(),
            'total_borrowed'    => (float) Lender::sum('total_borrowed'),
            'total_repaid'      => (float) Lender::sum('total_repaid'),
            'total_outstanding' => (float) Lender::sum('outstanding_balance'),
        ];
    }

    // ══════════════════════════════════════
    //  LOAN OPERATIONS
    // ══════════════════════════════════════

    public function listLoans(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Loan::with(['lender', 'disbursementAccount'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->whereHas('lender', fn ($lq) => $lq->search($s))->orWhere('loan_number', 'like', "%{$s}%"))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['lender_id'] ?? null, fn ($q, $id) => $q->where('lender_id', $id))
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->where('disbursement_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->where('disbursement_date', '<=', $d))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findLoan(int $id): Loan
    {
        return Loan::with([
            'lender', 'schedules', 'journalEntry.lines.account',
            'disbursementAccount', 'branch', 'creator',
        ])->findOrFail($id);
    }

    public function createLoan(array $data): Loan
    {
        return DB::transaction(function () use ($data) {
            $principal = (float) $data['principal_amount'];
            $totalInstallments = (int) $data['total_installments'];
            $installmentAmount = round($principal / $totalInstallments, 2);

            $loan = Loan::create([
                'loan_number'            => $this->generateLoanNumber(),
                'lender_id'              => $data['lender_id'],
                'principal_amount'       => $principal,
                'disbursement_date'      => $data['disbursement_date'],
                'disbursement_account_id' => $data['disbursement_account_id'],
                'total_installments'     => $totalInstallments,
                'installment_amount'     => $installmentAmount,
                'frequency'              => $data['frequency'] ?? 'monthly',
                'start_date'             => $data['start_date'],
                'total_repaid'           => 0,
                'total_remaining'        => $principal,
                'status'                 => 'active',
                'reference'              => $data['reference'] ?? null,
                'note'                   => $data['note'] ?? null,
                'branch_id'              => $data['branch_id'] ?? auth()->user()->branch_id ?? null,
                'created_by'             => auth()->id(),
            ]);

            // Generate repayment schedule
            $this->generateSchedule($loan, $installmentAmount, $totalInstallments, $principal);

            // Set next_due_date
            $loan->update(['next_due_date' => $loan->schedules()->upcoming()->min('due_date')]);

            // Record disbursement journal entry: DR Cash/Bank, CR Loan Payable
            $this->recordDisbursementJournal($loan);

            // Update lender totals
            $lender = Lender::find($loan->lender_id);
            $lender->increment('total_borrowed', $principal);
            $lender->outstanding_balance = (float) $lender->total_borrowed + (float) $lender->opening_balance - (float) $lender->total_repaid;
            $lender->save();

            return $loan->fresh()->load(['lender', 'schedules']);
        });
    }

    public function recordRepayment(Loan $loan, array $data): LoanSchedule
    {
        return DB::transaction(function () use ($loan, $data) {
            $schedule = LoanSchedule::findOrFail($data['schedule_id']);
            $amount = (float) $data['amount'];
            $paymentAccountId = $data['payment_account_id'];

            $remaining = (float) $schedule->amount - (float) $schedule->paid_amount;
            $payAmount = min($amount, $remaining);

            // Update schedule
            $schedule->paid_amount = (float) $schedule->paid_amount + $payAmount;
            $schedule->status = $schedule->paid_amount >= (float) $schedule->amount ? 'paid' : 'partial';
            $schedule->paid_date = $data['payment_date'] ?? now()->toDateString();
            $schedule->payment_account_id = $paymentAccountId;
            $schedule->note = $data['note'] ?? null;
            $schedule->save();

            // Record repayment journal entry: DR Loan Payable, CR Cash/Bank
            $je = $this->recordRepaymentJournal($loan, $schedule, $payAmount, $paymentAccountId);
            $schedule->update(['journal_entry_id' => $je?->id]);

            // Update loan totals
            $loan->total_repaid = (float) $loan->total_repaid + $payAmount;
            $loan->total_remaining = max(0, (float) $loan->principal_amount - (float) $loan->total_repaid);

            if ($loan->total_remaining <= 0) {
                $loan->status = 'completed';
            }

            // Update next_due_date
            $nextDue = $loan->schedules()->whereIn('status', ['upcoming', 'partial', 'overdue'])->min('due_date');
            $loan->next_due_date = $nextDue;
            $loan->save();

            // Update lender totals
            $lender = $loan->lender;
            $lender->increment('total_repaid', $payAmount);
            $lender->outstanding_balance = max(0, (float) $lender->total_borrowed + (float) $lender->opening_balance - (float) $lender->total_repaid);
            $lender->save();

            return $schedule;
        });
    }

    public function cancelLoan(Loan $loan): Loan
    {
        if ((float) $loan->total_repaid > 0) {
            throw new \RuntimeException('Cannot cancel a loan that has repayments. Clear repayments first.');
        }

        return DB::transaction(function () use ($loan) {
            // Void disbursement journal entry
            if ($loan->journal_entry_id) {
                try {
                    $this->journalService->void(
                        $loan->journalEntry,
                        "Loan {$loan->loan_number} cancelled",
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to void loan journal: {$e->getMessage()}");
                }
            }

            // Reverse lender totals
            $lender = $loan->lender;
            $lender->decrement('total_borrowed', (float) $loan->principal_amount);
            $lender->outstanding_balance = max(0, (float) $lender->total_borrowed + (float) $lender->opening_balance - (float) $lender->total_repaid);
            $lender->save();

            $loan->update(['status' => 'cancelled']);

            return $loan;
        });
    }

    public function getStats(): array
    {
        return [
            'total_loans'       => Loan::whereNull('deleted_at')->count(),
            'active_loans'      => Loan::active()->count(),
            'overdue_loans'     => Loan::overdue()->count(),
            'completed_loans'   => Loan::completed()->count(),
            'total_borrowed'    => (float) Loan::whereIn('status', ['active', 'completed', 'overdue'])->sum('principal_amount'),
            'total_repaid'      => (float) Loan::whereIn('status', ['active', 'completed', 'overdue'])->sum('total_repaid'),
            'total_outstanding' => (float) Loan::whereIn('status', ['active', 'overdue'])->sum('total_remaining'),
        ];
    }

    public function getUpcomingDue(int $days = 7): Collection
    {
        return LoanSchedule::with(['loan.lender'])
            ->dueWithin($days)
            ->limit(20)
            ->get();
    }

    public function markOverdue(): int
    {
        $count = LoanSchedule::whereIn('status', ['upcoming', 'partial'])
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        // Also mark parent loans as overdue
        Loan::where('status', 'active')
            ->whereHas('schedules', fn ($q) => $q->where('status', 'overdue'))
            ->update(['status' => 'overdue']);

        return $count;
    }

    public function recalculateLenderBalances(Lender $lender): void
    {
        $totalBorrowed = Loan::where('lender_id', $lender->id)
            ->whereNotIn('status', ['cancelled'])
            ->whereNull('deleted_at')
            ->sum('principal_amount');

        $totalRepaid = Loan::where('lender_id', $lender->id)
            ->whereNotIn('status', ['cancelled'])
            ->whereNull('deleted_at')
            ->sum('total_repaid');

        $lender->update([
            'total_borrowed'      => $totalBorrowed,
            'total_repaid'        => $totalRepaid,
            'outstanding_balance' => max(0, (float) $totalBorrowed + (float) $lender->opening_balance - (float) $totalRepaid),
        ]);
    }

    // ══════════════════════════════════════
    //  RESCHEDULE
    // ══════════════════════════════════════

    public function rescheduleUnpaid(Loan $loan, array $schedules): void
    {
        if (!in_array($loan->status, ['active', 'overdue'])) {
            throw new \RuntimeException('Only active or overdue loans can be rescheduled.');
        }

        DB::transaction(function () use ($loan, $schedules) {
            $paidTotal = $loan->schedules()->where('status', 'paid')->sum('amount');
            $unpaidIds = $loan->schedules()->where('status', '!=', 'paid')->pluck('id')->toArray();

            $newUnpaidTotal = 0;
            foreach ($schedules as $item) {
                if (!in_array((int) $item['id'], $unpaidIds)) {
                    continue;
                }
                $newUnpaidTotal += (float) $item['amount'];
            }

            $expectedTotal = round((float) $loan->principal_amount - $paidTotal, 2);
            if (round($newUnpaidTotal, 2) !== $expectedTotal) {
                throw new \RuntimeException(
                    'Total of unpaid installments (' . currency_symbol() . ' ' . number_format($newUnpaidTotal, 2) .
                    ') must equal remaining principal (' . currency_symbol() . ' ' . number_format($expectedTotal, 2) . ').'
                );
            }

            foreach ($schedules as $item) {
                $schedule = LoanSchedule::find($item['id']);
                if (!$schedule || $schedule->loan_id !== $loan->id || $schedule->status === 'paid') {
                    continue;
                }

                $schedule->update([
                    'due_date' => $item['due_date'],
                    'amount'   => round((float) $item['amount'], 2),
                ]);
            }

            // Update loan next_due_date
            $nextDue = $loan->schedules()->whereIn('status', ['upcoming', 'partial', 'overdue'])->min('due_date');
            $loan->update(['next_due_date' => $nextDue]);
        });
    }

    // ══════════════════════════════════════
    //  PRIVATE HELPERS
    // ══════════════════════════════════════

    private function generateSchedule(Loan $loan, float $installmentAmount, int $totalInstallments, float $principal): void
    {
        $dueDate = \Carbon\Carbon::parse($loan->start_date);
        $totalScheduled = 0;

        for ($i = 1; $i <= $totalInstallments; $i++) {
            $amount = $installmentAmount;

            // Last installment absorbs rounding remainder
            if ($i === $totalInstallments) {
                $amount = $principal - $totalScheduled;
            }

            LoanSchedule::create([
                'loan_id'            => $loan->id,
                'installment_number' => $i,
                'due_date'           => $dueDate->toDateString(),
                'amount'             => round($amount, 2),
            ]);

            $totalScheduled += $amount;

            // Advance due date by frequency
            $dueDate = match ($loan->frequency) {
                'weekly'    => $dueDate->copy()->addWeek(),
                'bi_weekly' => $dueDate->copy()->addWeeks(2),
                'monthly'   => $dueDate->copy()->addMonth(),
                default     => $dueDate->copy()->addMonth(),
            };
        }
    }

    private function generateLoanNumber(): string
    {
        $year = now()->format('Y');
        $count = Loan::withTrashed()->whereYear('created_at', $year)->count() + 1;

        return 'LOAN-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    private function resolveAssetAccountId(?int $paymentAccountId): ?int
    {
        $typeToCode = [
            'cash'            => '1001',
            'mobile_banking'  => '1002',
            'bank'            => '1004',
            'card'            => '1004',
        ];

        if ($paymentAccountId) {
            $accountType = \Modules\Payment\Models\PaymentAccount::where('id', $paymentAccountId)->value('account_type');
            $code = $typeToCode[$accountType] ?? '1001';

            return Account::where('account_code', $code)->value('id')
                ?? Account::where('account_type', 'asset')->value('id');
        }

        return Account::where('account_code', '1001')->value('id')
            ?? Account::where('account_type', 'asset')->value('id');
    }

    private function getLoanPayableAccountId(): ?int
    {
        return Account::where('account_code', '2100')->value('id')
            ?? Account::where('account_type', 'liability')->value('id');
    }

    private function recordDisbursementJournal(Loan $loan): void
    {
        $assetAccountId = $this->resolveAssetAccountId($loan->disbursement_account_id);
        $liabilityAccountId = $this->getLoanPayableAccountId();

        if (!$assetAccountId || !$liabilityAccountId) {
            \Illuminate\Support\Facades\Log::warning("Missing accounts for loan disbursement journal: asset={$assetAccountId}, liability={$liabilityAccountId}");
            return;
        }

        try {
            $je = $this->journalService->createFromSource(
                'loan_disbursement',
                $loan->id,
                [
                    ['account_id' => $assetAccountId, 'debit_amount' => $loan->principal_amount, 'credit_amount' => 0, 'description' => "Loan received: {$loan->loan_number}"],
                    ['account_id' => $liabilityAccountId, 'debit_amount' => 0, 'credit_amount' => $loan->principal_amount, 'description' => "Loan payable: {$loan->loan_number}"],
                ],
                "Loan disbursement: {$loan->loan_number} from {$loan->lender->name}",
                $loan->loan_number,
                $loan->disbursement_date,
            );

            $loan->update(['journal_entry_id' => $je->id]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to record loan disbursement journal: {$e->getMessage()}");
        }
    }

    private function recordRepaymentJournal(Loan $loan, LoanSchedule $schedule, float $amount, int $paymentAccountId): ?\Modules\Accounting\Models\JournalEntry
    {
        $assetAccountId = $this->resolveAssetAccountId($paymentAccountId);
        $liabilityAccountId = $this->getLoanPayableAccountId();

        if (!$assetAccountId || !$liabilityAccountId) {
            return null;
        }

        try {
            return $this->journalService->createFromSource(
                'loan_repayment',
                $loan->id,
                [
                    ['account_id' => $liabilityAccountId, 'debit_amount' => $amount, 'credit_amount' => 0, 'description' => "Loan repayment: {$loan->loan_number} #{$schedule->installment_number}"],
                    ['account_id' => $assetAccountId, 'debit_amount' => 0, 'credit_amount' => $amount, 'description' => "Paid from account: {$loan->loan_number}"],
                ],
                "Loan repayment: {$loan->loan_number} installment #{$schedule->installment_number}",
                $loan->loan_number,
                $schedule->paid_date,
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to record loan repayment journal: {$e->getMessage()}");
            return null;
        }
    }
}
