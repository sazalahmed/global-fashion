<?php

namespace Modules\Expense\Services;

use App\Helpers\Upload;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Accounting\Services\JournalEntryService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(
        private readonly JournalEntryService $journalService
    ) {}

    /**
     * Paginated expense list with filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)
            ->with(['category', 'account', 'paymentAccount', 'creator', 'branch'])
            ->latest('expense_date')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Shared filter chain used by list(), getStats() and filteredTotal() so the
     * index table, its stats and its totals row all scope to the same records.
     *
     * date_from/date_to filter by the date the expense was actually PAID
     * (its journal entry's entry_date), not expense_date. Cashflow and the
     * P&L both recognize an expense on payment — this app has no accrual
     * recognition at all, an expense has zero ledger effect until it's
     * marked paid — so filtering this list by expense_date instead made its
     * total silently disagree with both of those reports for the same date
     * range. A date filter now implies "paid within this range", which also
     * means a still-pending expense (no journal entry, no payment date) has
     * nothing to filter by and drops out of a date-filtered view; it still
     * shows in the unfiltered list.
     */
    private function filteredQuery(array $filters = [])
    {
        $hasDateFilter = ($filters['date_from'] ?? null) || ($filters['date_to'] ?? null);

        return Expense::query()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['category'] ?? null, fn ($q, $c) => $q->byCategory((int) $c))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['payment_account_id'] ?? null, fn ($q, $v) => $q->where('payment_account_id', $v))
            ->when($hasDateFilter, fn ($q) => $q->whereHas('journalEntry', function ($jq) use ($filters) {
                $jq->postedEffective()
                    ->when($filters['date_from'] ?? null, fn ($jq2, $d) => $jq2->where('entry_date', '>=', $d))
                    ->when($filters['date_to'] ?? null, fn ($jq2, $d) => $jq2->where('entry_date', '<=', $d));
            }));
    }

    /**
     * Sum of total_amount across all records matching the current filters
     * (not just the current page) — powers the index totals row.
     */
    public function filteredTotal(array $filters = []): float
    {
        return (float) $this->filteredQuery($filters)->sum('total_amount');
    }

    /**
     * Find a single expense with all relations.
     */
    public function find(int $id): Expense
    {
        return Expense::with(['category', 'account', 'paymentAccount', 'journalEntry.lines.account', 'creator', 'approver', 'rejector', 'branch'])
            ->findOrFail($id);
    }

    /**
     * Create a new expense.
     */
    public function create(array $data): Expense
    {
        // The GL (accounting) account for the expense comes from the selected
        // category. No expense category has account_id configured yet, so
        // every expense falls back here — to a deliberate, correctly-labelled
        // "General Operating Expenses" account (5165), never to an arbitrary
        // "first expense-type account by id" (that used to resolve to 5200
        // Loss on Asset Disposal, silently misfiling real operating costs as
        // one-off asset-disposal losses).
        if (empty($data['account_id'])) {
            $data['account_id'] = ExpenseCategory::find($data['expense_category_id'] ?? null)?->account_id
                ?? \Modules\Accounting\Models\Account::where('account_code', '5165')->value('id')
                ?? \Modules\Accounting\Models\Account::where('account_type', 'expense')->value('id');
        }

        $data['expense_number'] = $this->generateNumber();
        $data['total_amount'] = ((float) ($data['amount'] ?? 0)) + ((float) ($data['tax_amount'] ?? 0));
        $data['created_by'] = auth()->id();
        $data['branch_id'] = $data['branch_id'] ?? auth()->user()?->branch_id;
        // Take the method from the account that was chosen. The form posts only
        // payment_account_id, so defaulting to 'Cash' labelled every bank and
        // wallet expense as cash in the list. Shares one resolver with
        // PaymentService so the two modules cannot drift apart.
        $data['payment_method'] = $data['payment_method']
            ?? app(\Modules\Payment\Services\PaymentService::class)
                ->methodForAccount(isset($data['payment_account_id']) ? (int) $data['payment_account_id'] : null);
        $data['due_amount'] = $data['total_amount'];
        $data['paid_amount'] = 0;
        $data['payment_status'] = 'unpaid';

        if (!empty($data['receipt']) && $data['receipt'] instanceof \Illuminate\Http\UploadedFile) {
            $data['receipt_path'] = \App\Helpers\Upload::store($data['receipt'], 'expense-receipts');
            unset($data['receipt']);
        }

        $expense = Expense::create($data);

        return $expense;
    }

    /**
     * Update an existing expense (only pending expenses).
     */
    public function update(Expense $expense, array $data): Expense
    {
        if ($expense->status !== 'pending') {
            throw new \RuntimeException('Only pending expenses can be edited.');
        }

        if (isset($data['amount']) || isset($data['tax_amount'])) {
            $data['total_amount'] = ((float) ($data['amount'] ?? $expense->amount))
                + ((float) ($data['tax_amount'] ?? $expense->tax_amount));
            $data['due_amount'] = max(0, $data['total_amount'] - (float) $expense->paid_amount);
        }

        if (!empty($data['receipt']) && $data['receipt'] instanceof \Illuminate\Http\UploadedFile) {
            $data['receipt_path'] = \App\Helpers\Upload::store($data['receipt'], 'expense-receipts');
            unset($data['receipt']);
        }

        $expense->update($data);

        return $expense->fresh();
    }

    /**
     * Delete an expense of any status. Expense payments have no row of their
     * own (unlike sale/purchase payments) — they only exist as posted journal
     * entries ('expense' from markPaid, 'expense_payment' from recordPayment),
     * so "removing the payments" means voiding every one of those entries
     * (reversing their GL impact, same as cancel()) before the expense itself
     * is soft-deleted. Never hard-delete a posted entry — void it.
     */
    public function delete(Expense $expense): bool
    {
        return DB::transaction(function () use ($expense) {
            $entries = \Modules\Accounting\Models\JournalEntry::where('source_id', $expense->id)
                ->whereIn('source_type', ['expense', 'expense_payment'])
                ->where('status', 'posted')
                ->get();

            foreach ($entries as $je) {
                try {
                    $this->journalService->void($je, "Expense deleted: {$expense->expense_number}");
                } catch (\Throwable $e) {
                    \Log::warning("Failed to void journal entry for expense delete: {$e->getMessage()}");
                }
            }

            return $expense->delete();
        });
    }

    /**
     * Approve a pending expense.
     */
    public function approve(Expense $expense): Expense
    {
        if ($expense->status !== 'pending') {
            throw new \RuntimeException('Only pending expenses can be approved.');
        }

        $expense->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $expense->fresh();
    }

    /**
     * Reject a pending expense.
     */
    public function reject(Expense $expense, string $reason): Expense
    {
        if ($expense->status !== 'pending') {
            throw new \RuntimeException('Only pending expenses can be rejected.');
        }

        $expense->update([
            'status' => 'rejected',
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $expense->fresh();
    }

    /**
     * Mark an approved expense as paid and create journal entry.
     * Journal Entry: DR Expense Account / CR Payment Account
     *
     * Dated today — the moment the cash actually leaves — not expense_date.
     * This app has no accrual recognition (see filteredQuery()'s docblock):
     * an expense has zero ledger effect until it's paid, so the entry must
     * land on the day it's paid. An expense created or approved days earlier
     * and marked paid today, dated with its (past) expense_date instead,
     * silently misdated real cash movement into a closed prior day —
     * invisible in that day's Today's Expense / Today Money Out.
     */
    public function markPaid(Expense $expense): Expense
    {
        if ($expense->status !== 'approved') {
            throw new \RuntimeException('Only approved expenses can be marked as paid.');
        }

        if (!$expense->payment_account_id) {
            throw new \RuntimeException('Payment account must be set before marking as paid.');
        }

        return DB::transaction(function () use ($expense) {
            // expense.payment_account_id is a Modules\Payment\PaymentAccount id
            // (Cash, bKash, DBBL Bank — the business's own money accounts),
            // not a Chart of Accounts id — it must be resolved to the GL cash/
            // bank/mobile asset account before it can be used as a journal
            // entry line's account_id. recordPayment() already does this via
            // resolveAssetAccount(); this used the raw id directly, silently
            // crediting whatever unrelated GL account happened to share that
            // number instead of the real money account.
            $assetAccountId = $this->resolveAssetAccount($expense->payment_account_id);

            $je = $this->journalService->createFromSource(
                'expense',
                $expense->id,
                [
                    [
                        'account_id' => $expense->account_id,
                        'description' => $expense->description,
                        'debit_amount' => $expense->total_amount,
                        'credit_amount' => 0,
                    ],
                    [
                        'account_id' => $assetAccountId,
                        'description' => "Payment for {$expense->expense_number}",
                        'debit_amount' => 0,
                        'credit_amount' => $expense->total_amount,
                    ],
                ],
                "Expense: {$expense->expense_number} — {$expense->description}",
                $expense->expense_number,
                now()->toDateString()
            );

            $expense->update([
                'status' => 'paid',
                'paid_amount' => $expense->total_amount,
                'due_amount' => 0,
                'payment_status' => 'paid',
                'journal_entry_id' => $je->id,
            ]);

            return $expense->fresh();
        });
    }

    /**
     * Record a partial or full payment against an approved/partial expense.
     */
    public function recordPayment(Expense $expense, array $data): Expense
    {
        if (!in_array($expense->status, ['approved', 'paid']) || $expense->payment_status === 'paid') {
            throw new \RuntimeException('Only approved expenses with outstanding dues can receive payments.');
        }

        $amount = (float) $data['amount'];
        $remaining = (float) $expense->total_amount - (float) $expense->paid_amount;

        if ($amount > $remaining + 0.01) {
            throw new \RuntimeException("Payment amount (" . currency_symbol() . " " . number_format($amount) . ") exceeds outstanding due (" . currency_symbol() . " " . number_format($remaining) . ").");
        }

        $payAmount = min($amount, $remaining);
        $paymentAccountId = $data['payment_account_id'] ?? $expense->payment_account_id;

        return DB::transaction(function () use ($expense, $payAmount, $paymentAccountId, $data) {
            // Create journal entry for this payment: DR Expense / CR Cash
            $expenseAccountId = $expense->account_id;
            $assetAccountId = $this->resolveAssetAccount($paymentAccountId);

            $je = null;
            if ($expenseAccountId && $assetAccountId) {
                try {
                    $je = $this->journalService->createFromSource(
                        'expense_payment',
                        $expense->id,
                        [
                            ['account_id' => $expenseAccountId, 'debit_amount' => $payAmount, 'credit_amount' => 0, 'description' => "Expense payment: {$expense->expense_number}"],
                            ['account_id' => $assetAccountId, 'debit_amount' => 0, 'credit_amount' => $payAmount, 'description' => "Paid for: {$expense->description}"],
                        ],
                        "Expense payment: {$expense->expense_number}",
                        $data['reference'] ?? $expense->expense_number,
                        $data['payment_date'] ?? now()->toDateString(),
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to record expense payment journal: {$e->getMessage()}");
                }
            }

            // Update expense amounts
            $newPaid = (float) $expense->paid_amount + $payAmount;
            $newDue = max(0, (float) $expense->total_amount - $newPaid);
            $paymentStatus = $newDue <= 0 ? 'paid' : 'partial';
            $status = $newDue <= 0 ? 'paid' : $expense->status;

            // Store the latest journal entry ID (the last payment's JE)
            $expense->update([
                'paid_amount'      => $newPaid,
                'due_amount'       => $newDue,
                'payment_status'   => $paymentStatus,
                'status'           => $status,
                'journal_entry_id' => $je?->id ?? $expense->journal_entry_id,
            ]);

            return $expense->fresh();
        });
    }

    /**
     * Resolve asset account ID from payment account.
     */
    private function resolveAssetAccount(?int $paymentAccountId): ?int
    {
        $typeToCode = ['cash' => '1001', 'mobile_banking' => '1002', 'bank' => '1004', 'card' => '1004'];

        if ($paymentAccountId) {
            $type = \Modules\Payment\Models\PaymentAccount::where('id', $paymentAccountId)->value('account_type');
            $code = $typeToCode[$type] ?? '1001';

            return \Modules\Accounting\Models\Account::where('account_code', $code)->value('id')
                ?? \Modules\Accounting\Models\Account::where('account_type', 'asset')->value('id');
        }

        return \Modules\Accounting\Models\Account::where('account_code', '1001')->value('id');
    }

    /**
     * Cancel a paid or approved expense — reverse journal entry and vendor totals.
     */
    public function cancel(Expense $expense): Expense
    {
        if ($expense->status === 'cancelled') {
            throw new \RuntimeException('This expense is already cancelled.');
        }

        return DB::transaction(function () use ($expense) {
            // If paid, void the journal entry
            if ($expense->status === 'paid' && $expense->journal_entry_id) {
                try {
                    $je = \Modules\Accounting\Models\JournalEntry::find($expense->journal_entry_id);
                    if ($je && $je->status === 'posted') {
                        $this->journalService->void($je, "Expense cancelled: {$expense->expense_number}");
                    }
                } catch (\Throwable $e) {
                    \Log::warning("Failed to void journal entry for expense cancel: {$e->getMessage()}");
                }
            }

            $expense->update([
                'status' => 'cancelled',
                'paid_amount' => 0,
                'due_amount' => 0,
                'payment_status' => 'unpaid',
            ]);

            return $expense->fresh();
        });
    }

    /**
     * Stats for the expense index page.
     */
    public function getStats(array $filters = []): array
    {
        // These summary cards always reflect the real periods (and the global
        // pending count); they are intentionally independent of the list
        // filters, so applying a date/category/status filter never changes them.
        $now = now();

        return [
            'today'         => (float) Expense::whereDate('expense_date', today())->sum('total_amount'),
            'this_week'     => (float) Expense::whereBetween('expense_date', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])->sum('total_amount'),
            'this_month'    => (float) Expense::whereMonth('expense_date', $now->month)->whereYear('expense_date', $now->year)->sum('total_amount'),
            'pending_count' => Expense::where('status', 'pending')->count(),
        ];
    }

    /**
     * Expense ledger with optional filters.
     */
    public function getLedger(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return Expense::with(['category', 'account', 'creator', 'approver', 'branch'])
            ->where('status', '!=', 'rejected')
            ->when($filters['category'] ?? null, fn ($q, $c) => $q->byCategory((int) $c))
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->where('expense_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->where('expense_date', '<=', $d))
            ->when($filters['branch'] ?? null, fn ($q, $b) => $q->where('branch_id', $b))
            ->latest('expense_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Stats for the expense ledger page: total, top categories, monthly average, period label.
     */
    public function getLedgerStats(array $filters = []): array
    {
        $baseQuery = Expense::where('status', '!=', 'rejected')
            ->when($filters['category'] ?? null, fn ($q, $c) => $q->byCategory((int) $c))
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->where('expense_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->where('expense_date', '<=', $d))
            ->when($filters['branch'] ?? null, fn ($q, $b) => $q->where('branch_id', $b));

        $totalExpenses = (float) (clone $baseQuery)->sum('total_amount');

        // Top 3 categories by total amount
        $topCategories = (clone $baseQuery)
            ->select('expense_category_id', DB::raw('SUM(total_amount) as category_total'))
            ->groupBy('expense_category_id')
            ->orderByDesc('category_total')
            ->limit(3)
            ->with('category')
            ->get()
            ->map(fn ($row) => $row->category
                ? $row->category->name . ': ' . currency_symbol() . ' ' . number_format($row->category_total, 0)
                : 'Uncategorized: ' . currency_symbol() . ' ' . number_format($row->category_total, 0)
            )
            ->implode(' | ');

        // Monthly average based on distinct months with expenses
        $monthCount = (clone $baseQuery)
            ->selectRaw('COUNT(DISTINCT DATE_FORMAT(expense_date, "%Y-%m")) as month_count')
            ->value('month_count');
        $monthlyAverage = $monthCount > 0 ? $totalExpenses / $monthCount : 0;

        // Period label
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        if ($dateFrom && $dateTo) {
            $periodLabel = \Carbon\Carbon::parse($dateFrom)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($dateTo)->format('d M Y');
        } elseif ($dateFrom) {
            $periodLabel = 'From ' . \Carbon\Carbon::parse($dateFrom)->format('d M Y');
        } elseif ($dateTo) {
            $periodLabel = 'Up to ' . \Carbon\Carbon::parse($dateTo)->format('d M Y');
        } else {
            $periodLabel = 'All Time';
        }

        return [
            'total_expenses' => $totalExpenses,
            'top_categories' => $topCategories ?: 'N/A',
            'monthly_average' => $monthlyAverage,
            'period_label' => $periodLabel,
        ];
    }

    /**
     * Generate the next expense number: EXP-{YEAR}-{PADDED_SEQ}
     */
    private function generateNumber(): string
    {
        $year = now()->format('Y');
        $last = Expense::withTrashed()
            ->whereYear('created_at', $year)
            ->count();

        return 'EXP-' . $year . '-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

}
