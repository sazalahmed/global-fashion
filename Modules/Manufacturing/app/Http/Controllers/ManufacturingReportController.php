<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Manufacturing\Models\Factory;
use Modules\Manufacturing\Models\ProductionDamage;
use Modules\Manufacturing\Models\ProductionOrder;
use Modules\Manufacturing\Models\RawMaterial;
use Modules\Manufacturing\Models\RmWaste;
use Modules\Manufacturing\Models\ProductWaste;
use Modules\Manufacturing\Services\ManufacturingReportService;

class ManufacturingReportController extends Controller
{
    public function __construct(private ManufacturingReportService $reportService)
    {
    }

    /**
     * RM Stock Report.
     */
    public function rmStock(Request $request)
    {
        bpAuthorize('manufacturing.view');
        $filters = $request->only(['category', 'low_stock_only']);
        $data = $this->reportService->getRmStockReport($filters);

        $categories = RawMaterial::getCategories();

        return view('manufacturing::reports.rm-stock', [
            'stocks' => $data['stocks'],
            'totalStockValue' => $data['total_stock_value'],
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    /**
     * Production Report.
     */
    public function production(Request $request)
    {
        bpAuthorize('manufacturing.view');
        $filters = $request->only(['factory_id', 'status', 'from_date', 'to_date']);
        $data = $this->reportService->getProductionReport($filters);

        $factories = Factory::active()->ordered()->get();
        $statuses = ProductionOrder::getStatuses();

        return view('manufacturing::reports.production', [
            'orders' => $data['orders'],
            'summary' => $data['summary'],
            'factories' => $factories,
            'statuses' => $statuses,
            'filters' => $filters,
        ]);
    }

    /**
     * Damage Report.
     */
    public function damage(Request $request)
    {
        bpAuthorize('manufacturing.view');
        $filters = $request->only(['factory_id', 'damage_type', 'responsibility', 'compensation_status', 'from_date', 'to_date']);
        $data = $this->reportService->getDamageReport($filters);

        $factories = Factory::active()->ordered()->get();
        $damageTypes = ProductionDamage::getDamageTypes();
        $responsibilities = ProductionDamage::getResponsibilities();
        $compensationStatuses = ProductionDamage::getCompensationStatuses();

        return view('manufacturing::reports.damage', [
            'damages' => $data['damages'],
            'summary' => $data['summary'],
            'factories' => $factories,
            'damageTypes' => $damageTypes,
            'responsibilities' => $responsibilities,
            'compensationStatuses' => $compensationStatuses,
            'filters' => $filters,
        ]);
    }

    /**
     * Waste Report.
     */
    public function waste(Request $request)
    {
        bpAuthorize('manufacturing.view');
        $filters = $request->only(['tab', 'waste_type', 'from_date', 'to_date']);
        $filters['tab'] = $filters['tab'] ?? 'rm';
        $data = $this->reportService->getWasteReport($filters);

        $rmWasteTypes = RmWaste::getWasteTypes();
        $productWasteTypes = ProductWaste::getWasteTypes();

        return view('manufacturing::reports.waste', [
            'rmWastes' => $data['rm_wastes'],
            'productWastes' => $data['product_wastes'],
            'summary' => $data['summary'],
            'rmWasteTypes' => $rmWasteTypes,
            'productWasteTypes' => $productWasteTypes,
            'filters' => $filters,
        ]);
    }

    /**
     * Cost Analysis Report.
     */
    public function costAnalysis(Request $request)
    {
        bpAuthorize('manufacturing.view');
        $filters = $request->only(['factory_id', 'from_date', 'to_date']);
        $data = $this->reportService->getCostAnalysisReport($filters);

        $factories = Factory::active()->ordered()->get();

        return view('manufacturing::reports.cost-analysis', [
            'orders' => $data['orders'],
            'factoryComparison' => $data['factory_comparison'],
            'factories' => $factories,
            'filters' => $filters,
        ]);
    }
}
