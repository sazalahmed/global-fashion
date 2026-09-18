<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Dashboard\Http\Requests\DashboardFilterRequest;
use Modules\Dashboard\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $service,
    ) {}

    public function index(DashboardFilterRequest $request)
    {
        $orderCounts = $this->service->getOrderCounts(
            $request->input('from_date'),
            $request->input('to_date'),
        );
        $salesTrend = $this->service->getSalesTrend(30);
        $paymentBreakdown = $this->service->getPaymentMethodBreakdown(
            $request->input('from_date'),
            $request->input('to_date'),
        );
        $topProducts = $this->service->getTopSellingProducts(
            10,
            $request->input('from_date'),
            $request->input('to_date'),
        );
        $recentSales = $this->service->getRecentSales(10);
        $recentExpenses = $this->service->getRecentExpenses(10);
        $lowStockAlerts = $this->service->getLowStockAlerts(10);

        return view('dashboard::index', compact(
            'orderCounts',
            'salesTrend',
            'paymentBreakdown',
            'topProducts',
            'recentSales',
            'recentExpenses',
            'lowStockAlerts',
        ));
    }
}
