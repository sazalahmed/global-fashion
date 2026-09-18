<?php

namespace Modules\Accounting\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\BankReconciliation;
use Modules\Accounting\Models\BankReconciliationItem;
use Modules\Accounting\Models\JournalEntryLine;

class BankReconciliationService
{
    public function __construct(
        private readonly ChartOfAccountsService $accountService,
    ) {}

    // ── List ──

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return BankReconciliation::with(['account', 'reconciledByUser'])
            ->when($filters['account_id'] ?? null, fn ($q, $id) => $q->where('account_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->latest('statement_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): BankReconciliation
    {
        return BankReconciliation::with(['account', 'items.journalEntryLine.journalEntry', 'reconciledByUser'])
            ->findOrFail($id);
    }

    // ── Start Reconciliation ──

    public function start(int $accountId, string $statementDate, float $statementBalance): BankReconciliation
    {
        $account = Account::findOrFail($accountId);
        $bookBalance = $this->accountService->computeBalanceAsOf($account, \Carbon\Carbon::parse($statementDate));

        return DB::transaction(function () use ($accountId, $statementDate, $statementBalance, $bookBalance) {
            $reconciliation = BankReconciliation::create([
                'account_id'        => $accountId,
                'statement_date'    => $statementDate,
                'statement_balance' => $statementBalance,
                'book_balance'      => $bookBalance,
                'status'            => 'in_progress',
            ]);

            // Load book transactions (journal entry lines for this account)
            $bookTransactions = JournalEntryLine::where('account_id', $accountId)
                ->whereHas('journalEntry', fn ($q) => $q->where('status', 'posted')
                    ->where('entry_date', '<=', $statementDate))
                ->with('journalEntry')
                ->get();

            foreach ($bookTransactions as $line) {
                $amount = $line->debit_amount - $line->credit_amount;

                $reconciliation->items()->create([
                    'journal_entry_line_id' => $line->id,
                    'type'                  => 'book_transaction',
                    'transaction_date'      => $line->journalEntry->entry_date,
                    'description'           => $line->description ?? $line->journalEntry->description,
                    'reference'             => $line->journalEntry->reference,
                    'amount'                => $amount,
                    'is_reconciled'         => false,
                ]);
            }

            return $reconciliation->load('items');
        });
    }

    // ── Import Bank Statement (CSV) ──

    public function importStatement(BankReconciliation $reconciliation, array $rows): int
    {
        $count = 0;

        foreach ($rows as $row) {
            $reconciliation->items()->create([
                'type'             => 'bank_statement',
                'transaction_date' => $row['date'],
                'description'      => $row['description'],
                'reference'        => $row['reference'] ?? null,
                'amount'           => $row['amount'],
                'is_reconciled'    => false,
            ]);
            $count++;
        }

        return $count;
    }

    // ── Match Items ──

    public function matchItems(int $bookItemId, int $statementItemId): void
    {
        $bookItem = BankReconciliationItem::findOrFail($bookItemId);
        $statementItem = BankReconciliationItem::findOrFail($statementItemId);

        DB::transaction(function () use ($bookItem, $statementItem) {
            $bookItem->update([
                'is_reconciled'  => true,
                'matched_item_id' => $statementItem->id,
            ]);
            $statementItem->update([
                'is_reconciled'  => true,
                'matched_item_id' => $bookItem->id,
            ]);
        });
    }

    // ── Unmatch Items ──

    public function unmatchItems(int $itemId): void
    {
        $item = BankReconciliationItem::findOrFail($itemId);

        DB::transaction(function () use ($item) {
            if ($item->matched_item_id) {
                BankReconciliationItem::where('id', $item->matched_item_id)
                    ->update(['is_reconciled' => false, 'matched_item_id' => null]);
            }
            $item->update(['is_reconciled' => false, 'matched_item_id' => null]);
        });
    }

    // ── Complete Reconciliation ──

    public function complete(BankReconciliation $reconciliation): BankReconciliation
    {
        $unreconciledBook = $reconciliation->items()->bookTransactions()->unreconciled()->sum('amount');
        $unreconciledStatement = $reconciliation->items()->bankStatement()->unreconciled()->sum('amount');

        $adjustedBalance = $reconciliation->book_balance + $unreconciledBook;

        $reconciliation->update([
            'status'           => 'completed',
            'adjusted_balance' => $adjustedBalance,
            'reconciled_by'    => auth()->id(),
            'reconciled_at'    => now(),
        ]);

        return $reconciliation->fresh();
    }

    // ── Stats ──

    public function getSummary(BankReconciliation $reconciliation): array
    {
        $items = $reconciliation->items;

        return [
            'book_balance'           => $reconciliation->book_balance,
            'statement_balance'      => $reconciliation->statement_balance,
            'difference'             => $reconciliation->difference,
            'total_book_items'       => $items->where('type', 'book_transaction')->count(),
            'total_statement_items'  => $items->where('type', 'bank_statement')->count(),
            'reconciled_count'       => $items->where('is_reconciled', true)->count(),
            'unreconciled_count'     => $items->where('is_reconciled', false)->count(),
            'unreconciled_book_total'      => $items->where('type', 'book_transaction')->where('is_reconciled', false)->sum('amount'),
            'unreconciled_statement_total' => $items->where('type', 'bank_statement')->where('is_reconciled', false)->sum('amount'),
        ];
    }

    // ── Bank Accounts ──

    public function getBankAccounts(): Collection
    {
        return Account::active()->bankAccounts()->orderBy('account_code')->get();
    }
}
