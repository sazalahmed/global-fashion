<?php

namespace Modules\Employee\Services;

use App\Helpers\Upload;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Modules\Employee\Models\Department;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\SalaryIncrement;

class EmployeeService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Employee::with('branch')
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('employee_id', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['branch_id'] ?? null, fn($q, $b) => $q->where('branch_id', $b))
            ->when($filters['department_id'] ?? null, fn($q, $d) => $q->where('department_id', $d))
            ->orderByDesc('created_at');

        return $query->paginate($perPage)->withQueryString();
    }

    public function find(int $id): Employee
    {
        return Employee::with('branch', 'user', 'creator')->findOrFail($id);
    }

    public function create(array $data): Employee
    {
        $data['employee_id'] = $this->generateEmployeeId();
        $data['created_by'] = Auth::id();

        if (isset($data['photo']) && $data['photo']) {
            $data['photo'] = Upload::store($data['photo'], 'employees');
        }

        return Employee::create($data);
    }

    public function update(Employee $employee, array $data): Employee
    {
        if (isset($data['photo']) && $data['photo']) {
            if ($employee->photo) {
                Upload::delete($employee->photo);
            }
            $data['photo'] = Upload::store($data['photo'], 'employees');
        } else {
            unset($data['photo']);
        }

        $employee->update($data);
        return $employee->fresh();
    }

    public function delete(Employee $employee): void
    {
        // Prevent deletion if employee has outstanding advance balance
        if ((float) $employee->advance_balance > 0) {
            throw new \RuntimeException("Cannot delete employee — outstanding advance balance of " . currency_symbol() . " " . number_format($employee->advance_balance) . " exists.");
        }

        // Prevent deletion if employee has unpaid payroll items
        $unpaidPayroll = \Modules\Payroll\Models\PayrollItem::where('employee_id', $employee->id)
            ->where('payment_status', 'pending')
            ->count();

        if ($unpaidPayroll > 0) {
            throw new \RuntimeException("Cannot delete employee — {$unpaidPayroll} unpaid payroll item(s) exist.");
        }

        if ($employee->photo) {
            Upload::delete($employee->photo);
        }
        $employee->delete();
    }

    public function adjustAdvanceBalance(Employee $employee, float $amount, string $type = 'add'): Employee
    {
        if ($type === 'add') {
            $employee->increment('advance_balance', $amount);
        } else {
            $employee->decrement('advance_balance', $amount);
        }
        return $employee->fresh();
    }

    /**
     * Apply a salary increment to an employee: bump employees.salary and
     * record an auditable SalaryIncrement row. The increment is either a
     * fixed BDT amount or a percentage of the current salary.
     */
    public function incrementSalary(Employee $employee, string $type, float $value, ?string $note = null, ?string $appliedAt = null): SalaryIncrement
    {
        return DB::transaction(function () use ($employee, $type, $value, $note, $appliedAt) {
            $employee = Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();

            $previous = (float) $employee->salary;
            $delta = $type === 'percentage' ? round($previous * ($value / 100), 2) : $value;
            $new = $previous + $delta;

            $employee->update(['salary' => $new]);

            return SalaryIncrement::create([
                'employee_id'     => $employee->id,
                'previous_salary' => $previous,
                'new_salary'      => $new,
                'increment_type'  => $type,
                'increment_value' => $value,
                'note'            => $note,
                'incremented_by'  => Auth::id(),
                'applied_at'      => $appliedAt ? Carbon::parse($appliedAt)->toDateString() : now()->toDateString(),
            ]);
        });
    }

    /**
     * Edit a past increment. previous_salary is fixed (changing it would
     * require re-walking the whole chain); new_salary is recomputed from it.
     * The employee's current salary is re-synced to the latest increment.
     */
    public function updateIncrement(SalaryIncrement $increment, array $data): SalaryIncrement
    {
        return DB::transaction(function () use ($increment, $data) {
            $previous = (float) $increment->previous_salary;
            $value = (float) $data['increment_value'];
            $delta = $data['increment_type'] === 'percentage' ? round($previous * ($value / 100), 2) : $value;

            $increment->update([
                'applied_at'      => Carbon::parse($data['applied_at'])->toDateString(),
                'increment_type'  => $data['increment_type'],
                'increment_value' => $value,
                'new_salary'      => $previous + $delta,
                'note'            => $data['note'] ?? null,
            ]);

            $this->syncSalaryToLatest($increment->employee_id);

            return $increment->fresh();
        });
    }

    /**
     * Delete an increment. If it was the most recent one, roll the employee's
     * current salary back to the next-latest increment (or this row's
     * previous_salary when no increments remain).
     */
    public function deleteIncrement(SalaryIncrement $increment): void
    {
        DB::transaction(function () use ($increment) {
            $employeeId = $increment->employee_id;
            $latest = $this->latestIncrement($employeeId);
            $wasLatest = $latest && $latest->id === $increment->id;
            $fallbackSalary = (float) $increment->previous_salary;

            $increment->delete();

            if ($wasLatest) {
                $newLatest = $this->latestIncrement($employeeId);
                Employee::whereKey($employeeId)->update([
                    'salary' => $newLatest ? $newLatest->new_salary : $fallbackSalary,
                ]);
            }
        });
    }

    private function latestIncrement(int $employeeId): ?SalaryIncrement
    {
        return SalaryIncrement::where('employee_id', $employeeId)
            ->orderByDesc('applied_at')
            ->orderByDesc('id')
            ->first();
    }

    private function syncSalaryToLatest(int $employeeId): void
    {
        $latest = $this->latestIncrement($employeeId);
        if ($latest) {
            Employee::whereKey($employeeId)->update(['salary' => $latest->new_salary]);
        }
    }

    public function getStats(): array
    {
        return [
            'total' => Employee::count(),
            'active' => Employee::where('status', 'active')->count(),
            'inactive' => Employee::where('status', 'inactive')->count(),
            'on_leave' => Employee::where('status', 'on_leave')->count(),
            'total_salary' => Employee::where('status', 'active')->sum('salary'),
        ];
    }

    public function getDepartments(): array
    {
        return Department::orderBy('sort_order')->orderBy('name')->pluck('name')->toArray();
    }

    private function generateEmployeeId(): string
    {
        $last = Employee::withTrashed()
            ->where('employee_id', 'like', 'EMP-%')
            ->orderByDesc('id')
            ->value('employee_id');

        $nextNum = 1;
        if ($last && preg_match('/EMP-(\d+)/', $last, $m)) {
            $nextNum = (int) $m[1] + 1;
        }

        return 'EMP-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
}
