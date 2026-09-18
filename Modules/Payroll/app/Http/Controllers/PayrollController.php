<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Branch\Models\Branch;
use Modules\Payroll\Http\Requests\StoreSalaryStructureRequest;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\PayrollItem;
use Modules\Payroll\Models\SalaryStructure;
use Modules\Payroll\Services\PayrollService;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollService $service,
    ) {}

    public function index(Request $request)
    {
        bpAuthorize('hr.view');
        $filters = $request->only(['month', 'status', 'branch_id']);
        // Default the listing to the current month on first load (no explicit
        // month param). An explicit empty month — the user cleared the filter —
        // is respected as "all months", so the key's presence is the signal.
        if (! $request->has('month')) {
            $filters['month'] = now()->format('Y-m');
        }
        $stats = $this->service->getStats();
        $payrolls = $this->service->listPayrolls($filters);
        $branches = Branch::where('is_active', true)->get();

        return view('payroll::index', compact('stats', 'payrolls', 'branches'));
    }

    public function show(Payroll $payroll)
    {
        bpAuthorize('hr.view');
        $payroll = $this->service->findPayroll($payroll->id);

        return view('payroll::show', compact('payroll'));
    }

    public function generate()
    {
        bpAuthorize('hr.create');
        $branches = Branch::where('is_active', true)->get();

        return view('payroll::generate', compact('branches'));
    }

    public function generateStore(Request $request)
    {
        bpAuthorize('hr.create');
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        try {
            $payroll = $this->service->generatePayroll(
                $request->input('month'),
                $request->input('branch_id'),
            );
            return redirect()->route('payroll.show', $payroll)
                ->with('success', "Payroll {$payroll->payroll_number} generated for {$payroll->total_employees} employees.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approve(Payroll $payroll)
    {
        bpAuthorize('hr.edit');
        try {
            $this->service->approvePayroll($payroll);
            return back()->with('success', "Payroll {$payroll->payroll_number} approved.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function markPaid(Request $request, Payroll $payroll)
    {
        bpAuthorize('hr.edit');
        try {
            $paymentAccountId = $request->input('payment_account_id');
            $paymentMethod = 'bank_transfer';
            if ($paymentAccountId) {
                $paymentMethod = \Modules\Payment\Models\PaymentAccount::where('id', $paymentAccountId)->value('account_type') ?? 'bank_transfer';
            }
            $this->service->markAsPaid($payroll, $paymentMethod, $paymentAccountId ? (int) $paymentAccountId : null);
            return back()->with('success', "Payroll {$payroll->payroll_number} marked as paid. Journal entry created.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Payroll $payroll)
    {
        bpAuthorize('hr.edit');
        try {
            $this->service->cancelPayroll($payroll);
            return back()->with('success', "Payroll {$payroll->payroll_number} cancelled.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete a draft or cancelled payroll.
     */
    public function destroy(Payroll $payroll)
    {
        bpAuthorize('hr.delete');
        try {
            $this->service->deletePayroll($payroll);
            return redirect()->route('payroll.index')->with('success', __('Payroll deleted.'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Print view for a payroll.
     */
    public function print(Payroll $payroll)
    {
        bpAuthorize('hr.view');
        $payroll->load(['items.employee', 'branch']);

        return view('payroll::print', compact('payroll'));
    }

    /**
     * Edit an employee's payroll line (overtime, bonus, commission, deductions).
     */
    public function updateItem(Request $request, PayrollItem $item)
    {
        bpAuthorize('hr.edit');
        $data = $request->validate([
            'basic_salary'      => 'nullable|numeric|min:0',
            'overtime'          => 'nullable|numeric|min:0',
            'bonus'             => 'nullable|numeric|min:0',
            'commission'        => 'nullable|numeric|min:0',
            'absent_deduction'  => 'nullable|numeric|min:0',
            'advance_deduction' => 'nullable|numeric|min:0',
        ]);

        try {
            $this->service->updateItem($item, $data);

            return back()->with('success', 'Salary line updated.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approveItem(PayrollItem $item)
    {
        bpAuthorize('hr.edit');
        $this->service->approveItem($item);

        return back()->with('success', 'Salary approved for ' . ($item->employee->name ?? 'employee') . '.');
    }

    public function unapproveItem(PayrollItem $item)
    {
        bpAuthorize('hr.edit');
        try {
            $this->service->unapproveItem($item);

            return back()->with('success', 'Salary approval reverted.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Pay a single approved salary line.
     */
    public function payItem(Request $request, PayrollItem $item)
    {
        bpAuthorize('hr.edit');
        $data = $request->validate([
            'payment_account_id' => 'required|exists:payment_accounts,id',
        ]);

        try {
            $paymentAccountId = (int) $data['payment_account_id'];
            $paymentMethod = \Modules\Payment\Models\PaymentAccount::where('id', $paymentAccountId)->value('account_type') ?? 'bank_transfer';
            $this->service->payItem($item, $paymentMethod, $paymentAccountId);

            return back()->with('success', 'Salary paid for ' . ($item->employee->name ?? 'employee') . '.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Undo a paid salary line's payment so a wrong amount can be fixed and re-paid.
     */
    public function undoPayItem(PayrollItem $item)
    {
        bpAuthorize('hr.edit');
        try {
            $this->service->undoItemPayment($item);

            return back()->with('success', 'Payment undone for ' . ($item->employee->name ?? 'employee') . '. Unapprove the line to edit the amount, then approve and pay again.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Printable per-employee payslip with a signature line.
     */
    public function payslip(PayrollItem $item)
    {
        bpAuthorize('hr.view');
        $item->load(['employee', 'payroll.branch']);

        return view('payroll::payslip', compact('item'));
    }

    // Salary Structures
    public function salaryStructures()
    {
        bpAuthorize('hr.view');
        $structures = $this->service->listStructures();

        return view('payroll::salary-structure', compact('structures'));
    }

    public function salaryStructureCreate()
    {
        bpAuthorize('hr.create');
        return view('payroll::salary-structure-create');
    }

    public function salaryStructureStore(StoreSalaryStructureRequest $request)
    {
        bpAuthorize('hr.create');
        $this->service->createStructure(
            $request->only(['name', 'code', 'description', 'is_active']),
            $request->input('components', []),
        );

        return redirect()->route('payroll.salary-structures')
            ->with('success', __('Salary structure created.'));
    }

    public function salaryStructureShow(SalaryStructure $salaryStructure)
    {
        bpAuthorize('hr.view');
        $structure = $this->service->findStructure($salaryStructure->id);

        return view('payroll::salary-structure-show', compact('structure'));
    }

    public function salaryStructureEdit(SalaryStructure $salaryStructure)
    {
        bpAuthorize('hr.edit');
        $structure = $this->service->findStructure($salaryStructure->id);

        return view('payroll::salary-structure-edit', compact('structure'));
    }

    public function salaryStructureUpdate(StoreSalaryStructureRequest $request, SalaryStructure $salaryStructure)
    {
        bpAuthorize('hr.edit');
        $this->service->updateStructure(
            $salaryStructure,
            $request->only(['name', 'code', 'description', 'is_active']),
            $request->input('components', []),
        );

        return redirect()->route('payroll.salary-structures.show', $salaryStructure)
            ->with('success', __('Salary structure updated.'));
    }

    public function salaryStructureDestroy(SalaryStructure $salaryStructure)
    {
        bpAuthorize('hr.delete');
        $this->service->deleteStructure($salaryStructure);

        return redirect()->route('payroll.salary-structures')
            ->with('success', __('Salary structure deleted.'));
    }

    public function salaryStructureToggleStatus(SalaryStructure $salaryStructure): \Illuminate\Http\JsonResponse
    {
        bpAuthorize('hr.edit');
        $salaryStructure->update(['is_active' => ! $salaryStructure->is_active]);

        return response()->json(['success' => true, 'is_active' => $salaryStructure->is_active, 'message' => __('Status updated.')]);
    }
}
