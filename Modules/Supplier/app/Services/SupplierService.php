<?php

namespace Modules\Supplier\Services;

use Modules\Supplier\Models\Supplier;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentService;
use Modules\Purchase\Models\Purchase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SupplierService
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * Get paginated list of suppliers with optional filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Supplier::ordered();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', '%' . $search . '%')
                  ->orWhere('contact_person', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filters['status']) && in_array($filters['status'], ['active', 'inactive'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            match ($filters['payment_status']) {
                'has_due' => $query->where('due_balance', '>', 0),
                'no_due' => $query->where('due_balance', 0),
                default => null,
            };
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Paginated list of suppliers with an outstanding payable (due_balance > 0)
     * for the Payable List page. Supports search + group filter.
     */
    public function getPayableList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Supplier::hasDue()->ordered();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', '%' . $search . '%')
                  ->orWhere('contact_person', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filters['supplier_group_id'])) {
            $query->where('supplier_group_id', $filters['supplier_group_id']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Total outstanding payable across all suppliers.
     */
    public function getTotalPayable(): float
    {
        return (float) Supplier::where('due_balance', '>', 0)->sum('due_balance');
    }

    /**
     * Create a new supplier.
     */
    public function create(array $data): Supplier
    {
        $data['due_balance'] = $data['opening_balance'] ?? 0;

        return Supplier::create($data);
    }

    /**
     * Update an existing supplier.
     */
    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);

        return $supplier;
    }

    /**
     * Toggle a supplier's active/inactive status.
     */
    public function toggleStatus(Supplier $supplier): Supplier
    {
        $supplier->update(['status' => $supplier->status === 'active' ? 'inactive' : 'active']);

        return $supplier;
    }

    /**
     * Soft delete a supplier.
     */
    public function delete(Supplier $supplier): bool
    {
        // Prevent deletion if supplier has outstanding dues
        if ((float) $supplier->due_balance > 0) {
            throw new \RuntimeException("Cannot delete supplier — outstanding due balance of " . currency_symbol() . " " . number_format($supplier->due_balance) . " exists.");
        }

        // Prevent deletion if active (non-cancelled, non-draft) purchases exist
        $activePurchases = \Modules\Purchase\Models\Purchase::where('supplier_id', $supplier->id)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->count();

        if ($activePurchases > 0) {
            throw new \RuntimeException("Cannot delete supplier — {$activePurchases} active purchase(s) exist.");
        }

        // Prevent deletion if active purchase returns exist
        $activeReturns = \Modules\PurchaseReturn\Models\PurchaseReturn::where('supplier_id', $supplier->id)
            ->whereNotIn('status', ['cancelled'])
            ->count();

        if ($activeReturns > 0) {
            throw new \RuntimeException("Cannot delete supplier — {$activeReturns} active purchase return(s) exist.");
        }

        return $supplier->delete();
    }

    /**
     * Get supplier statistics for the index page.
     */
    public function getStats(): array
    {
        return [
            'total' => Supplier::count(),
            'active' => Supplier::where('status', 'active')->count(),
            'totalPayable' => Supplier::sum('due_balance'),
            'thisMonthPurchases' => \Modules\Purchase\Models\Purchase::whereNotIn('status', ['draft', 'cancelled'])
                ->whereMonth('po_date', now()->month)
                ->whereYear('po_date', now()->year)
                ->sum('grand_total'),
        ];
    }

    /**
     * Get all active suppliers for dropdown selects.
     */
    public function getActiveSuppliers(): Collection
    {
        return Supplier::active()->ordered()->get(['id', 'company_name', 'contact_person', 'phone', 'due_balance']);
    }

    /**
     * Get supplier ledger entries (purchases as debit, payments as credit).
     */
    public function getLedger(Supplier $supplier, array $filters = []): array
    {
        // Purchases as debit entries
        $purchasesQuery = Purchase::where('supplier_id', $supplier->id)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->select(
                'po_date as date',
                'po_number as reference',
                DB::raw("'Purchase' as type"),
                DB::raw('grand_total as debit'),
                DB::raw('0 as credit'),
                DB::raw("CONCAT('Purchase Order: ', po_number) as description")
            );

        if (!empty($filters['from'])) {
            $purchasesQuery->where('po_date', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $purchasesQuery->where('po_date', '<=', $filters['to']);
        }

        // Payments as credit entries
        $paymentsQuery = $supplier->payments()
            ->select(
                'payment_date as date',
                'payment_number as reference',
                DB::raw("'Payment' as type"),
                DB::raw('0 as debit'),
                DB::raw('amount as credit'),
                DB::raw("COALESCE(note, CONCAT('Payment: ', payment_number)) as description")
            );

        if (!empty($filters['from'])) {
            $paymentsQuery->where('payment_date', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $paymentsQuery->where('payment_date', '<=', $filters['to']);
        }

        // Combine and sort by date. Use concat() (not merge()): the aliased
        // SELECTs omit the `id` column, so every model's key is null and
        // Eloquent merge()'s key-based dedupe would collapse all rows into one
        // (entries vanished). concat() appends without keying.
        $entries = $purchasesQuery->get()
            ->concat($paymentsQuery->get())
            ->sortBy('date')
            ->values();

        $balance = (float) $supplier->opening_balance;
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

        return [
            'entries' => $ledgerEntries,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'currentBalance' => $balance,
            'openingBalance' => (float) $supplier->opening_balance,
        ];
    }

    /**
     * Record a payment to a supplier. Writes to payments (with journal entry)
     * and allocates against the purchase if provided.
     */
    public function recordPayment(Supplier $supplier, array $data): Payment
    {
        return DB::transaction(function () use ($supplier, $data) {
            if (empty($data['payment_method']) && !empty($data['payment_account_id'])) {
                $acctType = \Modules\Payment\Models\PaymentAccount::where('id', $data['payment_account_id'])->value('account_type');
                $data['payment_method'] = ucfirst(str_replace('_', ' ', $acctType ?? 'cash'));
            }
            $data['payment_method'] = $data['payment_method'] ?? 'Cash';

            $amount = (float) $data['amount'];
            $isAdvance = ($data['payment_type'] ?? 'payment') === 'advance';

            if ($isAdvance) {
                $supplier->increment('advance_balance', $amount);
            }

            $allocations = [];
            if (!empty($data['purchase_id'])) {
                $allocations[] = [
                    'allocatable_type' => Purchase::class,
                    'allocatable_id'   => $data['purchase_id'],
                    'amount'           => $amount,
                ];
            }

            return $this->paymentService->create([
                'direction'          => 'pay',
                'party_type'         => 'supplier',
                'party_id'           => $supplier->id,
                'payment_type'       => $isAdvance ? 'advance_payment' : 'purchase_payment',
                'amount'             => $amount,
                'payment_method'     => strtolower(str_replace(' ', '_', $data['payment_method'])),
                'payment_account_id' => $data['payment_account_id'] ?? null,
                'payment_date'       => $data['payment_date'],
                'reference'          => $data['reference'] ?? null,
                'note'               => $data['notes'] ?? null,
            ], $allocations);
        });
    }

    /**
     * Recalculate supplier balances from actual records.
     */
    public function recalculateBalances(Supplier $supplier): void
    {
        $totalPaid = $supplier->payments()
            ->where('payment_type', 'purchase_payment')
            ->sum('amount');

        $advancePaid = $supplier->payments()
            ->where('payment_type', 'advance_payment')
            ->sum('amount');

        $returnCredits = $supplier->payments()
            ->where('payment_type', 'return_credit')
            ->sum('amount');

        $totalPaidAll = (float) $totalPaid + (float) $returnCredits;
        $dueBalance = (float) $supplier->total_purchase + (float) $supplier->opening_balance - $totalPaidAll;

        $supplier->update([
            'total_paid' => $totalPaidAll,
            'advance_balance' => $advancePaid,
            'due_balance' => max(0, $dueBalance),
        ]);
    }
}
