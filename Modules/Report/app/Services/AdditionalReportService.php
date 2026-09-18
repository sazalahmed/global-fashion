<?php

namespace Modules\Report\Services;

use Illuminate\Support\Facades\DB;
use Modules\Customer\Models\Customer;
use Modules\Payment\Models\Payment;
use Modules\Sale\Models\Sale;

class AdditionalReportService
{
    /**
     * Category-wise sales breakdown.
     */
    public function categorySales(array $filters = []): array
    {
        $from = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to_date'] ?? now()->toDateString();

        $data = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->whereIn('sales.status', ['confirmed', 'delivered'])
            ->whereNull('sales.deleted_at')
            ->select([
                'categories.name as category',
                DB::raw('SUM(sale_items.quantity) as items_sold'),
                DB::raw('SUM(sale_items.subtotal) as revenue'),
                DB::raw('SUM(sale_items.quantity * products.cost_price) as cost'),
                DB::raw('SUM(sale_items.subtotal) - SUM(sale_items.quantity * products.cost_price) as profit'),
            ])
            ->groupBy('categories.name')
            ->orderByDesc('revenue')
            ->get();

        return ['data' => $data, 'from' => $from, 'to' => $to];
    }

    /**
     * Monthly sales summary for a given year.
     */
    public function monthlySummary(array $filters = []): array
    {
        $year = $filters['year'] ?? now()->year;

        $data = Sale::whereYear('sale_date', $year)
            ->whereIn('status', ['confirmed', 'delivered'])
            ->selectRaw("
                MONTH(sale_date) as month,
                COUNT(*) as count,
                COALESCE(SUM(grand_total), 0) as total_sales,
                COALESCE(SUM(paid_amount), 0) as total_paid,
                COALESCE(SUM(due_amount), 0) as total_due
            ")
            ->groupByRaw('MONTH(sale_date)')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Fill all 12 months
        $months = collect();
        for ($m = 1; $m <= 12; $m++) {
            $row = $data->get($m);
            $months->push([
                'month'       => $m,
                'month_name'  => date('F', mktime(0, 0, 0, $m, 1)),
                'count'       => $row ? (int) $row->count : 0,
                'total_sales' => $row ? (float) $row->total_sales : 0,
                'total_paid'  => $row ? (float) $row->total_paid : 0,
                'total_due'   => $row ? (float) $row->total_due : 0,
            ]);
        }

        return ['data' => $months, 'year' => $year];
    }

    /**
     * Item-level detail sales report.
     */
    public function detailSales(array $filters = []): array
    {
        $from = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to_date'] ?? now()->toDateString();

        $query = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->whereIn('sales.status', ['confirmed', 'delivered'])
            ->whereNull('sales.deleted_at')
            ->when($filters['customer_id'] ?? null, fn ($q, $v) => $q->where('sales.customer_id', $v))
            ->select([
                'sales.sale_date as date',
                'sales.invoice_number as invoice_no',
                \Illuminate\Support\Facades\DB::raw("COALESCE(customers.name, sales.customer_name_snapshot, 'Walk-in Customer') as customer_name"),
                'sale_items.product_name',
                'sale_items.product_sku as sku',
                'sale_items.quantity',
                'sale_items.unit_price',
                'sale_items.discount_amount as discount',
                'sale_items.subtotal as total',
            ])
            ->orderByDesc('sales.sale_date')
            ->limit(500)
            ->get();

        return ['data' => $query, 'from' => $from, 'to' => $to];
    }

    /**
     * Receivables aging report — overdue sales grouped by age buckets.
     */
    public function receivablesAging(): array
    {
        $sales = Sale::whereIn('payment_status', ['partial', 'unpaid'])
            ->whereIn('status', ['confirmed', 'delivered'])
            ->with('customer:id,name,phone')
            ->select(['id', 'invoice_number', 'customer_id', 'sale_date', 'due_date', 'grand_total', 'paid_amount', 'due_amount'])
            ->orderBy('due_date')
            ->get();

        $buckets = ['current' => [], '1_30' => [], '31_60' => [], '61_90' => [], '90_plus' => []];

        foreach ($sales as $sale) {
            $dueDate = $sale->due_date ?? $sale->sale_date;
            $daysOverdue = max(0, now()->diffInDays($dueDate, false) * -1);

            $sale->days_overdue = $daysOverdue;

            if ($daysOverdue <= 0) {
                $buckets['current'][] = $sale;
            } elseif ($daysOverdue <= 30) {
                $buckets['1_30'][] = $sale;
            } elseif ($daysOverdue <= 60) {
                $buckets['31_60'][] = $sale;
            } elseif ($daysOverdue <= 90) {
                $buckets['61_90'][] = $sale;
            } else {
                $buckets['90_plus'][] = $sale;
            }
        }

        $totals = [];
        foreach ($buckets as $key => $items) {
            $totals[$key] = collect($items)->sum('due_amount');
        }

        return ['buckets' => $buckets, 'totals' => $totals, 'grand_total' => array_sum($totals)];
    }

    /**
     * Cash movement — received vs paid by period.
     */
    public function cashMovement(array $filters = []): array
    {
        $from = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to_date'] ?? now()->toDateString();

        $received = Payment::where('direction', 'receive')
            ->whereBetween('payment_date', [$from, $to])
            ->selectRaw("payment_method, COALESCE(SUM(amount), 0) as total")
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->toArray();

        $paid = Payment::where('direction', 'pay')
            ->whereBetween('payment_date', [$from, $to])
            ->selectRaw("payment_type, COALESCE(SUM(amount), 0) as total")
            ->groupBy('payment_type')
            ->pluck('total', 'payment_type')
            ->toArray();

        $totalReceived = array_sum($received);
        $totalPaid = array_sum($paid);

        return [
            'received'       => $received,
            'paid'           => $paid,
            'total_received' => $totalReceived,
            'total_paid'     => $totalPaid,
            'net'            => $totalReceived - $totalPaid,
            'from'           => $from,
            'to'             => $to,
        ];
    }

    /**
     * Supplier payment report.
     */
    public function supplierPayments(array $filters = []): array
    {
        $from = $filters['from_date'] ?? null;
        $to = $filters['to_date'] ?? null;

        $data = DB::table('suppliers')
            ->leftJoin('payments', function ($join) use ($from, $to) {
                $join->on('suppliers.id', '=', 'payments.party_id')
                     ->where('payments.party_type', 'supplier')
                     ->where('payments.direction', 'pay')
                     ->whereNull('payments.deleted_at');
                if ($from) $join->where('payments.payment_date', '>=', $from);
                if ($to) $join->where('payments.payment_date', '<=', $to);
            })
            ->whereNull('suppliers.deleted_at')
            ->select([
                'suppliers.id',
                'suppliers.company_name',
                'suppliers.contact_person',
                'suppliers.phone',
                'suppliers.total_purchase',
                'suppliers.total_paid as all_time_paid',
                'suppliers.due_balance',
                DB::raw('COALESCE(SUM(payments.amount), 0) as period_paid'),
                DB::raw('MAX(payments.payment_date) as last_payment_date'),
            ])
            ->groupBy('suppliers.id', 'suppliers.company_name', 'suppliers.contact_person',
                       'suppliers.phone', 'suppliers.total_purchase', 'suppliers.total_paid', 'suppliers.due_balance')
            ->orderByDesc('period_paid')
            ->get();

        return ['data' => $data, 'from' => $from, 'to' => $to];
    }
}
