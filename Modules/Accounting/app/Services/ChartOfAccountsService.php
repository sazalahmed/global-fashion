<?php

namespace Modules\Accounting\Services;

use Modules\Accounting\Models\Account;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ChartOfAccountsService
{
    /**
     * Paginated account list with filters.
     */
    public function list(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        return Account::with(['parent', 'children'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['account_type'] ?? null, fn ($q, $t) => $q->byType($t))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('account_code')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Accounts grouped by type for dropdown selects.
     */
    public function getAccountsGroupedByType(): Collection
    {
        return Account::active()
            ->orderBy('account_code')
            ->get()
            ->groupBy('account_type');
    }

    /**
     * Accounts nested: type → sub_type → accounts.
     * Used by chart-of-accounts index view for collapsible tree.
     */
    public function getAccountsHierarchy(?array $filters = []): array
    {
        $query = Account::with(['parent', 'children'])
            ->orderBy('account_code');

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }
        if (!empty($filters['account_type'])) {
            $query->byType($filters['account_type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $accounts = $query->get();

        $hierarchy = [];
        foreach ($accounts as $account) {
            $hierarchy[$account->account_type][$account->sub_type][] = $account;
        }

        return $hierarchy;
    }

    /**
     * Find a single account.
     */
    public function find(int $id): Account
    {
        return Account::with(['parent', 'children'])->findOrFail($id);
    }

    /**
     * Create a new account.
     */
    public function create(array $data): Account
    {
        $data['created_by'] = auth()->id();

        return Account::create($data);
    }

    /**
     * Update an existing account.
     */
    public function update(Account $account, array $data): Account
    {
        $account->update($data);

        return $account->fresh();
    }

    /**
     * Toggle an account's active/inactive status.
     */
    public function toggleStatus(Account $account): Account
    {
        $account->update(['status' => $account->status === 'active' ? 'inactive' : 'active']);

        return $account->fresh();
    }

    /**
     * Delete an account (with safety checks).
     */
    public function delete(Account $account): bool
    {
        if ($account->is_system) {
            throw new \RuntimeException('Cannot delete a system account.');
        }

        if ($account->journalEntryLines()->exists()) {
            throw new \RuntimeException('Cannot delete an account that has transactions.');
        }

        if ($account->children()->exists()) {
            throw new \RuntimeException('Cannot delete an account that has sub-accounts.');
        }

        return $account->delete();
    }

    /**
     * Stats for the chart of accounts index page.
     */
    public function getStats(): array
    {
        $accounts = Account::active()->get();

        $totalByType = [];
        foreach (['asset', 'liability', 'equity', 'revenue', 'expense'] as $type) {
            $totalByType[$type] = $accounts->where('account_type', $type)
                ->sum(fn ($a) => $a->balance);
        }

        return [
            'total_accounts' => $accounts->count(),
            'total_assets' => $totalByType['asset'],
            'total_liabilities' => $totalByType['liability'],
            'total_equity' => $totalByType['equity'],
            'total_revenue' => $totalByType['revenue'],
            'total_expenses' => $totalByType['expense'],
        ];
    }

    /**
     * Compute balance for an account as of a specific date.
     */
    public function computeBalanceAsOf(Account $account, $asOfDate = null): float
    {
        $query = $account->journalEntryLines()
            ->whereHas('journalEntry', function ($q) use ($asOfDate) {
                $q->postedEffective();
                if ($asOfDate) {
                    $q->where('entry_date', '<=', $asOfDate);
                }
            });

        $lineBalance = $query->selectRaw(
            'COALESCE(SUM(debit_amount), 0) as total_debit, COALESCE(SUM(credit_amount), 0) as total_credit'
        )->first();

        $totalDebit = (float) ($lineBalance->total_debit ?? 0);
        $totalCredit = (float) ($lineBalance->total_credit ?? 0);

        $netMovement = $account->isDebitNormal()
            ? ($totalDebit - $totalCredit)
            : ($totalCredit - $totalDebit);

        $openingSign = ($account->opening_balance_type === 'debit' && $account->isDebitNormal())
            || ($account->opening_balance_type === 'credit' && !$account->isDebitNormal())
            ? 1 : -1;

        return ((float) $account->opening_balance * $openingSign) + $netMovement;
    }
}
