<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Modules\Customer\Services\CustomerService;
use Modules\Dashboard\Services\DashboardService;
use Modules\Expense\Services\ExpenseService;
use Modules\Inventory\Services\InventoryService;
use Modules\Purchase\Services\PurchaseService;
use Modules\Sale\Models\Sale;
use Modules\Purchase\Models\Purchase;
use Illuminate\Support\Facades\DB;

class DashboardApiController extends BaseApiController
{
    public function __construct(
        private readonly DashboardService $service,
    ) {}

    public function index(): JsonResponse
    {
        return $this->success([
            'kpis'             => $this->service->getKpiCards(),
            'recent_sales'     => $this->service->getRecentSales(),
            'monthly_summary'  => $this->service->getMonthlySummary(),
        ], 'Dashboard data retrieved');
    }

    public function salesTrend(): JsonResponse
    {
        return $this->success($this->service->getSalesTrend(), 'Sales trend retrieved');
    }

    public function paymentBreakdown(): JsonResponse
    {
        return $this->success($this->service->getPaymentMethodBreakdown(), 'Payment breakdown retrieved');
    }

    public function topProducts(): JsonResponse
    {
        return $this->success($this->service->getTopSellingProducts(), 'Top products retrieved');
    }

    public function expenseSummary(): JsonResponse
    {
        $expenseService = app(ExpenseService::class);
        $stats = $expenseService->getStats();

        // Expense by category this month
        $byCategory = DB::table('expenses')
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->where('expenses.status', '!=', 'rejected')
            ->select('expense_categories.name as category_name', DB::raw('SUM(expenses.total_amount) as total_amount'))
            ->groupBy('expense_categories.name')
            ->orderByDesc('total_amount')
            ->get();

        $totalMonth = $byCategory->sum('total_amount');

        return $this->success([
            'stats' => $stats,
            'by_category' => $byCategory->map(fn ($c) => [
                'category_name' => $c->category_name,
                'total_amount'  => (float) $c->total_amount,
                'percentage'    => $totalMonth > 0 ? round($c->total_amount / $totalMonth * 100, 1) : 0,
            ]),
        ], 'Expense summary retrieved');
    }

    public function purchaseSummary(): JsonResponse
    {
        $purchaseService = app(PurchaseService::class);
        return $this->success($purchaseService->getStats(), 'Purchase summary retrieved');
    }

    public function cashFlow(): JsonResponse
    {
        $days = 30;
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');

            $inflow = (float) Sale::whereDate('sale_date', $date)
                ->whereNotIn('status', CustomerService::HIDDEN_SALE_STATUSES)
                ->sum('paid_amount');

            $outflow = (float) DB::table('expenses')
                ->whereDate('expense_date', $date)
                ->where('status', '!=', 'rejected')
                ->sum('total_amount');

            $data[] = [
                'date'    => $date,
                'inflow'  => $inflow,
                'outflow' => $outflow,
                'net'     => $inflow - $outflow,
            ];
        }

        return $this->success($data, 'Cash flow retrieved');
    }

    public function receivables(): JsonResponse
    {
        $hidden = CustomerService::HIDDEN_SALE_STATUSES;

        $total = (float) Sale::whereNotIn('status', $hidden)
            ->where('due_amount', '>', 0)
            ->sum('due_amount');

        $aging = [
            'current'      => (float) Sale::whereNotIn('status', $hidden)->where('due_amount', '>', 0)->where('sale_date', '>=', now()->subDays(30))->sum('due_amount'),
            'days_30'      => (float) Sale::whereNotIn('status', $hidden)->where('due_amount', '>', 0)->whereBetween('sale_date', [now()->subDays(60), now()->subDays(31)])->sum('due_amount'),
            'days_60'      => (float) Sale::whereNotIn('status', $hidden)->where('due_amount', '>', 0)->whereBetween('sale_date', [now()->subDays(90), now()->subDays(61)])->sum('due_amount'),
            'days_90_plus' => (float) Sale::whereNotIn('status', $hidden)->where('due_amount', '>', 0)->where('sale_date', '<', now()->subDays(90))->sum('due_amount'),
        ];

        return $this->success([
            'total' => $total,
            'aging' => $aging,
        ], 'Receivables retrieved');
    }

    public function payables(): JsonResponse
    {
        $total = (float) Purchase::where('status', '!=', 'cancelled')
            ->where('due_amount', '>', 0)
            ->sum('due_amount');

        $aging = [
            'current'      => (float) Purchase::where('status', '!=', 'cancelled')->where('due_amount', '>', 0)->where('po_date', '>=', now()->subDays(30))->sum('due_amount'),
            'days_30'      => (float) Purchase::where('status', '!=', 'cancelled')->where('due_amount', '>', 0)->whereBetween('po_date', [now()->subDays(60), now()->subDays(31)])->sum('due_amount'),
            'days_60'      => (float) Purchase::where('status', '!=', 'cancelled')->where('due_amount', '>', 0)->whereBetween('po_date', [now()->subDays(90), now()->subDays(61)])->sum('due_amount'),
            'days_90_plus' => (float) Purchase::where('status', '!=', 'cancelled')->where('due_amount', '>', 0)->where('po_date', '<', now()->subDays(90))->sum('due_amount'),
        ];

        return $this->success([
            'total' => $total,
            'aging' => $aging,
        ], 'Payables retrieved');
    }

    public function inventorySummary(): JsonResponse
    {
        $inventoryService = app(InventoryService::class);
        return $this->success($inventoryService->getStats(), 'Inventory summary retrieved');
    }
}
