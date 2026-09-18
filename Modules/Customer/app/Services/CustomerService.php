<?php

namespace Modules\Customer\Services;

use App\Helpers\Upload;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Models\Customer;
use Modules\Sale\Models\Sale;
use Modules\Payment\Models\Payment;

class CustomerService
{
    /**
     * Sale statuses hidden from customer-facing purchase/due figures (the
     * list columns, list totals row, and profile stat cards): cancelled
     * sales and placeholder records. Everything else — pending, confirmed,
     * delivered, etc. — counts, matching the sales each customer's Sales
     * History tab actually lists. This intentionally differs from the
     * accounting recognition rule (Customer::PURCHASED_STATUSES — delivered
     * only), which still governs stored totals and reports.
     */
    public const HIDDEN_SALE_STATUSES = ['cancelled', 'incompleted', 'draft'];

    // ── List ──

    /**
     * Get paginated list of customers with optional filters. Each row carries
     * `visible_total` / `visible_due` sums (see HIDDEN_SALE_STATUSES).
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)
            ->with(['branch', 'creator', 'customerGroup'])
            ->withSum(['sales as visible_total' => fn ($q) => $q->whereNotIn('status', self::HIDDEN_SALE_STATUSES)], 'grand_total')
            ->withSum(['sales as visible_due' => fn ($q) => $q->whereNotIn('status', self::HIDDEN_SALE_STATUSES)], 'due_amount')
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Purchase/due totals over ALL customers matching the current filters
     * (not just the visible page) — rendered as the list's totals row.
     */
    public function getListTotals(array $filters = []): array
    {
        $row = Sale::whereIn('customer_id', $this->filteredQuery($filters)->select('id'))
            ->whereNotIn('status', self::HIDDEN_SALE_STATUSES)
            ->selectRaw('COALESCE(SUM(grand_total), 0) as total_purchased, COALESCE(SUM(due_amount), 0) as due_amount')
            ->first();

        return [
            'total_purchased' => (float) $row->total_purchased,
            'due_amount'      => (float) $row->due_amount,
        ];
    }

    /**
     * Base customer query with the list page's filters applied — shared by
     * list() and getListTotals() so the totals always match the rows.
     */
    private function filteredQuery(array $filters)
    {
        return Customer::query()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['customer_group_id'] ?? null, fn ($q, $g) => $q->byGroup((int) $g))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['branch_id'] ?? null, fn ($q, $b) => $q->byBranch($b));
    }

    // ── Find ──

    /**
     * Find a single customer by ID with eager-loaded relations.
     */
    public function find(int $id): Customer
    {
        return Customer::with(['branch', 'creator'])->findOrFail($id);
    }

    // ── Create ──

    /**
     * Create a new customer with optional photo upload.
     */
    public function create(array $data): Customer
    {
        if (isset($data['photo']) && $data['photo']->isValid()) {
            $data['photo'] = Upload::store($data['photo'], 'customers');
        }

        $data['created_by'] = auth()->id();
        $data['branch_id'] = $data['branch_id'] ?? auth()->user()->branch_id ?? null;

        return Customer::create($data);
    }

    // ── Update ──

    /**
     * Update an existing customer, replacing photo if a new one is uploaded.
     */
    public function update(Customer $customer, array $data): Customer
    {
        if (isset($data['photo']) && $data['photo']->isValid()) {
            if ($customer->photo) {
                Upload::delete($customer->photo);
            }

            $data['photo'] = Upload::store($data['photo'], 'customers');
        }

        $customer->update($data);

        return $customer->fresh();
    }

    // ── Delete ──

    /**
     * Soft delete a customer.
     */
    public function delete(Customer $customer): bool
    {
        // Prevent deletion if customer has outstanding dues
        $dueBalance = (float) $customer->due_amount;
        if ($dueBalance > 0) {
            throw new \RuntimeException("Cannot delete customer — outstanding due balance of " . currency_symbol() . " " . number_format($dueBalance) . " exists.");
        }

        // Prevent deletion if active (non-cancelled) sales exist
        $activeSales = $customer->sales()
            ->whereNotIn('status', ['cancelled'])
            ->count();

        if ($activeSales > 0) {
            throw new \RuntimeException("Cannot delete customer — {$activeSales} active sale(s) exist.");
        }

        return $customer->delete();
    }

    // ── Toggle Status ──

    /**
     * Flip a customer's active status.
     */
    public function toggleStatus(Customer $customer): Customer
    {
        $customer->update(['is_active' => ! $customer->is_active]);

        return $customer;
    }

    // ── Search (AJAX / POS) ──

    /**
     * Lightweight search for POS and AJAX dropdowns.
     */
    public function search(string $term, int $limit = 15): Collection
    {
        return Customer::active()
            ->search($term)
            ->limit($limit)
            ->get(['id', 'name', 'phone', 'total_purchased']);
    }

    // ── Quick Create (POS) ──

    /**
     * Minimal customer creation from POS: name + phone + optional group.
     */
    public function quickCreate(array $data): Customer
    {
        return Customer::create([
            'name'           => $data['name'],
            'phone'          => $data['phone'],
            'customer_group_id' => $data['customer_group_id'] ?? null,
            'created_by'     => auth()->id(),
            'branch_id'      => auth()->user()->branch_id ?? null,
        ]);
    }

    // ── Stats ──

    /**
     * Get aggregate statistics for the customer index page.
     */
    public function getStats(): array
    {
        return [
            'total'           => Customer::count(),
            'active'          => Customer::active()->count(),
            'new_this_month'  => Customer::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'total_receivable' => (float) Sale::whereNotIn('status', self::HIDDEN_SALE_STATUSES)
                ->whereHas('customer', fn ($q) => $q->where('is_active', true))
                ->sum('due_amount'),
        ];
    }

    // ── Balance Recalculation ──

    /**
     * Recalculate a customer's total_purchased from related sales.
     * total_paid + due are derived live from sales — no denormalized counter.
     * Same recognition rule as SaleService::recomputeCustomerPurchased —
     * only delivered sales count (Customer::PURCHASED_STATUSES).
     */
    public function updateBalance(Customer $customer): void
    {
        $customer->update([
            'total_purchased' => $customer->sales()->whereIn('status', Customer::PURCHASED_STATUSES)->sum('grand_total'),
        ]);
    }

    // ── Due Receive ──

    /**
     * Get paginated customers with outstanding due balances.
     */
    public function getDueReceiveList(array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        // Receivables recognition: only DELIVERED sales are a realised due
        // (Customer::PURCHASED_STATUSES) — the same rule the displayed "Due
        // Amount" column (Customer::getDueAmountAttribute) already uses. This
        // keeps the filter and the shown figure in agreement, and ensures
        // cancelled / incompleted / pending / confirmed orders never create a
        // phantom due row (they remain visible only in the customer ledger).
        $dueSub = Sale::selectRaw('customer_id, SUM(due_amount) as due_balance')
            ->whereIn('status', Customer::PURCHASED_STATUSES)
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->havingRaw('SUM(due_amount) > 0');

        // The joinSub already exposes a single `due_balance` column (and filters
        // to customers with due > 0 via its HAVING clause), so select/order by
        // that directly. The previous correlated selectSub returned multiple
        // columns and triggered "Cardinality violation: Operand should contain 1 column".
        return Customer::select('customers.*')
            ->addSelect('sales_due.due_balance')
            ->joinSub($dueSub, 'sales_due', fn ($j) => $j->on('sales_due.customer_id', '=', 'customers.id'))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['customer_group_id'] ?? null, fn ($q, $g) => $q->byGroup((int) $g))
            ->orderByDesc('sales_due.due_balance')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Map of [customer_id => outstanding due] across the customer's ACTIVE
     * orders (every status except HIDDEN_SALE_STATUSES). Used by the Order and
     * Quotation forms to surface a customer's prior balance when creating or
     * editing a transaction. Intentionally broader than the delivered-only
     * receivables rule on the Due Receive page: here the salesperson wants the
     * full picture of what the customer owes across all open orders.
     *
     * @param  int|null  $excludeSaleId  Sale to omit (the one being edited) so
     *                                    its own due is not counted as "previous".
     * @return array<int, float>
     */
    public function getActiveDueMap(?int $excludeSaleId = null): array
    {
        return Sale::query()
            ->whereNotIn('status', self::HIDDEN_SALE_STATUSES)
            ->whereNotNull('customer_id')
            ->when($excludeSaleId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->groupBy('customer_id')
            ->selectRaw('customer_id, SUM(due_amount) as due')
            ->pluck('due', 'customer_id')
            ->map(fn ($d) => (float) $d)
            ->toArray();
    }

    /**
     * Get total outstanding due amount across all customers.
     */
    public function getTotalDue(): float
    {
        // Match getDueReceiveList: only realised (delivered) dues are counted so
        // this card equals the sum of the table's Due Amount column.
        return (float) Sale::whereIn('status', Customer::PURCHASED_STATUSES)
            ->whereNotNull('customer_id')
            ->sum('due_amount');
    }

    /**
     * Column grand totals (purchased / paid / due) for the Due Receive table
     * footer. Sums the delivered sales of the SAME customer set the list shows
     * (those with an outstanding delivered due), honouring the search/group
     * filters — so the footer reflects every matching row, not just the page.
     *
     * @return array{purchased: float, paid: float, due: float}
     */
    public function getDueReceiveTotals(array $filters = []): array
    {
        $dueCustomerIds = Sale::whereIn('status', Customer::PURCHASED_STATUSES)
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->havingRaw('SUM(due_amount) > 0')
            ->pluck('customer_id');

        // Apply the same search/group filters the list uses, so a filtered
        // table gets a matching footer.
        $customerIds = Customer::whereIn('id', $dueCustomerIds)
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['customer_group_id'] ?? null, fn ($q, $g) => $q->byGroup((int) $g))
            ->pluck('id');

        $row = Sale::whereIn('status', Customer::PURCHASED_STATUSES)
            ->whereIn('customer_id', $customerIds)
            ->selectRaw('COALESCE(SUM(grand_total), 0) AS purchased,
                         COALESCE(SUM(paid_amount), 0) AS paid,
                         COALESCE(SUM(due_amount), 0) AS due')
            ->first();

        return [
            'purchased' => (float) $row->purchased,
            'paid'      => (float) $row->paid,
            'due'       => (float) $row->due,
        ];
    }

    // ── Customer Advances ──

    /**
     * Get customer advance balances (received, returned, net balance).
     */
    public function getCustomerAdvances(array $filters = []): array
    {
        $query = Payment::where('party_type', 'customer')
            ->whereIn('payment_type', ['advance_payment', 'advance_return'])
            ->select(
                'party_id',
                DB::raw("SUM(CASE WHEN payment_type = 'advance_payment' THEN amount ELSE 0 END) as total_received"),
                DB::raw("SUM(CASE WHEN payment_type = 'advance_return' THEN amount ELSE 0 END) as total_returned"),
            )
            ->groupBy('party_id')
            ->havingRaw("SUM(CASE WHEN payment_type = 'advance_payment' THEN amount ELSE 0 END)
                       - SUM(CASE WHEN payment_type = 'advance_return' THEN amount ELSE 0 END) > 0")
            ->get();

        $customerIds = $query->pluck('party_id')->toArray();
        $customersMap = Customer::whereIn('id', $customerIds)
            ->get(['id', 'name', 'phone'])
            ->keyBy('id');

        if (!empty($filters['search'])) {
            $search = mb_strtolower($filters['search']);
            $query = $query->filter(function ($item) use ($customersMap, $search) {
                $customer = $customersMap[$item->party_id] ?? null;
                if (!$customer) {
                    return false;
                }
                return str_contains(mb_strtolower($customer->name), $search)
                    || str_contains(mb_strtolower($customer->phone ?? ''), $search);
            })->values();
        }

        $totalBalance = $query->sum(fn ($a) => $a->total_received - $a->total_returned);

        return [
            'items' => $query,
            'customers' => $customersMap,
            'total_balance' => $totalBalance,
        ];
    }

    // ── Ledger ──

    /**
     * Get customer ledger entries (sales as debit, payments as credit).
     */
    // ── Profile stat cards ──

    /**
     * Purchase/paid/due totals for the profile header cards, summed over the
     * same sales the profile's Sales History tab lists (see
     * HIDDEN_SALE_STATUSES), so the cards always agree with the table below.
     */
    public function getProfileStats(int $customerId): array
    {
        $row = Sale::where('customer_id', $customerId)
            ->whereNotIn('status', self::HIDDEN_SALE_STATUSES)
            ->selectRaw('
                COALESCE(SUM(grand_total), 0) as total_purchased,
                COALESCE(SUM(paid_amount), 0) as total_paid,
                COALESCE(SUM(due_amount), 0) as due_amount
            ')
            ->first();

        return [
            'total_purchased' => (float) $row->total_purchased,
            'total_paid'      => (float) $row->total_paid,
            'due_amount'      => (float) $row->due_amount,
        ];
    }

    // ── Guest checkout resolution ──

    /**
     * Find a customer by phone (format-tolerant) or create one from checkout
     * contact details. Guest website orders get a CRM record on their first
     * purchase; repeat guests are matched back to the same record so no
     * duplicate customers are created. Returns null when no phone was given.
     */
    public function findOrCreateByPhone(array $contact): ?Customer
    {
        $phone = trim((string) ($contact['phone'] ?? ''));
        if ($phone === '') {
            return null;
        }

        // Match exact first, then digits-only so "01712-345678" and
        // "01712345678" resolve to the same customer. withTrashed(): the
        // phone column is UNIQUE across trashed rows, so a soft-deleted
        // match must be restored rather than duplicated.
        $digits = preg_replace('/\D+/', '', $phone);
        $customer = Customer::withTrashed()
            ->where(function ($q) use ($phone, $digits) {
                $q->where('phone', $phone)
                    ->orWhereRaw("REGEXP_REPLACE(phone, '[^0-9]+', '') = ?", [$digits]);
            })
            ->orderByRaw('phone = ? desc', [$phone])
            ->first();

        if ($customer) {
            if ($customer->trashed()) {
                $customer->restore();
            }

            return $customer;
        }

        // Email is also UNIQUE — only store it if it isn't already claimed.
        $email = trim((string) ($contact['email'] ?? '')) ?: null;
        if ($email && Customer::withTrashed()->where('email', $email)->exists()) {
            $email = null;
        }

        return Customer::create([
            'name'      => trim((string) ($contact['name'] ?? '')) ?: 'Website Customer',
            'phone'     => $phone,
            'email'     => $email,
            'address'   => $contact['address'] ?? null,
            'is_active' => true,
        ]);
    }

    // ── Profile tabs (paginated, served over AJAX) ──

    /**
     * Paginated sales history for the customer profile tab.
     */
    public function getSalesHistory(int $customerId, int $perPage = 15): LengthAwarePaginator
    {
        return Sale::where('customer_id', $customerId)
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Paginated payment history for the customer profile tab.
     */
    public function getPaymentHistory(int $customerId, int $perPage = 15): LengthAwarePaginator
    {
        return Payment::where('party_type', 'customer')
            ->where('party_id', $customerId)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Paginated advance transactions (received/returned) for the profile tab.
     */
    public function getAdvanceTransactions(int $customerId, int $perPage = 15): LengthAwarePaginator
    {
        return Payment::where('party_type', 'customer')
            ->where('party_id', $customerId)
            ->whereIn('payment_type', ['advance_payment', 'advance_return'])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Advance totals for the profile tab stat cards — computed over ALL
     * transactions (not just the current page).
     */
    public function getAdvanceStats(int $customerId): array
    {
        $row = Payment::where('party_type', 'customer')
            ->where('party_id', $customerId)
            ->whereIn('payment_type', ['advance_payment', 'advance_return'])
            ->selectRaw("
                COALESCE(SUM(CASE WHEN payment_type = 'advance_payment' THEN amount ELSE 0 END), 0) as total_received,
                COALESCE(SUM(CASE WHEN payment_type = 'advance_return' THEN amount ELSE 0 END), 0) as total_returned
            ")
            ->first();

        return [
            'total_received' => (float) $row->total_received,
            'total_returned' => (float) $row->total_returned,
            'balance'        => (float) $row->total_received - (float) $row->total_returned,
        ];
    }

    /**
     * Ledger for the profile tab, paginated. The running balance requires the
     * full chronological history, so the ledger is computed in full and the
     * requested page sliced out (totals always reflect the whole ledger).
     */
    /**
     * The ledger as a paginated statement. Totals and the running balance are
     * computed over every entry first, so the visible page shows real balances
     * rather than restarting from zero.
     */
    public function getLedgerPaginated(int $customerId, int $perPage = 15, ?int $page = null, array $filters = []): array
    {
        // Filters have to reach getLedger, or a date-filtered statement would
        // silently paginate the customer's entire history instead.
        $ledger = $this->getLedger($customerId, $filters);
        $page = $page ?: \Illuminate\Pagination\Paginator::resolveCurrentPage();

        $all = collect($ledger['entries']);
        $ledger['entries'] = new \Illuminate\Pagination\LengthAwarePaginator(
            $all->forPage($page, $perPage)->values(),
            $all->count(),
            $perPage,
            $page,
            // Keep customer_id and the date range on the page links, otherwise
            // page 2 resets to an empty statement.
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => request()->query()]
        );

        return $ledger;
    }

    /**
     * SQL naming the account a receipt landed in, followed by its type.
     *
     * Several accounts can share a name — the same person's bank account and
     * mobile wallet — so the name alone does not say where the money went.
     * The type is left off when the name already is the type, which keeps
     * the default cash account reading "Cash" rather than "Cash — Cash".
     * Falls back to the denormalised payment_method when no account is set.
     */
    private function paymentAccountLabelSql(): string
    {
        return "CASE
            WHEN payment_accounts.id IS NULL OR payment_accounts.name = ''
                THEN REPLACE(payments.payment_method, '_', ' ')
            WHEN LOWER(payment_accounts.name) = LOWER(REPLACE(payment_accounts.account_type, '_', ' '))
                THEN payment_accounts.name
            ELSE CONCAT(payment_accounts.name, ' — ', CASE payment_accounts.account_type
                WHEN 'cash' THEN 'Cash'
                WHEN 'mobile_banking' THEN 'Mobile Banking'
                WHEN 'bank' THEN 'Bank'
                WHEN 'card' THEN 'Card'
                ELSE REPLACE(payment_accounts.account_type, '_', ' ')
            END)
        END";
    }

    public function getLedger(int $customerId, array $filters = []): array
    {
        $customer = Customer::findOrFail($customerId);

        // Sales as debit entries
        $salesQuery = Sale::where('customer_id', $customerId)
            ->whereNotIn('status', self::HIDDEN_SALE_STATUSES)
            ->select(
                'id as source_id',
                'sale_date as date',
                'invoice_number as reference',
                DB::raw("'sale' as source_type"),
                DB::raw("'Sale' as type"),
                DB::raw('grand_total as debit'),
                DB::raw('0 as credit'),
                DB::raw("CONCAT('Sale Invoice: ', invoice_number) as description")
            );

        if (!empty($filters['date_from'])) {
            $salesQuery->where('sale_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $salesQuery->where('sale_date', '<=', $filters['date_to']);
        }

        // ALL payments as credit entries (sale_payment, advance_payment, advance_return)
        // Name the receipt by the account it actually landed in. payment_method
        // is a denormalised label that defaulted to 'Cash' for any form posting
        // only payment_account_id, so bank receipts read as cash here.
        $paymentsQuery = Payment::where('party_type', 'customer')
            ->where('party_id', $customerId)
            ->leftJoin('payment_accounts', 'payment_accounts.id', '=', 'payments.payment_account_id')
            ->select(
                'payments.id as source_id',
                'payments.payment_date as date',
                'payments.payment_number as reference',
                DB::raw("'payment' as source_type"),
                DB::raw("CASE
                    WHEN payments.payment_type = 'advance_payment' THEN 'Advance Received'
                    WHEN payments.payment_type = 'advance_return' THEN 'Advance Adjusted'
                    ELSE 'Payment'
                END as type"),
                DB::raw("CASE WHEN payments.payment_type = 'advance_return' THEN payments.amount ELSE 0 END as debit"),
                // A due-collection discount reduces what the customer owes
                // exactly like cash does — fold it into the credit so the
                // running balance below reconciles with Sale.due_amount
                // (which already subtracts it). Leaving discount_amount out
                // here would make the ledger's closing balance permanently
                // overstate the due by however much was ever written off.
                DB::raw("CASE WHEN payments.payment_type != 'advance_return' THEN payments.amount + payments.discount_amount ELSE 0 END as credit"),
                DB::raw("CASE
                    WHEN payments.payment_type = 'advance_payment'
                        THEN CONCAT('Advance Received (', " . $this->paymentAccountLabelSql() . ", ')')
                    WHEN payments.payment_type = 'advance_return'
                        THEN CONCAT('Advance Adjusted Against Sale: ', COALESCE(payments.reference, ''))
                    WHEN payments.discount_amount > 0
                        THEN CONCAT('Payment Received (', " . $this->paymentAccountLabelSql() . ", ') + ', ROUND(payments.discount_amount), ' discount written off')
                    ELSE CONCAT('Payment Received (', " . $this->paymentAccountLabelSql() . ", ')')
                END as description")
            );

        if (!empty($filters['date_from'])) {
            $paymentsQuery->where('payments.payment_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $paymentsQuery->where('payments.payment_date', '<=', $filters['date_to']);
        }

        // Combine and sort by date. Use concat() (not merge()): the aliased
        // SELECTs omit the plain `id` column (id is aliased to source_id), so
        // every model's key is null and Eloquent merge()'s key-based dedupe
        // would collapse the rows (entries vanished). concat() appends safely.
        $entries = $salesQuery->get()
            ->concat($paymentsQuery->get())
            ->sortBy('date')
            ->values();

        $balance = 0;
        $totalDebit = 0;
        $totalCredit = 0;
        $ledgerEntries = [];

        foreach ($entries as $entry) {
            $balance = $balance + (float) $entry->debit - (float) $entry->credit;
            $totalDebit += (float) $entry->debit;
            $totalCredit += (float) $entry->credit;

            $entry->balance = $balance;
            $ledgerEntries[] = $entry;
        }

        // Display newest-first by default — a statement is read back from the
        // latest activity. The running balance above is always accumulated
        // oldest-first, so each row keeps the balance as at its own date and
        // the top row carries the closing balance.
        if (strtolower($filters['direction'] ?? 'desc') === 'desc') {
            $ledgerEntries = array_reverse($ledgerEntries);
        }

        return [
            'entries' => $ledgerEntries,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'currentBalance' => $balance,
            'openingBalance' => 0,
            'customerName' => $customer->name,
        ];
    }

    /**
     * Offset customer due balances using their advance balance (FIFO).
     */
    public function offsetDueWithAdvance(Customer $customer): array
    {
        $advanceBalance = (float) Payment::where('party_type', 'customer')
            ->where('party_id', $customer->id)
            ->whereIn('payment_type', ['advance_payment', 'advance_return'])
            ->selectRaw("
                COALESCE(SUM(CASE WHEN payment_type = 'advance_payment' THEN amount ELSE 0 END), 0)
                - COALESCE(SUM(CASE WHEN payment_type = 'advance_return' THEN amount ELSE 0 END), 0)
                as balance
            ")
            ->value('balance');

        if ($advanceBalance <= 0) {
            return ['applied' => 0, 'message' => 'No advance balance available.'];
        }

        $unpaidSales = Sale::where('customer_id', $customer->id)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereIn('status', ['confirmed', 'delivered'])
            ->orderBy('sale_date')
            ->get();

        if ($unpaidSales->isEmpty()) {
            return ['applied' => 0, 'message' => 'No outstanding dues to offset.'];
        }

        $totalApplied = 0;
        $remaining = $advanceBalance;

        DB::transaction(function () use ($unpaidSales, &$remaining, &$totalApplied, $customer) {
            $paymentService = app(\Modules\Payment\Services\PaymentService::class);

            foreach ($unpaidSales as $sale) {
                if ($remaining <= 0) {
                    break;
                }

                $dueAmount = (float) $sale->due_amount;
                $applyAmount = min($remaining, $dueAmount);

                // Create payment record
                $paymentService->create([
                    'direction'      => 'receive',
                    'party_type'     => 'customer',
                    'party_id'       => $customer->id,
                    'payment_type'   => 'advance_return',
                    'amount'         => $applyAmount,
                    'payment_method' => 'cash',
                    'payment_date'   => now()->toDateString(),
                    'reference'      => $sale->invoice_number,
                    'note'           => 'Auto-offset from advance balance',
                ], [
                    [
                        'allocatable_type' => Sale::class,
                        'allocatable_id'   => $sale->id,
                        'amount'           => $applyAmount,
                    ],
                ]);

                // Update sale
                $sale->paid_amount += $applyAmount;
                $sale->due_amount = max(0, $sale->grand_total - $sale->paid_amount - (float) $sale->due_discount_amount);
                $sale->payment_status = $sale->due_amount <= 0 ? 'paid' : 'partial';
                $sale->save();

                $remaining -= $applyAmount;
                $totalApplied += $applyAmount;
            }
        });

        return [
            'applied' => $totalApplied,
            'message' => currency_symbol() . " " . number_format($totalApplied) . " applied from advance to outstanding dues.",
        ];
    }
}
