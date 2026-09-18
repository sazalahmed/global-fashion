<?php

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Modules\Payment\Models\PaymentAccount;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\Bank;
use Modules\Payment\Models\BalanceTransfer;
use Modules\Payment\Models\AccountCharge;
use Modules\Payment\Http\Requests\StoreBalanceTransferRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentAccountController extends Controller
{
    public function index(Request $request)
    {
        bpAuthorize('payments.view');
        $filters = $request->only(['search', 'account_type', 'status']);

        $query = PaymentAccount::with('bank')->latest();

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('mobile_number', 'like', "%{$s}%")
                  ->orWhere('bank_account_number', 'like', "%{$s}%");
            });
        }

        if (!empty($filters['account_type'])) {
            $query->where('account_type', $filters['account_type']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_active', $filters['status'] === 'active');
        }

        // Cloned before paginate() so the total covers every account matching
        // the filters rather than just the visible page — same convention as
        // the sales list's calculation row. Balances are derived per account
        // rather than stored, so this has to walk the models.
        $totalsQuery = clone $query;

        $accounts = $query->paginate(15)->withQueryString();

        $totalBalance = $totalsQuery->get()->sum(fn ($account) => $account->currentBalance());

        $stats = [
            'total'          => PaymentAccount::count(),
            'cash'           => PaymentAccount::ofType('cash')->count(),
            'mobile_banking' => PaymentAccount::ofType('mobile_banking')->count(),
            'bank'           => PaymentAccount::ofType('bank')->count(),
            'card'           => PaymentAccount::ofType('card')->count(),
        ];

        return view('payment::accounts.index', compact('accounts', 'stats', 'totalBalance'));
    }

    public function create()
    {
        bpAuthorize('payments.create');
        $banks = Bank::active()->orderBy('name')->get();
        $mobileBanks = \Modules\Payment\Models\MobileBank::active()->orderBy('name')->get();

        return view('payment::accounts.create', compact('banks', 'mobileBanks'));
    }

    public function store(Request $request)
    {
        bpAuthorize('payments.create');
        $validated = $request->validate($this->validationRules());

        $validated['created_by'] = auth()->id();

        // Set default: only one default per type
        if (!empty($validated['is_default'])) {
            PaymentAccount::where('account_type', $validated['account_type'])
                ->update(['is_default' => false]);
        }

        PaymentAccount::create($validated);

        return redirect()->route('payment-accounts.index')
            ->with('success', __('Payment account created successfully.'));
    }

    public function edit(PaymentAccount $paymentAccount)
    {
        bpAuthorize('payments.create');
        $banks = Bank::active()->orderBy('name')->get();
        $mobileBanks = \Modules\Payment\Models\MobileBank::active()->orderBy('name')->get();

        return view('payment::accounts.edit', compact('paymentAccount', 'banks', 'mobileBanks'));
    }

    public function update(Request $request, PaymentAccount $paymentAccount)
    {
        bpAuthorize('payments.create');
        $validated = $request->validate($this->validationRules());

        if (!empty($validated['is_default'])) {
            PaymentAccount::where('account_type', $validated['account_type'])
                ->where('id', '!=', $paymentAccount->id)
                ->update(['is_default' => false]);
        }

        $paymentAccount->update($validated);

        return redirect()->route('payment-accounts.index')
            ->with('success', __('Payment account updated successfully.'));
    }

    public function toggleStatus(PaymentAccount $paymentAccount): JsonResponse
    {
        bpAuthorize('payments.create');
        $paymentAccount->update(['is_active' => ! $paymentAccount->is_active]);

        return response()->json([
            'success'   => true,
            'is_active' => $paymentAccount->is_active,
            'message'   => __('Status updated.'),
        ]);
    }

    public function destroy(PaymentAccount $paymentAccount)
    {
        bpAuthorize('payments.delete');
        $paymentAccount->delete();

        return redirect()->route('payment-accounts.index')
            ->with('success', __('Payment account deleted successfully.'));
    }

    /**
     * AJAX: Get active payment accounts for POS/Sales dropdowns.
     */
    public function apiList(Request $request): JsonResponse
    {
        bpAuthorize('payments.view');
        $type = $request->input('type');

        $query = PaymentAccount::active()->with('bank');

        if ($type) {
            $query->ofType($type);
        }

        return response()->json([
            'accounts' => $query->get()->map(fn($a) => [
                'id'           => $a->id,
                'name'         => $a->name,
                'display_name' => $a->display_name,
                'account_type' => $a->account_type,
                'is_default'   => $a->is_default,
            ]),
        ]);
    }

    /**
     * Balance transfers page.
     */
    public function transfers()
    {
        bpAuthorize('payments.view');
        $transfers = BalanceTransfer::with(['fromAccount', 'toAccount', 'creator'])
            ->latest('date')
            ->paginate(20);
        $accounts = PaymentAccount::active()->get();
        $accountBalances = $accounts->mapWithKeys(fn ($a) => [$a->id => $a->currentBalance()]);

        return view('payment::accounts.transfers', compact('transfers', 'accounts', 'accountBalances'));
    }

    public function storeTransfer(StoreBalanceTransferRequest $request)
    {
        bpAuthorize('payments.create');
        $validated = $request->validated();

        $validated['created_by'] = auth()->id();
        BalanceTransfer::create($validated);

        return redirect()->route('payment-accounts.transfers')
            ->with('success', __('Balance transfer recorded successfully.'));
    }

    /**
     * Record a standalone bank charge against an account (maintenance fee,
     * SMS fee, cash-out charge, ...). Posts DR Bank Charges & Fees (5170) /
     * CR the account's cash/bank ledger, so it lands in P&L and cash flow.
     */
    public function storeCharge(Request $request, PaymentAccount $paymentAccount)
    {
        bpAuthorize('payments.create');
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date'   => 'required|date',
            'note'   => 'nullable|string|max:500',
        ]);

        $charge = AccountCharge::create([
            'payment_account_id' => $paymentAccount->id,
            'amount'             => $validated['amount'],
            'date'               => $validated['date'],
            'note'               => $validated['note'] ?? null,
            'created_by'         => auth()->id(),
        ]);

        try {
            $typeToCode = ['cash' => '1001', 'mobile_banking' => '1002', 'bank' => '1004', 'card' => '1004'];
            $cashCode = $typeToCode[$paymentAccount->account_type] ?? '1001';

            $account = fn ($code) => \Modules\Accounting\Models\Account::where('account_code', $code)->value('id');
            $chargeAccId = $account('5170'); // Bank Charges & Fees
            $cashAccId = $account($cashCode);

            if ($chargeAccId && $cashAccId) {
                $je = app(\Modules\Accounting\Services\JournalEntryService::class)->createFromSource(
                    'bank_charge',
                    $charge->id,
                    [
                        ['account_id' => $chargeAccId, 'debit_amount' => $charge->amount, 'credit_amount' => 0, 'description' => "Bank charge on {$paymentAccount->name} (CHG-{$charge->id})"],
                        ['account_id' => $cashAccId, 'debit_amount' => 0, 'credit_amount' => $charge->amount, 'description' => "Bank charge on {$paymentAccount->name} (CHG-{$charge->id})"],
                    ],
                    "Bank charge: {$paymentAccount->name}",
                    "CHG-{$charge->id}",
                    $charge->date,
                );

                $charge->update(['journal_entry_id' => $je->id]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to record bank charge JE for charge {$charge->id}: {$e->getMessage()}");
        }

        return redirect()->route('payment-accounts.index')
            ->with('success', __('Bank charge of :amount recorded against :account.', [
                'amount'  => currency_symbol() . ' ' . number_format($charge->amount),
                'account' => $paymentAccount->name,
            ]));
    }

    /**
     * Manage banks list.
     */
    public function banks()
    {
        bpAuthorize('payments.view');
        $banks = Bank::orderBy('name')->get();

        return view('payment::accounts.banks', compact('banks'));
    }

    public function storeBank(Request $request)
    {
        bpAuthorize('payments.create');
        $request->validate(['name' => 'required|string|max:100|unique:banks,name']);
        Bank::create(['name' => $request->name]);

        return redirect()->route('payment-accounts.banks')
            ->with('success', __('Bank added successfully.'));
    }

    public function destroyBank(Bank $bank)
    {
        bpAuthorize('payments.delete');
        if (PaymentAccount::where('bank_id', $bank->id)->exists()) {
            return back()->with('error', __('Cannot delete bank — it is linked to payment accounts.'));
        }
        $bank->delete();

        return redirect()->route('payment-accounts.banks')
            ->with('success', __('Bank deleted.'));
    }

    /**
     * Manage mobile banks list.
     */
    public function mobileBanks()
    {
        bpAuthorize('payments.view');
        $mobileBanks = \Modules\Payment\Models\MobileBank::orderBy('name')->get();

        return view('payment::accounts.mobile-banks', compact('mobileBanks'));
    }

    public function storeMobileBank(Request $request)
    {
        bpAuthorize('payments.create');
        $request->validate(['name' => 'required|string|max:100|unique:mobile_banks,name']);
        \Modules\Payment\Models\MobileBank::create(['name' => $request->name]);

        return redirect()->route('payment-accounts.mobile-banks')
            ->with('success', __('Mobile bank added successfully.'));
    }

    public function destroyMobileBank(\Modules\Payment\Models\MobileBank $mobileBank)
    {
        bpAuthorize('payments.delete');
        if (PaymentAccount::where('mobile_bank_name', $mobileBank->name)->exists()) {
            return back()->with('error', __('Cannot delete — it is linked to payment accounts.'));
        }
        $mobileBank->delete();

        return redirect()->route('payment-accounts.mobile-banks')
            ->with('success', __('Mobile bank deleted.'));
    }

    /**
     * Display the ledger for a specific payment account.
     */
    public function ledger(Request $request, PaymentAccount $paymentAccount)
    {
        bpAuthorize('payments.view');
        $from = $request->input('from');
        $to = $request->input('to');

        $transactions = $this->ledgerSources($paymentAccount, $from, $to)
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();

        // With a start date the table opens mid-history, so the balance has to
        // pick up where the untouched earlier movements left it rather than
        // restarting from the account's opening balance.
        $openingBalance = (float) $paymentAccount->opening_balance
            + $this->netMovementBefore($paymentAccount, $from);

        // Several accounts can share a name, so a transfer described by name
        // alone is ambiguous. Resolve the counterparty and label it with the
        // identifying detail (bank + account number, wallet + mobile number).
        $counterparties = PaymentAccount::withTrashed()
            ->with('bank')
            ->whereIn('id', $transactions->pluck('counterparty_account_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        // Calculate running balance
        $balance = $openingBalance;
        $totalIn = 0;
        $totalOut = 0;

        foreach ($transactions as $txn) {
            if ($counterparty = $counterparties->get($txn->counterparty_account_id)) {
                $txn->description = ($txn->txn_type === 'transfer_out' ? 'Transfer to ' : 'Transfer from ')
                    . $counterparty->ledger_label;
            }

            $balance += (float) $txn->in_amount - (float) $txn->out_amount;
            $txn->running_balance = $balance;
            $totalIn += (float) $txn->in_amount;
            $totalOut += (float) $txn->out_amount;
        }

        // Newest first for display. The running balance above has to accumulate
        // oldest-first, so the flip happens after it is worked out, not in the
        // query's ORDER BY.
        $transactions = $transactions->reverse()->values();

        // Every row's balance depends on the ones before it, so the page is
        // sliced out of the finished ledger rather than fetched with LIMIT.
        // The totals above stay whole-period. Mirrors
        // CustomerService::getLedgerPaginated().
        $perPage = 15;
        $page = Paginator::resolveCurrentPage();
        $transactions = new LengthAwarePaginator(
            $transactions->forPage($page, $perPage)->values(),
            $transactions->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()],
        );

        return view('payment::accounts.ledger', compact(
            'paymentAccount', 'transactions', 'totalIn', 'totalOut', 'balance', 'openingBalance'
        ));
    }

    /**
     * Net of everything that moved through the account before the window
     * opens, so a filtered view can carry the balance forward. Runs the same
     * source definitions as the visible rows — they cannot drift apart.
     */
    private function netMovementBefore(PaymentAccount $paymentAccount, ?string $from): float
    {
        if (! $from) {
            return 0.0;
        }

        $prior = $this->ledgerSources(
            $paymentAccount,
            null,
            Carbon::parse($from)->subDay()->toDateString(),
        );

        return (float) DB::query()
            ->fromSub($prior, 'prior')
            ->selectRaw('COALESCE(SUM(in_amount - out_amount), 0) as net')
            ->value('net');
    }

    /**
     * Every table that moves money through a payment account, unioned into one
     * shape: date, reference, description, in_amount, out_amount, txn_type,
     * counterparty_account_id, created_at. PaymentAccount::currentBalance()
     * sums the same set and must be kept in step with it.
     */
    private function ledgerSources(PaymentAccount $paymentAccount, ?string $from, ?string $to)
    {
        // 1. Payments received INTO this account
        $received = Payment::where('payment_account_id', $paymentAccount->id)
            ->where('direction', 'receive')
            ->when($from, fn ($q) => $q->where('payment_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('payment_date', '<=', $to))
            ->select([
                'payment_date as date',
                'payment_number as reference',
                DB::raw("CONCAT('Payment from ', COALESCE(party_type, 'N/A'), ' — ', payment_type) as description"),
                'amount as in_amount',
                DB::raw('0 as out_amount'),
                DB::raw("'receive' as txn_type"),
                DB::raw('NULL as counterparty_account_id'),
                'created_at',
            ]);

        // 2. Payments paid OUT from this account
        $paid = Payment::where('payment_account_id', $paymentAccount->id)
            ->where('direction', 'pay')
            ->when($from, fn ($q) => $q->where('payment_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('payment_date', '<=', $to))
            ->select([
                'payment_date as date',
                'payment_number as reference',
                DB::raw("CONCAT('Payment to ', COALESCE(party_type, 'N/A'), ' — ', payment_type) as description"),
                DB::raw('0 as in_amount'),
                'amount as out_amount',
                DB::raw("'pay' as txn_type"),
                DB::raw('NULL as counterparty_account_id'),
                'created_at',
            ]);

        // 3. Transfers FROM this account (money going out)
        $transfersOut = BalanceTransfer::where('from_account_id', $paymentAccount->id)
            ->when($from, fn ($q) => $q->where('date', '>=', $from))
            ->when($to, fn ($q) => $q->where('date', '<=', $to))
            ->join('payment_accounts as to_acc', 'balance_transfers.to_account_id', '=', 'to_acc.id')
            ->select([
                'balance_transfers.date',
                DB::raw("CONCAT('TRF-', balance_transfers.id) as reference"),
                DB::raw("CONCAT('Transfer to ', to_acc.name) as description"),
                DB::raw('0 as in_amount'),
                DB::raw('(balance_transfers.amount + balance_transfers.charge) as out_amount'),
                DB::raw("'transfer_out' as txn_type"),
                'balance_transfers.to_account_id as counterparty_account_id',
                'balance_transfers.created_at',
            ]);

        // 4. Transfers TO this account (money coming in)
        $transfersIn = BalanceTransfer::where('to_account_id', $paymentAccount->id)
            ->when($from, fn ($q) => $q->where('date', '>=', $from))
            ->when($to, fn ($q) => $q->where('date', '<=', $to))
            ->join('payment_accounts as from_acc', 'balance_transfers.from_account_id', '=', 'from_acc.id')
            ->select([
                'balance_transfers.date',
                DB::raw("CONCAT('TRF-', balance_transfers.id) as reference"),
                DB::raw("CONCAT('Transfer from ', from_acc.name) as description"),
                'balance_transfers.amount as in_amount',
                DB::raw('0 as out_amount'),
                DB::raw("'transfer_in' as txn_type"),
                'balance_transfers.from_account_id as counterparty_account_id',
                'balance_transfers.created_at',
            ]);

        // 5. Standalone bank charges on this account (money going out)
        $charges = AccountCharge::where('payment_account_id', $paymentAccount->id)
            ->when($from, fn ($q) => $q->where('date', '>=', $from))
            ->when($to, fn ($q) => $q->where('date', '<=', $to))
            ->select([
                'date',
                DB::raw("CONCAT('CHG-', id) as reference"),
                DB::raw("COALESCE(NULLIF(note, ''), 'Bank charge') as description"),
                DB::raw('0 as in_amount'),
                'amount as out_amount',
                DB::raw("'bank_charge' as txn_type"),
                DB::raw('NULL as counterparty_account_id'),
                'created_at',
            ]);

        // 6. Investor capital injections (money in) / withdrawals (money out)
        $investorCapital = DB::table('investor_capital')
            ->where('investor_capital.payment_account_id', $paymentAccount->id)
            ->when($from, fn ($q) => $q->where('investor_capital.transaction_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('investor_capital.transaction_date', '<=', $to))
            ->join('investors', 'investors.id', '=', 'investor_capital.investor_id')
            ->select([
                'investor_capital.transaction_date as date',
                DB::raw("CONCAT('CAP-', investor_capital.id) as reference"),
                DB::raw("CONCAT(IF(investor_capital.type = 'inject', 'Capital injection from ', 'Capital withdrawal by '), investors.name) as description"),
                DB::raw("IF(investor_capital.type = 'inject', investor_capital.amount, 0) as in_amount"),
                DB::raw("IF(investor_capital.type = 'inject', 0, investor_capital.amount) as out_amount"),
                DB::raw("IF(investor_capital.type = 'inject', 'capital_in', 'capital_out') as txn_type"),
                DB::raw('NULL as counterparty_account_id'),
                'investor_capital.created_at',
            ]);

        // 7. Profit distributions paid out to investors from this account
        $distributions = DB::table('investor_distributions')
            ->where('investor_distributions.payment_account_id', $paymentAccount->id)
            ->when($from, fn ($q) => $q->where('investor_distributions.distribution_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('investor_distributions.distribution_date', '<=', $to))
            ->join('investors', 'investors.id', '=', 'investor_distributions.investor_id')
            ->select([
                'investor_distributions.distribution_date as date',
                DB::raw("COALESCE(investor_distributions.batch_ref, CONCAT('DIST-', investor_distributions.id)) as reference"),
                DB::raw("CONCAT('Profit distribution to ', investors.name) as description"),
                DB::raw('0 as in_amount'),
                'investor_distributions.distribution_amount as out_amount',
                DB::raw("'distribution' as txn_type"),
                DB::raw('NULL as counterparty_account_id'),
                'investor_distributions.created_at',
            ]);

        // 8. Expenses paid out of this account. Expenses carry no Payment row —
        // they are settled in place on the expense record — so only paid_amount
        // has actually left the account (a cancelled expense resets it to 0).
        $expenses = DB::table('expenses')
            ->where('expenses.payment_account_id', $paymentAccount->id)
            ->whereNull('expenses.deleted_at')
            ->where('expenses.paid_amount', '>', 0)
            ->when($from, fn ($q) => $q->where('expenses.expense_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('expenses.expense_date', '<=', $to))
            ->leftJoin('expense_categories as ec', 'ec.id', '=', 'expenses.expense_category_id')
            ->select([
                'expenses.expense_date as date',
                'expenses.expense_number as reference',
                DB::raw("CONCAT('Expense — ', COALESCE(NULLIF(ec.name, ''), 'Uncategorised'), COALESCE(CONCAT(' — ', NULLIF(expenses.description, '')), '')) as description"),
                DB::raw('0 as in_amount'),
                'expenses.paid_amount as out_amount',
                DB::raw("'expense' as txn_type"),
                DB::raw('NULL as counterparty_account_id'),
                'expenses.created_at',
            ]);

        // 9. Personal loans. Money lent out and returned to a lender leaves the
        // account; repayments collected and loans taken bring money in.
        $loans = DB::table('personal_loan_transactions as plt')
            ->where('plt.payment_account_id', $paymentAccount->id)
            ->whereNull('plt.deleted_at')
            ->when($from, fn ($q) => $q->where('plt.txn_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('plt.txn_date', '<=', $to))
            ->leftJoin('borrowers as b', 'b.id', '=', 'plt.borrower_id')
            ->select([
                'plt.txn_date as date',
                'plt.txn_number as reference',
                DB::raw("CONCAT(
                    CASE plt.type
                        WHEN 'disbursement' THEN 'Loan disbursed to '
                        WHEN 'repayment'    THEN 'Loan repayment from '
                        WHEN 'loan_taken'   THEN 'Loan taken from '
                        WHEN 'loan_return'  THEN 'Loan returned to '
                        ELSE CONCAT(plt.type, ' — ')
                    END, COALESCE(b.name, 'N/A')) as description"),
                DB::raw("IF(plt.type IN ('repayment','loan_taken'), plt.amount, 0) as in_amount"),
                DB::raw("IF(plt.type IN ('repayment','loan_taken'), 0, plt.amount) as out_amount"),
                DB::raw("IF(plt.type IN ('repayment','loan_taken'), 'loan_in', 'loan_out') as txn_type"),
                DB::raw('NULL as counterparty_account_id'),
                'plt.created_at',
            ]);

        // 10. Courier withdrawals — COD proceeds settled into this account.
        // amount is already net of delivery and COD charges.
        $courier = DB::table('courier_withdrawals as cw')
            ->where('cw.payment_account_id', $paymentAccount->id)
            ->whereNull('cw.deleted_at')
            ->when($from, fn ($q) => $q->where('cw.withdrawal_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('cw.withdrawal_date', '<=', $to))
            ->select([
                'cw.withdrawal_date as date',
                'cw.withdrawal_number as reference',
                DB::raw("CONCAT('Courier withdrawal — ', COALESCE(NULLIF(cw.courier_provider, ''), 'Courier')) as description"),
                'cw.amount as in_amount',
                DB::raw('0 as out_amount'),
                DB::raw("'courier_withdrawal' as txn_type"),
                DB::raw('NULL as counterparty_account_id'),
                'cw.created_at',
            ]);

        // 11. Asset purchases paid from this account. A null payment_account_id
        // means the default cash account — AccountingIntegrationService's
        // cashAccountCodeFor() falls back to cash for exactly that case.
        $assets = DB::table('assets as ast')
            ->whereNull('ast.deleted_at')
            ->where('ast.paid_amount', '>', 0)
            ->where(function ($q) use ($paymentAccount) {
                $q->where('ast.payment_account_id', $paymentAccount->id);
                if ($paymentAccount->isDefaultCashAccount()) {
                    $q->orWhereNull('ast.payment_account_id');
                }
            })
            ->when($from, fn ($q) => $q->where('ast.purchase_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('ast.purchase_date', '<=', $to))
            ->select([
                'ast.purchase_date as date',
                'ast.asset_code as reference',
                DB::raw("CONCAT('Asset purchase — ', ast.name) as description"),
                DB::raw('0 as in_amount'),
                'ast.paid_amount as out_amount',
                DB::raw("'asset' as txn_type"),
                DB::raw('NULL as counterparty_account_id'),
                'ast.created_at',
            ]);

        // 12. Later payments against an asset's outstanding purchase due.
        $assetPayments = DB::table('asset_payments as ap')
            ->where('ap.payment_account_id', $paymentAccount->id)
            ->whereNull('ap.deleted_at')
            ->when($from, fn ($q) => $q->where('ap.payment_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('ap.payment_date', '<=', $to))
            ->join('assets as a2', 'a2.id', '=', 'ap.asset_id')
            ->select([
                'ap.payment_date as date',
                'ap.payment_number as reference',
                DB::raw("CONCAT('Asset payment — ', a2.name) as description"),
                DB::raw('0 as in_amount'),
                'ap.amount as out_amount',
                DB::raw("'asset' as txn_type"),
                DB::raw('NULL as counterparty_account_id'),
                'ap.created_at',
            ]);

        // 13. Salary payments. Payroll never stores which payment account paid
        // it — recordPayrollJournal() takes the id, posts with it and discards
        // it — so the only trace left on the row is payment_method, which
        // holds an account_type. Attribute it to the primary account of that
        // type, and only when this account is that one, so two accounts
        // sharing a type never both claim the same salary.
        $payroll = $paymentAccount->isPrimaryForType()
            ? DB::table('payroll_items as pi')
                ->join('payrolls as pr', 'pr.id', '=', 'pi.payroll_id')
                ->join('employees as emp', 'emp.id', '=', 'pi.employee_id')
                ->whereNull('pr.deleted_at')
                ->where('pi.payment_status', 'paid')
                ->where('pi.payment_method', $paymentAccount->account_type)
                ->where('pi.net_salary', '>', 0)
                ->when($from, fn ($q) => $q->whereRaw('DATE(COALESCE(pi.approved_at, pi.updated_at)) >= ?', [$from]))
                ->when($to, fn ($q) => $q->whereRaw('DATE(COALESCE(pi.approved_at, pi.updated_at)) <= ?', [$to]))
                ->select([
                    DB::raw('DATE(COALESCE(pi.approved_at, pi.updated_at)) as date'),
                    'pr.payroll_number as reference',
                    DB::raw("CONCAT('Salary — ', emp.name) as description"),
                    DB::raw('0 as in_amount'),
                    'pi.net_salary as out_amount',
                    DB::raw("'payroll' as txn_type"),
                    DB::raw('NULL as counterparty_account_id'),
                    'pi.updated_at as created_at',
                ])
            : null;

        return $received
            ->unionAll($paid)
            ->unionAll($transfersOut)
            ->unionAll($transfersIn)
            ->unionAll($charges)
            ->unionAll($investorCapital)
            ->unionAll($distributions)
            ->unionAll($expenses)
            ->unionAll($loans)
            ->unionAll($courier)
            ->unionAll($assets)
            ->unionAll($assetPayments)
            ->when($payroll, fn ($q) => $q->unionAll($payroll));
    }

    private function validationRules(): array
    {
        return [
            'name'                => 'required|string|max:150',
            'account_type'        => 'required|in:cash,mobile_banking,bank,card',
            'is_default'          => 'nullable|boolean',
            'is_active'           => 'nullable|boolean',
            'mobile_bank_name'    => 'nullable|string|max:50',
            'mobile_number'       => 'nullable|string|max:20',
            'bank_id'             => 'nullable|exists:banks,id',
            'bank_account_type'   => 'nullable|string|max:30',
            'bank_account_name'   => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_branch'         => 'nullable|string|max:100',
            'card_type'           => 'nullable|string|max:30',
            'card_holder_name'    => 'nullable|string|max:100',
            'card_number'         => 'nullable|string|max:30',
            'service_charge'      => 'nullable|numeric|min:0|max:100',
            'opening_balance'     => 'nullable|numeric|min:0',
        ];
    }
}
