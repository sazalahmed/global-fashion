<?php

namespace Modules\Report\Services;

use Illuminate\Support\Facades\DB;
use Modules\Expense\Models\Expense;
use Modules\Payment\Models\Payment;
use Modules\Purchase\Models\Purchase;
use Modules\PurchaseReturn\Models\PurchaseReturn;
use Modules\Sale\Models\Sale;
use Modules\SaleReturn\Models\SaleReturn;

class DTSReportService
{
    /**
     * Get complete Daily Transaction Summary for a given date.
     */
    public function getSummary(string $date): array
    {
        return [
            'date'       => $date,
            'sales'      => $this->salesSummary($date),
            'purchases'  => $this->purchaseSummary($date),
            'expenses'   => $this->expenseSummary($date),
            'received'   => $this->paymentsReceived($date),
            'paid_out'   => $this->paymentsPaidOut($date),
            'returns'    => $this->returnsSummary($date),
            'net_cash'   => $this->netCashPosition($date),
        ];
    }

    private function salesSummary(string $date): array
    {
        $sales = Sale::whereDate('sale_date', $date)
            ->whereIn('status', ['confirmed', 'delivered'])
            ->selectRaw("
                COUNT(*) as count,
                COALESCE(SUM(grand_total), 0) as total,
                COALESCE(SUM(paid_amount), 0) as paid,
                COALESCE(SUM(due_amount), 0) as due
            ")
            ->first();

        $bySource = Sale::whereDate('sale_date', $date)
            ->whereIn('status', ['confirmed', 'delivered'])
            ->selectRaw("source, COUNT(*) as count, COALESCE(SUM(grand_total), 0) as total")
            ->groupBy('source')
            ->pluck('total', 'source')
            ->toArray();

        return [
            'count'  => (int) $sales->count,
            'total'  => (float) $sales->total,
            'paid'   => (float) $sales->paid,
            'due'    => (float) $sales->due,
            'pos'    => (float) ($bySource['pos'] ?? 0),
            'store'  => (float) ($bySource['store'] ?? 0),
            'ecom'   => (float) ($bySource['ecommerce'] ?? 0),
        ];
    }

    private function purchaseSummary(string $date): array
    {
        $purchases = Purchase::whereDate('po_date', $date)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->selectRaw("
                COUNT(*) as count,
                COALESCE(SUM(grand_total), 0) as total,
                COALESCE(SUM(paid_amount), 0) as paid,
                COALESCE(SUM(due_amount), 0) as due
            ")
            ->first();

        return [
            'count' => (int) $purchases->count,
            'total' => (float) $purchases->total,
            'paid'  => (float) $purchases->paid,
            'due'   => (float) $purchases->due,
        ];
    }

    private function expenseSummary(string $date): array
    {
        $total = Expense::whereDate('expense_date', $date)
            ->whereIn('status', ['approved', 'paid'])
            ->selectRaw("COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total")
            ->first();

        $byCategory = Expense::whereDate('expense_date', $date)
            ->whereIn('status', ['approved', 'paid'])
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->selectRaw("expense_categories.name as category, COALESCE(SUM(expenses.total_amount), 0) as total")
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->limit(5)
            ->pluck('total', 'category')
            ->toArray();

        return [
            'count'       => (int) $total->count,
            'total'       => (float) $total->total,
            'by_category' => $byCategory,
        ];
    }

    private function paymentsReceived(string $date): array
    {
        $received = Payment::whereDate('payment_date', $date)
            ->where('direction', 'receive')
            ->selectRaw("
                payment_method,
                COALESCE(SUM(amount), 0) as total
            ")
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->toArray();

        $total = array_sum($received);

        return [
            'total'     => $total,
            'by_method' => $received,
        ];
    }

    private function paymentsPaidOut(string $date): array
    {
        $paid = Payment::whereDate('payment_date', $date)
            ->where('direction', 'pay')
            ->selectRaw("
                payment_type,
                COALESCE(SUM(amount), 0) as total
            ")
            ->groupBy('payment_type')
            ->pluck('total', 'payment_type')
            ->toArray();

        $total = array_sum($paid);

        return [
            'total'   => $total,
            'by_type' => $paid,
        ];
    }

    private function returnsSummary(string $date): array
    {
        $saleReturns = SaleReturn::whereDate('return_date', $date)
            ->whereIn('status', ['approved', 'completed'])
            ->sum('total_amount');

        $purchaseReturns = PurchaseReturn::whereDate('return_date', $date)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->sum('total');

        return [
            'sale_returns'     => (float) $saleReturns,
            'purchase_returns' => (float) $purchaseReturns,
        ];
    }

    private function netCashPosition(string $date): float
    {
        $received = Payment::whereDate('payment_date', $date)
            ->where('direction', 'receive')
            ->sum('amount');

        $paid = Payment::whereDate('payment_date', $date)
            ->where('direction', 'pay')
            ->sum('amount');

        $expenses = Expense::whereDate('expense_date', $date)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('total_amount');

        return (float) ($received - $paid - $expenses);
    }
}
