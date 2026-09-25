<?php

namespace Modules\Payroll\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Models\Attendance;
use Modules\Employee\Models\Employee;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\PayrollItem;
use Modules\Payroll\Models\SalaryStructure;
use Modules\Payroll\Models\SalaryStructureComponent;

class PayrollService
{
    public function __construct(
        private readonly \Modules\Attendance\Services\AttendanceService $attendance,
    ) {}

    public function listPayrolls(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Payroll::with('branch', 'creator')
            ->when($filters['month'] ?? null, fn($q, $m) => $q->where('month', $m))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['branch_id'] ?? null, fn($q, $b) => $q->where('branch_id', $b))
            ->orderByDesc('month')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findPayroll(int $id): Payroll
    {
        return Payroll::with('items.employee', 'branch', 'creator', 'approver')->findOrFail($id);
    }

    public function generatePayroll(string $month, ?int $branchId = null): Payroll
    {
        return DB::transaction(function () use ($month, $branchId) {
            // Check if payroll already exists
            $existing = Payroll::where('month', $month)
                ->where('branch_id', $branchId)
                ->first();
            if ($existing) {
                throw new \Exception("Payroll for {$month} already exists.");
            }

            $employees = Employee::active()
                ->when($branchId, fn($q, $b) => $q->where('branch_id', $b))
                ->get();

            if ($employees->isEmpty()) {
                throw new \Exception('No active employees found.');
            }

            $payroll = Payroll::create([
                'payroll_number' => $this->generatePayrollNumber(),
                'month' => $month,
                'branch_id' => $branchId,
                'total_employees' => $employees->count(),
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            // Working days = calendar days minus configured weekends + holidays.
            [$payYear, $payMonth] = array_map('intval', explode('-', $month));
            $workingDays = $this->attendance->getWorkingDaysInMonth($payYear, $payMonth, $branchId);
            $overtimeSummary = $this->attendance->getOvertimeSummary($month, $branchId);

            foreach ($employees as $employee) {
                $attendances = Attendance::where('employee_id', $employee->id)
                    ->byMonth($month)
                    ->get();

                $presentDays = $attendances->whereIn('status', ['present', 'late'])->count();
                $absentDays = $attendances->where('status', 'absent')->count();
                $halfDays = $attendances->where('status', 'half_day')->count();

                // Full basic salary — absence is handled as an explicit, editable
                // deduction below rather than by prorating the basic.
                $basicSalary = (float) $employee->salary;
                $dailyRate = $workingDays > 0 ? $basicSalary / $workingDays : 0;
                $hourlyRate = $dailyRate / 8;

                // Auto-suggested absent deduction (absent days + half of half-days).
                $absentUnits = $absentDays + ($halfDays * 0.5);
                $absentDeduction = round($absentUnits * $dailyRate, 2);

                // Auto-suggested overtime from recorded overtime hours (E3).
                $otRow = $overtimeSummary->get($employee->id);
                $overtimeHours = (float) ($otRow->overtime_hours ?? 0);
                $overtime = round($overtimeHours * $hourlyRate, 2);

                $bonus = 0.0;
                $commission = 0.0;

                // Advance recovery suggestion — capped so net never goes negative.
                $grossBeforeAdvance = $basicSalary + $overtime + $bonus + $commission - $absentDeduction;
                $advanceDed = round(min((float) $employee->advance_balance, max(0, $grossBeforeAdvance)), 2);

                $gross = $basicSalary + $overtime + $bonus + $commission;
                $totalDed = $advanceDed + $absentDeduction;
                $netSalary = max(0, round($gross - $totalDed, 2));

                PayrollItem::create([
                    'payroll_id'          => $payroll->id,
                    'employee_id'         => $employee->id,
                    'salary_structure_id' => $employee->salary_structure_id,
                    'basic_salary'        => $basicSalary,
                    'overtime'            => $overtime,
                    'overtime_hours'      => $overtimeHours,
                    'bonus'               => $bonus,
                    'commission'          => $commission,
                    'gross_salary'        => $gross,
                    'total_earnings'      => $gross,
                    'advance_deduction'   => $advanceDed,
                    'absent_deduction'    => $absentDeduction,
                    'total_deductions'    => $totalDed,
                    'net_salary'          => $netSalary,
                    'status'              => 'pending',
                    'working_days'        => $workingDays,
                    'present_days'        => $presentDays + $halfDays,
                    'absent_days'         => $absentDays,
                ]);
            }

            $this->recalculatePayrollTotals($payroll);

            return $payroll->fresh();
        });
    }

    /**
     * Edit an employee's payroll line. Allowed only while the row is pending
     * (not yet approved) and its payroll isn't fully paid. Recomputes the row's
     * gross/net and the payroll totals. Advance is capped at the employee's
     * current advance balance.
     */
    public function updateItem(PayrollItem $item, array $data): PayrollItem
    {
        if ($item->status === 'approved') {
            throw new \RuntimeException('This salary is already approved. Unapprove it first to edit.');
        }
        if ($item->payment_status === 'paid') {
            throw new \RuntimeException('This salary has been paid and can no longer be edited.');
        }

        $basicSalary = max(0, (float) ($data['basic_salary'] ?? $item->basic_salary));
        $overtime = max(0, (float) ($data['overtime'] ?? $item->overtime));
        $bonus = max(0, (float) ($data['bonus'] ?? $item->bonus));
        $commission = max(0, (float) ($data['commission'] ?? $item->commission));
        $absentDeduction = max(0, (float) ($data['absent_deduction'] ?? $item->absent_deduction));

        $advanceBalance = (float) ($item->employee->advance_balance ?? 0);
        $advanceDeduction = max(0, (float) ($data['advance_deduction'] ?? $item->advance_deduction));
        $advanceDeduction = round(min($advanceDeduction, $advanceBalance), 2);

        $gross = $basicSalary + $overtime + $bonus + $commission;
        $totalDed = $advanceDeduction + $absentDeduction;

        $item->update([
            'basic_salary'      => $basicSalary,
            'overtime'          => $overtime,
            'bonus'             => $bonus,
            'commission'        => $commission,
            'absent_deduction'  => $absentDeduction,
            'advance_deduction' => $advanceDeduction,
            'gross_salary'      => $gross,
            'total_earnings'    => $gross,
            'total_deductions'  => $totalDed,
            'net_salary'        => max(0, round($gross - $totalDed, 2)),
        ]);

        $this->recalculatePayrollTotals($item->payroll);

        return $item->fresh();
    }

    public function approveItem(PayrollItem $item): PayrollItem
    {
        if ($item->status === 'approved') {
            return $item;
        }

        $item->update([
            'status'      => 'approved',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);

        return $item->fresh();
    }

    public function unapproveItem(PayrollItem $item): PayrollItem
    {
        if ($item->payment_status === 'paid') {
            throw new \RuntimeException('A paid salary cannot be unapproved.');
        }

        $item->update([
            'status'      => 'pending',
            'approved_at' => null,
            'approved_by' => null,
        ]);

        return $item->fresh();
    }

    /**
     * Recompute the payroll header totals from its items.
     */
    public function recalculatePayrollTotals(Payroll $payroll): void
    {
        $items = $payroll->items()->get();
        $payroll->update([
            'total_employees'  => $items->count(),
            'total_gross'      => round($items->sum('gross_salary'), 2),
            'total_deductions' => round($items->sum('total_deductions'), 2),
            'total_net'        => round($items->sum('net_salary'), 2),
        ]);
    }

    public function approvePayroll(Payroll $payroll): void
    {
        if ($payroll->status !== 'draft') {
            throw new \Exception('Only draft payrolls can be approved.');
        }

        // Approve every pending item in one go.
        $payroll->items()->where('status', 'pending')->update([
            'status'      => 'approved',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);

        $payroll->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);
    }

    /**
     * Pay out the approved-but-unpaid salary lines. Can be run repeatedly as
     * more employees get approved. Posts one journal entry per batch:
     *   DR Salary Expense (5110) = Σ (gross − absent)   [salary actually earned]
     *   CR Loans & Advances (1015) = Σ advance recovered
     *   CR Cash/Bank              = Σ net paid
     * and records each advance recovery on the employee's advance ledger.
     */
    public function markAsPaid(Payroll $payroll, string $paymentMethod = 'bank_transfer', ?int $paymentAccountId = null): void
    {
        $items = $payroll->items()->where('status', 'approved')->where('payment_status', '!=', 'paid')->with('employee')->get();

        if ($items->isEmpty()) {
            throw new \Exception('No approved salaries are awaiting payment.');
        }

        $this->payItems($payroll, $items, $paymentMethod, $paymentAccountId);
    }

    /**
     * Pay a single approved salary line (per-employee pay from the grid).
     * Same accounting as markAsPaid, just a one-item batch.
     */
    public function payItem(PayrollItem $item, string $paymentMethod = 'bank_transfer', ?int $paymentAccountId = null): void
    {
        if (! $item->isApproved()) {
            throw new \Exception('Only approved salaries can be paid.');
        }
        if ($item->payment_status === 'paid') {
            throw new \Exception('This salary has already been paid.');
        }

        $item->loadMissing('employee', 'payroll');

        $this->payItems($item->payroll, collect([$item]), $paymentMethod, $paymentAccountId);
    }

    /**
     * @param \Illuminate\Support\Collection<int, PayrollItem> $items
     */
    private function payItems(Payroll $payroll, $items, string $paymentMethod, ?int $paymentAccountId): void
    {
        DB::transaction(function () use ($payroll, $items, $paymentMethod, $paymentAccountId) {
            $totalNet = round($items->sum('net_salary'), 2);
            $totalAdvance = round($items->sum('advance_deduction'), 2);
            $totalEarned = round($items->sum(fn ($i) => (float) $i->gross_salary - (float) $i->absent_deduction), 2);

            $je = $this->recordPayrollJournal($payroll, $totalEarned, $totalAdvance, $totalNet, $paymentAccountId);

            foreach ($items as $item) {
                $item->update([
                    'payment_status' => 'paid',
                    'payment_method' => $paymentMethod,
                ]);

                // Recover advance: reduce the balance and log it on the advance
                // ledger (linked to this payroll JE — no separate cash entry).
                if ((float) $item->advance_deduction > 0 && $item->employee) {
                    $deduction = (float) $item->advance_deduction;
                    $balance = (float) $item->employee->advance_balance;
                    
                    if ($deduction > $balance + 0.01) {
                        throw new \Exception("Cannot recover " . currency_symbol() . " {$deduction} for {$item->employee->name}. Their current advance balance is only " . currency_symbol() . " {$balance}. Please unapprove and edit their salary line to fix the deduction.");
                    }
                    
                    $item->employee->decrement('advance_balance', $deduction);
                    \Modules\Employee\Models\EmployeeAdvance::create([
                        'employee_id'      => $item->employee_id,
                        'advance_number'   => 'PAYREC-' . $payroll->payroll_number . '-' . $item->id,
                        'type'             => 'recovery',
                        'amount'           => (float) $item->advance_deduction,
                        'advance_date'     => now()->toDateString(),
                        'reference'        => $payroll->payroll_number,
                        'note'             => 'Advance recovered from salary',
                        'journal_entry_id' => $je?->id,
                        'created_by'       => Auth::id(),
                    ]);
                }
            }

            // Mark the whole payroll paid only when nothing is left unpaid.
            $remaining = $payroll->items()->where('payment_status', '!=', 'paid')->count();
            $payroll->update([
                'status'           => $remaining === 0 ? 'paid' : $payroll->status,
                'journal_entry_id' => $payroll->journal_entry_id ?: $je?->id,
            ]);
        });
    }

    /**
     * Undo a single paid salary line so a wrong amount can be corrected and
     * re-paid. Mirrors payItems in reverse for just this item:
     *   - posts a reversing journal entry (CR salary expense, DR advances, DR cash)
     *   - restores the employee's advance balance and removes its recovery ledger row
     *   - flips the line back to approved/unpaid and reopens the payroll if needed
     * After undoing, unapprove → edit → approve → pay with the correct figures.
     */
    public function undoItemPayment(PayrollItem $item): PayrollItem
    {
        if ($item->payment_status !== 'paid') {
            throw new \RuntimeException('Only paid salaries can have their payment undone.');
        }

        $item->loadMissing('employee', 'payroll');
        $payroll = $item->payroll;

        DB::transaction(function () use ($item, $payroll) {
            $net = (float) $item->net_salary;
            $advance = (float) $item->advance_deduction;
            $earned = round((float) $item->gross_salary - (float) $item->absent_deduction, 2);

            // Reverse the payment journal for this line only (other lines paid
            // in the same batch keep their accounting untouched).
            $this->recordPayrollReversalJournal($payroll, $item, $earned, $advance, $net);

            // Give back the recovered advance and drop its ledger row.
            if ($advance > 0 && $item->employee) {
                $item->employee->increment('advance_balance', $advance);
                \Modules\Employee\Models\EmployeeAdvance::where('employee_id', $item->employee_id)
                    ->where('advance_number', 'PAYREC-' . $payroll->payroll_number . '-' . $item->id)
                    ->delete();
            }

            $item->update([
                'payment_status' => 'pending',
                'payment_method' => null,
            ]);

            // The payroll is no longer fully paid.
            if ($payroll->status === 'paid') {
                $payroll->update(['status' => 'approved']);
            }
        });

        return $item->fresh();
    }

    private function recordPayrollReversalJournal(Payroll $payroll, PayrollItem $item, float $earned, float $advance, float $net): ?\Modules\Accounting\Models\JournalEntry
    {
        try {
            $account = fn ($code) => \Modules\Accounting\Models\Account::where('account_code', $code)->value('id');

            // Cash account from the method the line was paid with (payItems
            // stores account_type strings; bank_transfer/card land on 1004).
            $methodToCode = ['cash' => '1001', 'mobile_banking' => '1002'];
            $cashAccountId = $account($methodToCode[$item->payment_method] ?? '1004');

            $employeeName = $item->employee->name ?? ('employee #' . $item->employee_id);
            $lines = [
                ['account_id' => $cashAccountId, 'debit_amount' => $net, 'credit_amount' => 0, 'description' => "Salary payment undone ({$employeeName}): {$payroll->payroll_number}"],
            ];
            if ($advance > 0) {
                $lines[] = ['account_id' => $account('1015'), 'debit_amount' => $advance, 'credit_amount' => 0, 'description' => "Advance recovery reversed ({$employeeName}): {$payroll->payroll_number}"];
            }
            $lines[] = ['account_id' => $account('5110'), 'debit_amount' => 0, 'credit_amount' => $earned, 'description' => "Salary expense reversed ({$employeeName}): {$payroll->payroll_number}"];

            $journalService = app(\Modules\Accounting\Services\JournalEntryService::class);

            return $journalService->createFromSource(
                'payroll', $payroll->id, $lines,
                "Salary payment undone ({$employeeName}): {$payroll->payroll_number}",
                $payroll->payroll_number,
                now()->toDateString(),
            );
        } catch (\Throwable $e) {
            \Log::warning("Failed to create reversal journal entry for payroll {$payroll->payroll_number}: {$e->getMessage()}");
            return null;
        }
    }

    private function recordPayrollJournal(Payroll $payroll, float $earned, float $advance, float $net, ?int $paymentAccountId): ?\Modules\Accounting\Models\JournalEntry
    {
        try {
            $account = fn ($code) => \Modules\Accounting\Models\Account::where('account_code', $code)->value('id');
            $salaryAccountId = $account('5110');

            $typeToCode = ['cash' => '1001', 'mobile_banking' => '1002', 'bank' => '1004', 'card' => '1004'];
            $cashCode = '1001';
            if ($paymentAccountId) {
                $type = \Modules\Payment\Models\PaymentAccount::where('id', $paymentAccountId)->value('account_type');
                $cashCode = $typeToCode[$type ?? 'cash'] ?? '1001';
            }
            $cashAccountId = $account($cashCode);

            $lines = [
                ['account_id' => $salaryAccountId, 'debit_amount' => $earned, 'credit_amount' => 0, 'description' => "Salary: {$payroll->payroll_number}"],
            ];
            if ($advance > 0) {
                $lines[] = ['account_id' => $account('1015'), 'debit_amount' => 0, 'credit_amount' => $advance, 'description' => "Advance recovered: {$payroll->payroll_number}"];
            }
            $lines[] = ['account_id' => $cashAccountId, 'debit_amount' => 0, 'credit_amount' => $net, 'description' => "Net paid: {$payroll->payroll_number}"];

            $journalService = app(\Modules\Accounting\Services\JournalEntryService::class);

            return $journalService->createFromSource(
                'payroll', $payroll->id, $lines,
                "Payroll payment: {$payroll->payroll_number}",
                $payroll->payroll_number,
                now()->toDateString(),
            );
        } catch (\Throwable $e) {
            \Log::warning("Failed to create journal entry for payroll {$payroll->payroll_number}: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Cancel a paid payroll — reverse payments, advance deductions, and journal entry.
     */
    public function cancelPayroll(Payroll $payroll): void
    {
        if (!in_array($payroll->status, ['draft', 'approved', 'paid'])) {
            throw new \Exception('This payroll cannot be cancelled.');
        }

        DB::transaction(function () use ($payroll) {
            // If paid, reverse advance deductions and void journal
            if ($payroll->status === 'paid') {
                $payroll->load('items');

                foreach ($payroll->items as $item) {
                    if ($item->advance_deduction > 0) {
                        Employee::where('id', $item->employee_id)
                            ->increment('advance_balance', $item->advance_deduction);
                    }
                }

                // Remove the advance-recovery ledger rows created at payment time.
                \Modules\Employee\Models\EmployeeAdvance::where('reference', $payroll->payroll_number)
                    ->where('advance_number', 'like', 'PAYREC-' . $payroll->payroll_number . '-%')
                    ->delete();

                $payroll->items()->update([
                    'payment_status' => 'pending',
                    'payment_method' => null,
                ]);

                // Void journal entry
                if ($payroll->journal_entry_id) {
                    try {
                        $accountingService = app(\Modules\Accounting\Services\AccountingIntegrationService::class);
                        $accountingService->voidJournalEntry('payroll', $payroll->id);
                    } catch (\Throwable $e) {
                        \Log::warning("Failed to void journal entry for payroll cancel: {$e->getMessage()}");
                    }
                }
            }

            $payroll->update(['status' => 'cancelled']);
        });
    }

    public function deletePayroll(Payroll $payroll): void
    {
        if (!in_array($payroll->status, ['draft', 'cancelled'])) {
            throw new \Exception('Only draft or cancelled payrolls can be deleted.');
        }

        // A draft payroll can still hold individually-paid lines (per-item pay
        // doesn't change the header status). Deleting those would orphan their
        // posted journal entries and advance recoveries.
        if ($payroll->items()->where('payment_status', 'paid')->exists()) {
            throw new \Exception('This payroll has paid salaries. Undo those payments or cancel the payroll first.');
        }

        DB::transaction(function () use ($payroll) {
            $payroll->items()->delete();
            $payroll->delete();
        });
    }

    // Salary Structure CRUD
    public function listStructures(): Collection
    {
        return SalaryStructure::withCount('components')->get();
    }

    public function findStructure(int $id): SalaryStructure
    {
        return SalaryStructure::with('components')->findOrFail($id);
    }

    public function createStructure(array $data, array $components = []): SalaryStructure
    {
        return DB::transaction(function () use ($data, $components) {
            $structure = SalaryStructure::create($data);

            foreach ($components as $i => $comp) {
                $comp['salary_structure_id'] = $structure->id;
                $comp['sort_order'] = $i;
                SalaryStructureComponent::create($comp);
            }

            return $structure->fresh('components');
        });
    }

    public function updateStructure(SalaryStructure $structure, array $data, array $components = []): SalaryStructure
    {
        return DB::transaction(function () use ($structure, $data, $components) {
            $structure->update($data);

            // Replace components
            $structure->components()->delete();
            foreach ($components as $i => $comp) {
                $comp['salary_structure_id'] = $structure->id;
                $comp['sort_order'] = $i;
                SalaryStructureComponent::create($comp);
            }

            return $structure->fresh('components');
        });
    }

    public function deleteStructure(SalaryStructure $structure): void
    {
        DB::transaction(function () use ($structure) {
            $structure->components()->delete();
            $structure->delete();
        });
    }

    public function getStats(): array
    {
        return [
            'total_payrolls' => Payroll::count(),
            'pending' => Payroll::draft()->count(),
            'total_paid' => Payroll::where('status', 'paid')->sum('total_net'),
            'this_month' => Payroll::where('month', now()->format('Y-m'))->first()?->total_net ?? 0,
        ];
    }

    private function generatePayrollNumber(): string
    {
        $year = date('Y');
        $last = Payroll::withTrashed()
            ->where('payroll_number', 'like', "PAY-{$year}-%")
            ->orderByDesc('id')
            ->value('payroll_number');

        $nextNum = 1;
        if ($last && preg_match('/PAY-\d{4}-(\d+)/', $last, $m)) {
            $nextNum = (int) $m[1] + 1;
        }

        return "PAY-{$year}-" . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
}
