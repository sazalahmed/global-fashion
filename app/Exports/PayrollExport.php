<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Payroll\Models\PayrollItem;

class PayrollExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected string $month;

    public function __construct(string $month = '')
    {
        $this->month = $month;
    }

    public function query()
    {
        return PayrollItem::query()
            ->with(['employee:id,name,employee_id', 'payroll:id,month,status'])
            ->when($this->month, fn ($q) => $q->whereHas('payroll', fn ($q2) => $q2->where('month', $this->month)))
            ->orderBy('id');
    }

    public function headings(): array
    {
        return ['Employee ID', 'Employee', 'Month', 'Basic', 'Earnings', 'Deductions', 'Net Salary', 'Status'];
    }

    public function map($item): array
    {
        return [
            $item->employee?->employee_id, $item->employee?->name,
            $item->payroll?->month, $item->basic_salary, $item->total_earnings,
            $item->total_deductions, $item->net_salary, ucfirst($item->payment_status),
        ];
    }
}
