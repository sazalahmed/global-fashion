<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Branch\Models\Branch;
use Modules\Employee\Http\Requests\StoreEmployeeRequest;
use Modules\Employee\Http\Requests\StoreSalaryIncrementRequest;
use Modules\Employee\Http\Requests\UpdateSalaryIncrementRequest;
use Illuminate\Http\Request;
use Modules\Employee\Models\Department;
use Modules\Employee\Models\Designation;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\SalaryIncrement;
use Modules\Employee\Services\EmployeeService;
use Modules\Employee\Services\EmployeeAdvanceService;
use Modules\Payment\Models\PaymentAccount;
use Modules\Payment\Models\Payment;
use Modules\Accounting\Models\Account;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService $service,
        private readonly EmployeeAdvanceService $advanceService,
    ) {}

    public function index(\Illuminate\Http\Request $request)
    {
        bpAuthorize('hr.view');
        $stats = $this->service->getStats();
        $employees = $this->service->list(
            $request->only(['search', 'status', 'branch_id', 'department_id']),
        );
        $branches = Branch::where('is_active', true)->get();
        $departments = Department::active()->orderBy('sort_order')->orderBy('name')->get();

        return view('employee::index', compact('stats', 'employees', 'branches', 'departments'));
    }

    public function create()
    {
        bpAuthorize('hr.create');
        $branches = Branch::where('is_active', true)->get();
        $departments = Department::active()->orderBy('sort_order')->orderBy('name')->get();
        $designations = Designation::active()->orderBy('sort_order')->orderBy('name')->get();

        return view('employee::create', compact('branches', 'departments', 'designations'));
    }

    public function store(StoreEmployeeRequest $request)
    {
        bpAuthorize('hr.create');
        $employee = $this->service->create($request->validated());

        return redirect()->route('employee.show', $employee)
            ->with('success', "Employee {$employee->name} ({$employee->employee_id}) created.");
    }

    public function show(Employee $employee)
    {
        bpAuthorize('hr.view');
        $employee = $this->service->find($employee->id);
        $increments = $employee->salaryIncrements()
            ->with('incrementedBy')
            ->orderByDesc('applied_at')
            ->orderByDesc('id')
            ->get();

        $paymentAccounts = PaymentAccount::where('is_active', true)->orderBy('name')->get();

        return view('employee::show', compact('employee', 'increments', 'paymentAccounts'));
    }

    /**
     * Give a salary advance to an employee.
     */
    public function giveAdvance(Request $request, Employee $employee)
    {
        bpAuthorize('hr.create');
        $data = $this->advanceRules($request);

        try {
            $adv = $this->advanceService->recordAdvance($employee, $data);

            return back()->with('success', 'Advance of ' . currency_symbol() . ' ' . number_format($adv->amount) . ' given (' . $adv->advance_number . ').');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Record a manual recovery against an employee's advance balance.
     */
    public function recordRecovery(Request $request, Employee $employee)
    {
        bpAuthorize('hr.edit');
        $data = $this->advanceRules($request);

        try {
            $rec = $this->advanceService->recordRecovery($employee, $data);

            return back()->with('success', 'Recovery of ' . currency_symbol() . ' ' . number_format($rec->amount) . ' recorded (' . $rec->advance_number . ').');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function advanceLedger(Employee $employee)
    {
        bpAuthorize('hr.view');
        $employee = $this->service->find($employee->id);
        $ledger = $this->advanceService->getLedger($employee);

        return view('employee::advance-ledger', [
            'employee'       => $employee,
            'ledgerEntries'  => $ledger['entries'],
            'totalGiven'     => $ledger['totalGiven'],
            'totalRecovered' => $ledger['totalRecovered'],
            'currentBalance' => $ledger['currentBalance'],
        ]);
    }

    /**
     * Unified ledger for all employee transactions (salaries, advances, payments).
     */
    public function fullLedger(Request $request, Employee $employee)
    {
        bpAuthorize('hr.view');
        $employee = $this->service->find($employee->id);

        $payrolls = \Illuminate\Support\Facades\DB::table('payroll_items')
            ->join('payrolls', 'payroll_items.payroll_id', '=', 'payrolls.id')
            ->selectRaw("
                'salary' as type,
                payroll_items.id as source_id,
                payrolls.month as date,
                payrolls.payroll_number as reference,
                CONCAT('Salary for ', payrolls.month) as note,
                payroll_items.net_salary as amount,
                payroll_items.payment_method as method,
                payroll_items.created_at
            ")
            ->where('payroll_items.employee_id', $employee->id)
            ->where('payroll_items.payment_status', 'paid');

        $advances = \Illuminate\Support\Facades\DB::table('employee_advances')
            ->selectRaw("
                type,
                id as source_id,
                advance_date as date,
                advance_number as reference,
                note,
                amount,
                NULL as method,
                created_at
            ")
            ->where('employee_id', $employee->id);

        $payments = \Illuminate\Support\Facades\DB::table('payments')
            ->selectRaw("
                'payment' as type,
                id as source_id,
                payment_date as date,
                payment_number as reference,
                note,
                amount,
                payment_method as method,
                created_at
            ")
            ->where('party_type', 'employee')
            ->where('party_id', $employee->id);

        $query = $payrolls->unionAll($advances)->unionAll($payments);
        
        $unified = \Illuminate\Support\Facades\DB::query()
            ->fromSub($query, 'combined');

        if ($request->filled('month')) {
            $unified->where('date', 'like', $request->month . '%');
        }

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $unified->where(function($q) use ($s) {
                $q->where('note', 'like', $s)
                  ->orWhere('reference', 'like', $s);
            });
        }

        $transactions = $unified->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('employee::full-ledger', compact('employee', 'transactions'));
    }

    private function advanceRules(Request $request): array
    {
        return $request->validate([
            'amount'             => 'required|numeric|min:0.01',
            'advance_date'       => 'nullable|date',
            'payment_account_id' => 'required|exists:payment_accounts,id',
            'reference'          => 'nullable|string|max:100',
            'note'               => 'nullable|string|max:500',
        ]);
    }

    public function payDueSalary(Request $request, Employee $employee)
    {
        bpAuthorize('hr.create');
        
        $request->validate([
            'amount'             => 'required|numeric|min:0.01|max:' . $employee->due_salary,
            'payment_account_id' => 'required|exists:payment_accounts,id',
            'payment_date'       => 'required|date',
            'note'               => 'nullable|string|max:500'
        ]);

        try {
            DB::transaction(function () use ($request, $employee) {
                $amount = (float) $request->amount;
                
                // Create a Payment
                $paymentAccount = PaymentAccount::find($request->payment_account_id);
                $year = now()->format('Y');
                $last = Payment::withTrashed()->whereYear('created_at', $year)->count() + 1;
                $paymentNumber = 'PAY-' . $year . '-' . str_pad($last, 4, '0', STR_PAD_LEFT);

                $payment = Payment::create([
                    'payment_number'     => $paymentNumber,
                    'direction'          => 'pay',
                    'party_type'         => 'employee',
                    'party_id'           => $employee->id,
                    'payment_type'       => 'salary_payment',
                    'amount'             => $amount,
                    'payment_method'     => $paymentAccount->account_type,
                    'payment_account_id' => $paymentAccount->id,
                    'payment_date'       => $request->payment_date,
                    'note'               => $request->note ?? 'Due Salary Payment',
                    'created_by'         => auth()->id(),
                ]);

                // Create Journal Entry
                $journalService = app(\Modules\Accounting\Services\JournalEntryService::class);
                
                // Debit: Salary Payable (2015)
                // Credit: Asset Account
                $payableId = Account::where('account_code', '2015')->value('id');
                
                $paymentService = app(\Modules\Payment\Services\PaymentService::class);
                $assetAccountId = $paymentService->mapMethodToAccount($paymentAccount->account_type);

                $lines = [
                    ['account_id' => $payableId, 'debit_amount' => $amount, 'credit_amount' => 0, 'description' => "Due salary paid to Employee #{$employee->id}"],
                    ['account_id' => $assetAccountId, 'debit_amount' => 0, 'credit_amount' => $amount, 'description' => "Due salary payment: {$paymentNumber}"],
                ];

                $je = $journalService->createFromSource(
                    'payment',
                    $payment->id,
                    $lines,
                    "Payment {$paymentNumber} — pay",
                    $paymentNumber,
                    $payment->payment_date,
                );

                $payment->update(['journal_entry_id' => $je->id]);

                // Update Employee Due Salary
                $employee->decrement('due_salary', $amount);
            });

            return back()->with('success', 'Due salary payment recorded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function edit(Employee $employee)
    {
        bpAuthorize('hr.edit');
        $employee = $this->service->find($employee->id);
        $branches = Branch::where('is_active', true)->get();
        $departments = Department::active()->orderBy('sort_order')->orderBy('name')->get();
        $designations = Designation::active()->orderBy('sort_order')->orderBy('name')->get();

        return view('employee::edit', compact('employee', 'branches', 'departments', 'designations'));
    }

    public function update(StoreEmployeeRequest $request, Employee $employee)
    {
        bpAuthorize('hr.edit');
        $this->service->update($employee, $request->validated());

        return redirect()->route('employee.show', $employee)
            ->with('success', "Employee {$employee->name} updated.");
    }

    public function destroy(Employee $employee)
    {
        bpAuthorize('hr.delete');
        try {
            $this->service->delete($employee);

            return redirect()->route('employee.index')
                ->with('success', "Employee {$employee->name} deleted.");
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ── Salary increments ──

    public function salaryIncrementStore(StoreSalaryIncrementRequest $request, Employee $employee)
    {
        bpAuthorize('hr.create');
        $increment = $this->service->incrementSalary(
            $employee,
            $request->input('increment_type'),
            (float) $request->input('increment_value'),
            $request->input('note'),
            $request->input('applied_at'),
        );

        return redirect()->route('employee.show', $employee)
            ->with('success', "Salary incremented to " . currency_symbol() . " " . number_format($increment->new_salary, 0) . ".");
    }

    public function salaryIncrementUpdate(UpdateSalaryIncrementRequest $request, SalaryIncrement $increment)
    {
        bpAuthorize('hr.edit');
        $this->service->updateIncrement($increment, $request->validated());

        return redirect()->route('employee.show', $increment->employee_id)
            ->with('success', __('Salary increment updated.'));
    }

    public function salaryIncrementDestroy(SalaryIncrement $increment)
    {
        bpAuthorize('hr.delete');
        $employeeId = $increment->employee_id;
        $this->service->deleteIncrement($increment);

        return redirect()->route('employee.show', $employeeId)
            ->with('success', __('Salary increment deleted.'));
    }
}
