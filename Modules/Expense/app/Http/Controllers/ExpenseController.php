<?php

namespace Modules\Expense\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Models\Account;
use Modules\Branch\Models\Branch;
use Modules\Expense\Http\Requests\RecordExpensePaymentRequest;
use Modules\Expense\Http\Requests\StoreExpenseRequest;
use Modules\Expense\Http\Requests\UpdateExpenseRequest;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Expense\Services\ExpenseService;
use Modules\Payment\Models\PaymentAccount;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly ExpenseService $service
    ) {}

    /**
     * Display a listing of expenses.
     */
    public function index(Request $request)
    {
        bpAuthorize('finance.view');
        $filters = $request->only(['search', 'category', 'status', 'payment_account_id', 'date_from', 'date_to']);
        $stats = $this->service->getStats($filters);
        $expenses = $this->service->list($filters);
        $filteredTotal = $this->service->filteredTotal($filters);
        $categories = ExpenseCategory::active()->ordered()->get();

        return view('expense::index', compact('stats', 'expenses', 'filteredTotal', 'categories', 'filters'));
    }

    /**
     * Show the form for creating a new expense.
     */
    public function create()
    {
        bpAuthorize('finance.create');
        $categories = ExpenseCategory::flatTree();
        $paymentAccounts = \Modules\Payment\Models\PaymentAccount::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::where('is_active', true)->get();

        return view('expense::create', compact('categories', 'paymentAccounts', 'branches'));
    }

    /**
     * Store a newly created expense.
     */
    public function store(StoreExpenseRequest $request)
    {
        bpAuthorize('finance.create');
        $this->service->create($request->validated());

        return redirect()->route('expenses.index')
            ->with('success', __('Expense recorded successfully.'));
    }

    /**
     * Display the specified expense.
     */
    public function show(Expense $expense)
    {
        bpAuthorize('finance.view');
        $expense = $this->service->find($expense->id);

        return view('expense::show', compact('expense'));
    }

    /**
     * Show the form for editing an expense.
     */
    public function edit(Expense $expense)
    {
        bpAuthorize('finance.edit');
        if ($expense->status !== 'pending') {
            return back()->with('error', __('Only pending expenses can be edited.'));
        }

        $categories = ExpenseCategory::flatTree();
        $expenseAccounts = Account::active()->byType('expense')->orderBy('account_code')->get();
        $paymentAccounts = \Modules\Payment\Models\PaymentAccount::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::where('is_active', true)->get();

        return view('expense::edit', compact('expense', 'categories', 'expenseAccounts', 'paymentAccounts', 'branches'));
    }

    /**
     * Update the specified expense.
     */
    public function update(UpdateExpenseRequest $request, Expense $expense)
    {
        bpAuthorize('finance.edit');
        try {
            $this->service->update($expense, $request->validated());

            return redirect()->route('expenses.show', $expense)
                ->with('success', __('Expense updated successfully.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified expense.
     */
    public function destroy(Expense $expense)
    {
        bpAuthorize('finance.delete');
        try {
            $this->service->delete($expense);

            return redirect()->route('expenses.index')
                ->with('success', __('Expense deleted.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Approve a pending expense.
     */
    public function approve(Expense $expense)
    {
        bpAuthorize('finance.edit');
        try {
            $this->service->approve($expense);

            return back()->with('success', __('Expense approved.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject a pending expense.
     */
    public function reject(Request $request, Expense $expense)
    {
        bpAuthorize('finance.edit');
        $request->validate(['rejection_reason' => 'required|string|max:500']);

        try {
            $this->service->reject($expense, $request->rejection_reason);

            return back()->with('success', __('Expense rejected.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mark an approved expense as paid.
     */
    public function markPaid(Expense $expense)
    {
        bpAuthorize('finance.edit');
        try {
            $this->service->markPaid($expense);

            return back()->with('success', __('Expense marked as paid. Journal entry created.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Record a partial/full payment against an approved expense.
     */
    public function recordPayment(RecordExpensePaymentRequest $request, Expense $expense)
    {
        bpAuthorize('finance.edit');
        $data = $request->validated();

        try {
            $splits = $data['splits'] ?? [];

            if (!empty($splits)) {
                foreach ($splits as $split) {
                    $splitData = $data;
                    $splitData['amount'] = (float) $split['amount'];
                    $splitData['payment_account_id'] = $split['payment_account_id'];
                    unset($splitData['splits']);

                    $this->service->recordPayment($expense, $splitData);
                    $expense->refresh();
                }

                return back()->with('success', count($splits) . ' split payments totalling ' . currency_symbol() . ' ' . number_format($data['amount']) . ' recorded.');
            }

            $this->service->recordPayment($expense, $data);

            return back()->with('success', 'Payment of ' . currency_symbol() . ' ' . number_format($data['amount']) . ' recorded.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Print view for an expense.
     */
    public function print(Expense $expense)
    {
        bpAuthorize('finance.view');
        $expense->load(['category', 'paymentAccount', 'creator', 'approver']);

        return view('expense::print', compact('expense'));
    }

    /**
     * Cancel an expense (reverse journal entry if paid).
     */
    public function cancel(Expense $expense)
    {
        bpAuthorize('finance.edit');
        try {
            $this->service->cancel($expense);

            return redirect()->route('expenses.show', $expense)
                ->with('success', __('Expense cancelled successfully.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the expense ledger.
     */
    public function ledger(Request $request)
    {
        bpAuthorize('finance.view');
        $filters = $request->only(['category', 'date_from', 'date_to', 'branch']);
        $expenseEntries = $this->service->getLedger($filters);
        $categories = ExpenseCategory::active()->ordered()->get();
        $branches = Branch::where('is_active', true)->get();
        $ledgerStats = $this->service->getLedgerStats($filters);

        return view('expense::ledger', compact(
            'expenseEntries',
            'categories',
            'branches',
            'ledgerStats',
            'filters'
        ));
    }

}
