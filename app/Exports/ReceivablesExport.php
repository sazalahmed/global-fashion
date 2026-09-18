<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Sale\Models\Sale;

class ReceivablesExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function query()
    {
        return Sale::query()
            ->with('customer:id,name,phone')
            ->whereIn('payment_status', ['partial', 'unpaid'])
            ->whereIn('status', ['confirmed', 'delivered'])
            ->orderBy('due_date');
    }

    public function headings(): array
    {
        return ['Customer', 'Phone', 'Invoice #', 'Sale Date', 'Due Date', 'Total', 'Paid', 'Due', 'Days Overdue'];
    }

    public function map($sale): array
    {
        $dueDate = $sale->due_date ?? $sale->sale_date;
        $daysOverdue = max(0, now()->diffInDays($dueDate, false) * -1);

        return [
            $sale->customer_display_name, $sale->customer?->phone ?? $sale->customer_phone_snapshot,
            $sale->invoice_number, $sale->sale_date->format('d M Y'),
            $dueDate->format('d M Y'), $sale->grand_total, $sale->paid_amount,
            $sale->due_amount, $daysOverdue,
        ];
    }
}
