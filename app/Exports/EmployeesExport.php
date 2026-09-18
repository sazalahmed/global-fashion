<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Employee\Models\Employee;

class EmployeesExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function query()
    {
        return Employee::query()->orderBy('name');
    }

    public function headings(): array
    {
        return ['Employee ID', 'Name', 'Phone', 'Email', 'Department', 'Designation', 'Join Date', 'Salary', 'Status'];
    }

    public function map($emp): array
    {
        return [
            $emp->employee_id, $emp->name, $emp->phone, $emp->email,
            $emp->department, $emp->designation,
            $emp->joining_date?->format('d M Y'), $emp->salary, ucfirst($emp->status),
        ];
    }
}
