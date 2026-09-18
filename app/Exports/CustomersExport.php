<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Customer\Models\Customer;

class CustomersExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return Customer::query()
            ->when($this->filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"))
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->latest();
    }

    public function headings(): array
    {
        return [
            'Name',
            'Phone',
            'Email',
            'Address',
            'Status',
            'Total Purchased',
            'Total Paid',
            'Due Balance',
            'Advance Balance',
        ];
    }

    public function map($customer): array
    {
        return [
            $customer->name,
            $customer->phone,
            $customer->email,
            $customer->address,
            ucfirst($customer->status),
            $customer->total_purchased,
            $customer->total_paid,
            $customer->total_purchased - $customer->total_paid,
            $customer->advance_balance ?? 0,
        ];
    }
}
