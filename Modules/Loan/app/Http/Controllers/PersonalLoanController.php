<?php

namespace Modules\Loan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Loan\Models\Borrower;
use Modules\Loan\Services\PersonalLoanService;
use Modules\Payment\Models\PaymentAccount;

/**
 * Personal loans: one two-way account per person — the business can give a
 * loan (disburse/repay) and take a loan (take/pay-back) with the same party.
 */
class PersonalLoanController extends Controller
{
    public function __construct(
        protected readonly PersonalLoanService $service,
    ) {}

    public function index(Request $request)
    {
        bpAuthorize('finance.view');
        $stats = $this->service->getStats();
        $borrowers = $this->service->listBorrowers($request->only(['search', 'status', 'has_outstanding']));

        return view('loan::personal-loans.index', compact('stats', 'borrowers'));
    }

    public function create()
    {
        bpAuthorize('finance.create');

        return view('loan::personal-loans.create');
    }

    public function store(Request $request)
    {
        bpAuthorize('finance.create');
        $data = $request->validate($this->rules());
        $this->service->createBorrower($data);

        return redirect()->route('personal-loans.index')->with('success', __('Borrower created successfully.'));
    }

    public function show(Borrower $borrower, Request $request)
    {
        bpAuthorize('finance.view');
        $ledger = $this->service->getLedger($borrower, $request->only(['from', 'to']));
        $paymentAccounts = PaymentAccount::where('is_active', true)->orderBy('name')->get();

        return view('loan::personal-loans.show', [
            'borrower'        => $borrower,
            'ledgerEntries'   => $ledger['entries'],
            'totalGiven'      => $ledger['totalGiven'],
            'totalRepaid'     => $ledger['totalRepaid'],
            'totalTaken'      => $ledger['totalTaken'],
            'totalReturned'   => $ledger['totalReturned'],
            'currentBalance'  => $ledger['currentBalance'],
            'paymentAccounts' => $paymentAccounts,
        ]);
    }

    public function edit(Borrower $borrower)
    {
        bpAuthorize('finance.edit');

        return view('loan::personal-loans.edit', compact('borrower'));
    }

    public function update(Request $request, Borrower $borrower)
    {
        bpAuthorize('finance.edit');
        $data = $request->validate($this->rules());
        $this->service->updateBorrower($borrower, $data);

        return redirect()->route('personal-loans.show', $borrower)->with('success', __('Borrower updated.'));
    }

    public function destroy(Borrower $borrower)
    {
        bpAuthorize('finance.delete');
        try {
            $this->service->deleteBorrower($borrower);

            return redirect()->route('personal-loans.index')->with('success', __('Borrower deleted.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function disburse(Request $request, Borrower $borrower)
    {
        bpAuthorize('finance.create');
        $data = $this->txnRules($request);

        try {
            $txn = $this->service->recordDisbursement($borrower, $data);

            // Explicit route (not back()) so a ?action=give-loan deep link in
            // the referring URL can't reopen the modal after a successful submit.
            return redirect()->route('personal-loans.show', $borrower)
                ->with('success', 'Loan of ' . currency_symbol() . ' ' . number_format($txn->amount) . ' given (' . $txn->txn_number . ').');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function repay(Request $request, Borrower $borrower)
    {
        bpAuthorize('finance.edit');
        $data = $this->txnRules($request);

        try {
            $txn = $this->service->recordRepayment($borrower, $data);

            return redirect()->route('personal-loans.show', $borrower)
                ->with('success', 'Repayment of ' . currency_symbol() . ' ' . number_format($txn->amount) . ' recorded (' . $txn->txn_number . ').');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function take(Request $request, Borrower $borrower)
    {
        bpAuthorize('finance.create');
        $data = $this->txnRules($request);

        try {
            $txn = $this->service->recordTaken($borrower, $data);

            return redirect()->route('personal-loans.show', $borrower)
                ->with('success', 'Loan of ' . currency_symbol() . ' ' . number_format($txn->amount) . ' taken (' . $txn->txn_number . ').');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function payBack(Request $request, Borrower $borrower)
    {
        bpAuthorize('finance.edit');
        $data = $this->txnRules($request);

        try {
            $txn = $this->service->recordReturn($borrower, $data);

            return redirect()->route('personal-loans.show', $borrower)
                ->with('success', 'Payment of ' . currency_symbol() . ' ' . number_format($txn->amount) . ' paid back (' . $txn->txn_number . ').');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    protected function rules(): array
    {
        return [
            'name'            => 'required|string|max:255',
            'phone'           => 'nullable|string|max:30',
            'email'           => 'nullable|email|max:255',
            'photo'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'address'         => 'nullable|string|max:500',
            'opening_balance' => 'nullable|numeric|min:0',
            'opening_type'    => 'nullable|in:they_owe,we_owe',
            'status'          => 'nullable|in:active,inactive',
            'notes'           => 'nullable|string|max:1000',
        ];
    }

    protected function txnRules(Request $request): array
    {
        return $request->validate([
            'amount'             => 'required|numeric|min:0.01',
            'txn_date'           => 'nullable|date',
            'payment_account_id' => 'required|exists:payment_accounts,id',
            'reference'          => 'nullable|string|max:100',
            'note'               => 'nullable|string|max:500',
        ]);
    }
}
