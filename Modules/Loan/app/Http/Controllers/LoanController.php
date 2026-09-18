<?php

namespace Modules\Loan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Loan\Http\Requests\StoreLoanRepaymentRequest;
use Modules\Loan\Http\Requests\StoreLoanRequest;
use Modules\Loan\Models\Loan;
use Modules\Loan\Models\Lender;
use Modules\Loan\Services\LoanService;
use Modules\Payment\Models\PaymentAccount;

class LoanController extends Controller
{
    public function __construct(
        private readonly LoanService $service,
    ) {}

    public function index(Request $request)
    {
        bpAuthorize('finance.view');
        $stats = $this->service->getStats();
        $loans = $this->service->listLoans(
            $request->only(['search', 'status', 'lender_id', 'date_from', 'date_to']),
        );
        $upcoming = $this->service->getUpcomingDue();
        $lenders = Lender::active()->orderBy('name')->get(['id', 'name']);

        return view('loan::loans.index', compact('stats', 'loans', 'upcoming', 'lenders'));
    }

    public function create()
    {
        bpAuthorize('finance.create');
        $lenders = Lender::active()->orderBy('name')->get(['id', 'name', 'company_name']);
        $paymentAccounts = PaymentAccount::where('is_active', true)->orderBy('name')->get();

        return view('loan::loans.create', compact('lenders', 'paymentAccounts'));
    }

    public function store(StoreLoanRequest $request)
    {
        bpAuthorize('finance.create');
        $loan = $this->service->createLoan($request->validated());

        return redirect()->route('loans.show', $loan)
            ->with('success', "Loan {$loan->loan_number} created. {$loan->total_installments} installments scheduled.");
    }

    public function show(Loan $loan)
    {
        bpAuthorize('finance.view');
        $loan = $this->service->findLoan($loan->id);
        $paymentAccounts = PaymentAccount::where('is_active', true)->orderBy('name')->get();

        return view('loan::loans.show', compact('loan', 'paymentAccounts'));
    }

    public function repayment(StoreLoanRepaymentRequest $request, Loan $loan)
    {
        bpAuthorize('finance.edit');
        try {
            $schedule = $this->service->recordRepayment($loan, $request->validated());

            return back()->with('success', "Installment #{$schedule->installment_number} payment of " . currency_symbol() . " " . number_format($request->amount) . " recorded.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reschedule(Request $request, Loan $loan)
    {
        bpAuthorize('finance.edit');
        $request->validate([
            'schedules'            => ['required', 'array', 'min:1'],
            'schedules.*.id'       => ['required', 'integer', 'exists:loan_schedules,id'],
            'schedules.*.due_date' => ['required', 'date'],
            'schedules.*.amount'   => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $this->service->rescheduleUnpaid($loan, $request->input('schedules'));

            return back()->with('success', __('Repayment schedule updated successfully.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Loan $loan)
    {
        bpAuthorize('finance.edit');
        try {
            $this->service->cancelLoan($loan);

            return redirect()->route('loans.show', $loan)->with('success', "Loan {$loan->loan_number} cancelled.");
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
