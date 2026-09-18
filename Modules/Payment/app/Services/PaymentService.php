<?php

namespace Modules\Payment\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Payment\Models\Payment;

class PaymentService
{
    public function __construct(
        private readonly JournalEntryService $journalService,
    ) {}

    // ── List ──

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Payment::with(['paymentAccount', 'creator', 'branch'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['direction'] ?? null, fn ($q, $d) => $q->where('direction', $d))
            ->when($filters['party_type'] ?? null, fn ($q, $t) => $q->where('party_type', $t))
            ->when($filters['payment_method'] ?? null, fn ($q, $m) => $q->byMethod($m))
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->where('payment_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->where('payment_date', '<=', $d))
            ->latest('payment_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): Payment
    {
        return Payment::with([
            'paymentAccount', 'journalEntry.lines.account',
            'allocations.allocatable', 'creator', 'branch',
        ])->findOrFail($id);
    }

    // ── Create ──

    public function create(array $data, array $allocations = []): Payment
    {
        return DB::transaction(function () use ($data, $allocations) {
            // The total discount is derived from the allocations, never taken
            // from the client's top-level total — an allocation is the only
            // place a discount is actually tied to a document.
            $discountTotal = collect($allocations)->sum(fn ($a) => (float) ($a['discount_amount'] ?? 0));

            $payment = Payment::create([
                'payment_number' => $this->generateNumber(),
                'direction'      => $data['direction'],
                'party_type'     => $data['party_type'],
                'party_id'       => $data['party_id'],
                'payment_type'   => $data['payment_type'],
                'amount'         => $data['amount'],
                'discount_amount' => $discountTotal,
                // Derive the method from the account that was chosen. Blindly
                // defaulting to 'Cash' mislabelled every form that posts only
                // payment_account_id (the receive-against-invoices flow), and
                // because buildJournalLines() resolves the ledger account from
                // this label it also filed bank receipts under Cash in Hand.
                'payment_method' => $data['payment_method']
                    ?? $this->methodForAccount($data['payment_account_id'] ?? null),
                'payment_account_id' => $data['payment_account_id'] ?? null,
                'payment_date'   => $data['payment_date'],
                'reference'      => $data['reference'] ?? null,
                'note'           => $data['note'] ?? null,
                'created_by'     => auth()->id(),
                'branch_id'      => $data['branch_id'] ?? auth()->user()->branch_id ?? null,
            ]);

            // Build journal entry lines
            $lines = $this->buildJournalLines($payment);

            $je = $this->journalService->createFromSource(
                'payment',
                $payment->id,
                $lines,
                "Payment {$payment->payment_number} — {$payment->direction}",
                $payment->payment_number,
                $payment->payment_date,
            );

            $payment->update(['journal_entry_id' => $je->id]);

            // Allocate against invoices and update their paid/due amounts
            foreach ($allocations as $alloc) {
                $discount = (float) ($alloc['discount_amount'] ?? 0);

                $payment->allocations()->create([
                    'allocatable_type' => $alloc['allocatable_type'],
                    'allocatable_id'   => $alloc['allocatable_id'],
                    'amount'           => $alloc['amount'],
                    'discount_amount'  => $discount,
                ]);

                // Update the allocatable document's paid/due amounts
                $allocatable = $alloc['allocatable_type']::find($alloc['allocatable_id']);
                if ($allocatable && isset($allocatable->paid_amount)) {
                    $allocatable->paid_amount = (float) $allocatable->paid_amount + (float) $alloc['amount'];

                    // due_discount_amount only exists on documents that support
                    // a due write-off (currently Sale) — guarded the same way
                    // paid_amount is above, so this is a no-op elsewhere.
                    if ($discount > 0 && isset($allocatable->due_discount_amount)) {
                        $allocatable->due_discount_amount = (float) $allocatable->due_discount_amount + $discount;
                    }

                    $allocatable->due_amount = max(0, (float) $allocatable->grand_total - (float) $allocatable->paid_amount - (float) ($allocatable->due_discount_amount ?? 0));
                    $allocatable->payment_status = $allocatable->due_amount <= 0 ? 'paid' : 'partial';
                    $allocatable->save();
                }
            }

            // Update party denormalized totals
            $this->updatePartyTotals($payment);

            return $payment->load(['paymentAccount', 'allocations']);
        });
    }

    /**
     * Rebuild a payment's journal entry from its current amount/account/date.
     * A payment edited in place (SaleService::syncPayments()) changes the
     * `payments` row but leaves the posted journal at whatever it was built
     * with originally — the GL, cash flow and account balances then keep
     * disagreeing with the payment by the edited amount, permanently, until
     * this runs.
     */
    public function resyncJournal(Payment $payment): void
    {
        if ($payment->journalEntry && $payment->journalEntry->status === 'posted') {
            $this->journalService->void(
                $payment->journalEntry,
                "Payment {$payment->payment_number} corrected",
            );
        }

        $je = $this->journalService->createFromSource(
            'payment',
            $payment->id,
            $this->buildJournalLines($payment),
            "Payment {$payment->payment_number} — {$payment->direction}",
            $payment->payment_number,
            $payment->payment_date,
        );

        $payment->update(['journal_entry_id' => $je->id]);
    }

    /**
     * Update the supplier's denormalized total_paid counter.
     * Customer totals are derived live from sales — no counter to maintain.
     */
    private function updatePartyTotals(Payment $payment): void
    {
        if ($payment->party_type === 'supplier' && $payment->direction === 'pay') {
            $supplier = \Modules\Supplier\Models\Supplier::find($payment->party_id);
            if ($supplier) {
                $supplier->increment('total_paid', $payment->amount);
                $supplier->due_balance = max(0, (float) $supplier->total_purchase - (float) $supplier->total_paid);
                $supplier->save();
            }
        }
    }

    private function reversePartyTotals(Payment $payment): void
    {
        if ($payment->party_type === 'supplier' && $payment->direction === 'pay') {
            $supplier = \Modules\Supplier\Models\Supplier::find($payment->party_id);
            if ($supplier) {
                $supplier->decrement('total_paid', $payment->amount);
                $supplier->due_balance = max(0, (float) $supplier->total_purchase - (float) $supplier->total_paid);
                $supplier->save();
            }
        }
    }

    // ── Delete ──

    public function delete(Payment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            // Reverse allocations on allocatable documents
            foreach ($payment->allocations as $alloc) {
                $allocatable = $alloc->allocatable;
                if ($allocatable && isset($allocatable->paid_amount)) {
                    $allocatable->paid_amount = max(0, (float) $allocatable->paid_amount - (float) $alloc->amount);

                    if ((float) $alloc->discount_amount > 0 && isset($allocatable->due_discount_amount)) {
                        $allocatable->due_discount_amount = max(0, (float) $allocatable->due_discount_amount - (float) $alloc->discount_amount);
                    }

                    $allocatable->due_amount = (float) ($allocatable->grand_total ?? 0) - (float) $allocatable->paid_amount - (float) ($allocatable->due_discount_amount ?? 0);
                    $allocatable->payment_status = $allocatable->paid_amount <= 0 ? 'unpaid' : ($allocatable->due_amount <= 0 ? 'paid' : 'partial');
                    $allocatable->save();
                }
            }

            $payment->allocations()->delete();

            // Reverse party denormalized totals
            $this->reversePartyTotals($payment);

            if ($payment->journalEntry && $payment->journalEntry->status === 'posted') {
                $this->journalService->void(
                    $payment->journalEntry,
                    "Payment {$payment->payment_number} deleted",
                );
            }

            return $payment->delete();
        });
    }

    // ── Stats ──

    public function getStats(array $filters = []): array
    {
        $base = Payment::query()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['direction'] ?? null, fn ($q, $d) => $q->where('direction', $d))
            ->when($filters['party_type'] ?? null, fn ($q, $t) => $q->where('party_type', $t))
            ->when($filters['payment_method'] ?? null, fn ($q, $m) => $q->byMethod($m))
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->where('payment_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->where('payment_date', '<=', $d));

        $hasFilters = !empty(array_filter($filters));

        if ($hasFilters) {
            return [
                'total_received'     => (clone $base)->where('direction', 'receive')->sum('amount'),
                'total_paid'         => (clone $base)->where('direction', 'pay')->sum('amount'),
                'total_transactions' => (clone $base)->count(),
                'today_received'     => (clone $base)->where('direction', 'receive')->sum('amount'),
                'today_paid'         => (clone $base)->where('direction', 'pay')->sum('amount'),
            ];
        }

        $thisMonth = [now()->startOfMonth(), now()->endOfMonth()];

        return [
            'total_received'       => (clone $base)->where('direction', 'receive')->whereBetween('payment_date', $thisMonth)->sum('amount'),
            'total_paid'           => (clone $base)->where('direction', 'pay')->whereBetween('payment_date', $thisMonth)->sum('amount'),
            'total_transactions'   => (clone $base)->whereBetween('payment_date', $thisMonth)->count(),
            'today_received'       => (clone $base)->where('direction', 'receive')->whereDate('payment_date', today())->sum('amount'),
            'today_paid'           => (clone $base)->where('direction', 'pay')->whereDate('payment_date', today())->sum('amount'),
        ];
    }

    // ── Advance Balances ──

    public function getAdvanceBalances(): array
    {
        $advancesByType = function (string $partyType) {
            return Payment::where('party_type', $partyType)
                ->whereIn('payment_type', ['advance_payment', 'advance_return'])
                ->selectRaw('party_id')
                ->selectRaw('SUM(CASE WHEN payment_type = "advance_payment" AND direction = "receive" THEN amount ELSE 0 END) as received')
                ->selectRaw('SUM(CASE WHEN payment_type = "advance_payment" AND direction = "pay" THEN amount ELSE 0 END) as paid')
                ->selectRaw('SUM(CASE WHEN payment_type = "advance_return" THEN amount ELSE 0 END) as returned')
                ->groupBy('party_id')
                ->havingRaw('(received - paid - returned) > 0')
                ->get();
        };

        return [
            'customer' => $advancesByType('customer'),
            'supplier' => $advancesByType('supplier'),
            'employee' => $advancesByType('employee'),
        ];
    }

    // ── Outstanding Invoices ──

    public function getOutstandingInvoices(string $partyType, int $partyId): Collection
    {
        $modelMap = [
            'customer' => \Modules\Sale\Models\Sale::class,
            'supplier' => \Modules\Purchase\Models\Purchase::class,
        ];

        $modelClass = $modelMap[$partyType] ?? null;

        if (!$modelClass || !class_exists($modelClass)) {
            return collect();
        }

        $isCustomer = $partyType === 'customer';
        $foreignKey = $isCustomer ? 'customer_id' : 'supplier_id';

        $query = $modelClass::where($foreignKey, $partyId)
            ->where('due_amount', '>', 0);

        if ($isCustomer) {
            $query->select('id', 'invoice_number', 'grand_total', 'paid_amount', 'due_amount', 'created_at');
        } else {
            $query->select('id', 'po_number', 'grand_total', 'paid_amount', 'due_amount', 'po_date as created_at')
                  ->whereNotIn('status', ['draft', 'cancelled']);
        }

        return $query->orderBy('created_at')->get();
    }

    // ── Party Search ──

    public function searchParties(string $type, string $term, bool $duesOnly = false): Collection
    {
        $modelMap = [
            'customer' => \Modules\Customer\Models\Customer::class,
            'supplier' => \Modules\Supplier\Models\Supplier::class,
            'employee' => \Modules\Employee\Models\Employee::class,
        ];

        $modelClass = $modelMap[$type] ?? null;

        if (!$modelClass || !class_exists($modelClass)) {
            return collect();
        }

        $nameColumn = $type === 'supplier' ? 'company_name' : 'name';

        return $modelClass::where(function ($q) use ($nameColumn, $term) {
                $q->where($nameColumn, 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%");
            })
            // Restrict to parties with outstanding dues (matches
            // getOutstandingInvoices) when paying against invoices. Employees
            // have no invoice dues, so the filter is a no-op for them.
            ->when($duesOnly && $type === 'customer', fn ($q) => $q->whereIn(
                'id',
                \Modules\Sale\Models\Sale::where('due_amount', '>', 0)->distinct()->pluck('customer_id')
            ))
            ->when($duesOnly && $type === 'supplier', fn ($q) => $q->whereIn(
                'id',
                \Modules\Purchase\Models\Purchase::where('due_amount', '>', 0)
                    ->whereNotIn('status', ['draft', 'cancelled'])->distinct()->pluck('supplier_id')
            ))
            ->limit(15)
            ->get(['id', $nameColumn . ' as name', 'phone']);
    }

    // ── Find Party ──

    /**
     * Find a party (customer/supplier/employee) by type and ID.
     */
    public function findParty(string $type, int $id): ?object
    {
        $modelMap = [
            'customer' => \Modules\Customer\Models\Customer::class,
            'supplier' => \Modules\Supplier\Models\Supplier::class,
            'employee' => \Modules\Employee\Models\Employee::class,
        ];

        $modelClass = $modelMap[$type] ?? null;

        if (!$modelClass || !class_exists($modelClass)) {
            return null;
        }

        $nameColumn = $type === 'supplier' ? 'company_name' : 'name';

        return $modelClass::where('id', $id)
            ->select('id', $nameColumn . ' as name', 'phone')
            ->first();
    }

    // ── Helpers ──

    private function buildJournalLines(Payment $payment): array
    {
        $fallback      = Account::where('account_type', 'asset')->value('id');
        $receivableId  = Account::where('account_code', '1010')->value('id') ?? $fallback; // Accounts Receivable
        $payableId     = Account::where('account_code', '2001')->value('id') ?? $fallback; // Accounts Payable
        $custAdvanceId = Account::where('account_code', '2010')->value('id'); // Customer Advance (liability)
        $suppAdvanceId = Account::where('account_code', '1020')->value('id'); // Supplier Advance (asset)

        // Resolve the CoA asset account from the chosen payment account
        $assetAccountId = $this->resolveAssetAccount($payment) ?? $fallback;

        $isAdvance = in_array($payment->payment_type, ['advance_payment', 'advance_return']);

        if ($payment->direction === 'receive') {
            $counterAccountId = $isAdvance
                ? ($custAdvanceId ?? $receivableId)
                : $receivableId;

            $partyLabel = $payment->party_type
                ? "{$payment->party_type} #{$payment->party_id}"
                : 'walk-in sale';

            $discount = (float) $payment->discount_amount;
            $cashAmount = (float) $payment->amount;

            $lines = [];

            // A pure write-off (0 cash, 100% discount) has no cash leg at
            // all — an amount=0 debit/credit line would be dead weight.
            if ($cashAmount > 0) {
                $lines[] = ['account_id' => $assetAccountId, 'debit_amount' => $cashAmount, 'credit_amount' => 0, 'description' => "Received from {$partyLabel}"];
            }

            // A due-collection discount is a write-off, not cash — booked as
            // an expense so the entry stays balanced: the receivable is
            // credited for cash + discount, matching what was actually
            // cleared off the invoice.
            if ($discount > 0) {
                $discountAccountId = Account::where('account_code', '5180')->value('id') // Discount Allowed
                    ?? Account::where('account_type', 'expense')->value('id')
                    ?? $fallback;

                $lines[] = ['account_id' => $discountAccountId, 'debit_amount' => $discount, 'credit_amount' => 0, 'description' => "Discount allowed: {$payment->payment_number}"];
            }

            $lines[] = ['account_id' => $counterAccountId, 'debit_amount' => 0, 'credit_amount' => $cashAmount + $discount, 'description' => "Payment received: {$payment->payment_number}"];

            return $lines;
        }

        $counterAccountId = $isAdvance
            ? ($suppAdvanceId ?? $payableId)
            : $payableId;

        $partyLabel = $payment->party_type
            ? "{$payment->party_type} #{$payment->party_id}"
            : 'walk-in';

        return [
            ['account_id' => $counterAccountId, 'debit_amount' => $payment->amount, 'credit_amount' => 0, 'description' => "Paid to {$partyLabel}"],
            ['account_id' => $assetAccountId, 'debit_amount' => 0, 'credit_amount' => $payment->amount, 'description' => "Payment made: {$payment->payment_number}"],
        ];
    }

    /**
     * The account_type of a payment account, used as the payment method.
     * Falls back to cash only when no account was chosen at all.
     */
    public function methodForAccount(?int $paymentAccountId): string
    {
        if (! $paymentAccountId) {
            return 'cash';
        }

        return \Modules\Payment\Models\PaymentAccount::withTrashed()
            ->whereKey($paymentAccountId)
            ->value('account_type') ?: 'cash';
    }

    /**
     * The ledger asset account a payment moves money through. The chosen
     * payment account wins over payment_method: the label is denormalised and
     * historically unreliable, while the account is what the user picked.
     */
    private function resolveAssetAccount(Payment $payment): ?int
    {
        $accountType = $payment->payment_account_id
            ? $this->methodForAccount((int) $payment->payment_account_id)
            : null;

        return $this->mapMethodToAccount($accountType ?: (string) $payment->payment_method);
    }

    public function mapMethodToAccount(string $method): ?int
    {
        $typeToCode = [
            'cash'            => '1001',
            'mobile_banking'  => '1002',
            'bank'            => '1004',
            'card'            => '1004',
        ];

        // Case-insensitive: 'Cash' used to miss this map entirely and fall
        // through to the cash default, which silently hid mismatches.
        $method = strtolower(trim($method));

        // If it's an account_type, use it directly
        if (isset($typeToCode[$method])) {
            return Account::where('account_code', $typeToCode[$method])->value('id')
                ?? Account::where('account_code', '1001')->value('id');
        }

        // Fallback: try to resolve via payment_accounts table
        $paymentAccount = \Modules\Payment\Models\PaymentAccount::find($method);
        if ($paymentAccount) {
            $code = $typeToCode[$paymentAccount->account_type] ?? '1001';
            return Account::where('account_code', $code)->value('id')
                ?? Account::where('account_code', '1001')->value('id');
        }

        return Account::where('account_code', '1001')->value('id')
            ?? Account::where('account_type', 'asset')->value('id');
    }

    private function generateNumber(): string
    {
        $year = now()->format('Y');
        $last = Payment::withTrashed()->whereYear('created_at', $year)->count() + 1;

        return 'PAY-' . $year . '-' . str_pad($last, 4, '0', STR_PAD_LEFT);
    }
}
