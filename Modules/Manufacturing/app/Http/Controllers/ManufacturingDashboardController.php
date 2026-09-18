<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Manufacturing\Models\ProductionDamage;
use Modules\Manufacturing\Models\ProductionOrder;
use Modules\Manufacturing\Services\ManufacturingReportService;

class ManufacturingDashboardController extends Controller
{
    public function __construct(private ManufacturingReportService $reportService)
    {
    }

    /**
     * Display the manufacturing dashboard.
     */
    public function index()
    {
        bpAuthorize('manufacturing.view');
        $stats = $this->reportService->getDashboardStats();

        $recentOrders = ProductionOrder::with(['factory'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $recentDamages = ProductionDamage::with(['productionOrder', 'catalog', 'color', 'size'])
            ->where('compensation_status', ProductionDamage::COMP_PENDING)
            ->orderByDesc('damage_date')
            ->limit(10)
            ->get();

        return view('manufacturing::dashboard.index', compact('stats', 'recentOrders', 'recentDamages'));
    }
}
