<?php

namespace Modules\Manufacturing\Services;

use Illuminate\Support\Facades\DB;
use Modules\Manufacturing\Models\Factory;
use Modules\Manufacturing\Models\ProductionOrder;
use Modules\Manufacturing\Models\ProductionDamage;
use Modules\Manufacturing\Models\RmStock;
use Modules\Manufacturing\Models\RmWaste;
use Modules\Manufacturing\Models\ProductWaste;
use Modules\Manufacturing\Models\RmPurchaseOrder;
use Modules\Manufacturing\Models\RawMaterial;

class ManufacturingReportService
{
    /**
     * Get manufacturing dashboard statistics.
     */
    public function getDashboardStats(): array
    {
        $currentMonth = now()->startOfMonth()->toDateString();
        $currentMonthEnd = now()->endOfMonth()->toDateString();

        $totalFactories = Factory::count();
        $activeFactories = Factory::where('is_active', true)->count();

        $totalProductionOrders = ProductionOrder::count();
        $activeOrders = ProductionOrder::whereIn('status', [
            ProductionOrder::STATUS_APPROVED,
            ProductionOrder::STATUS_IN_PROGRESS,
            ProductionOrder::STATUS_PARTIAL_DELIVERED,
        ])->count();
        $completedOrders = ProductionOrder::where('status', ProductionOrder::STATUS_COMPLETED)->count();

        $totalRmPurchaseOrders = RmPurchaseOrder::count();
        $pendingRmPos = RmPurchaseOrder::whereIn('status', [
            RmPurchaseOrder::STATUS_DRAFT,
            RmPurchaseOrder::STATUS_PENDING,
            RmPurchaseOrder::STATUS_APPROVED,
            RmPurchaseOrder::STATUS_PARTIAL_RECEIVED,
        ])->count();

        $totalRawMaterials = RawMaterial::where('is_active', true)->count();
        $lowStockCount = RmStock::lowStock()->count();

        $totalDamageCost = ProductionDamage::sum('total_damage_cost');
        $totalCompensationReceived = ProductionDamage::sum('compensation_received');

        $totalRmWasteCost = RmWaste::sum('total_cost');
        $totalProductWasteCost = ProductWaste::sum('total_cost');
        $totalWasteCost = $totalRmWasteCost + $totalProductWasteCost;

        $monthlyFabricCost = ProductionOrder::whereBetween('order_date', [$currentMonth, $currentMonthEnd])
            ->sum('total_fabric_cost');
        $monthlyMakingCost = ProductionOrder::whereBetween('order_date', [$currentMonth, $currentMonthEnd])
            ->sum('total_making_cost');

        // Monthly orders chart - last 6 months
        $monthlyOrdersChart = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd = now()->subMonths($i)->endOfMonth();
            $monthlyOrdersChart[] = [
                'month' => $monthStart->format('M Y'),
                'count' => ProductionOrder::whereBetween('order_date', [
                    $monthStart->toDateString(),
                    $monthEnd->toDateString(),
                ])->count(),
                'completed' => ProductionOrder::where('status', ProductionOrder::STATUS_COMPLETED)
                    ->whereBetween('order_date', [
                        $monthStart->toDateString(),
                        $monthEnd->toDateString(),
                    ])->count(),
            ];
        }

        return [
            'total_factories' => $totalFactories,
            'active_factories' => $activeFactories,
            'total_production_orders' => $totalProductionOrders,
            'active_orders' => $activeOrders,
            'completed_orders' => $completedOrders,
            'total_rm_purchase_orders' => $totalRmPurchaseOrders,
            'pending_rm_pos' => $pendingRmPos,
            'total_raw_materials' => $totalRawMaterials,
            'low_stock_count' => $lowStockCount,
            'total_damage_cost' => $totalDamageCost,
            'total_compensation_received' => $totalCompensationReceived,
            'total_waste_cost' => $totalWasteCost,
            'total_fabric_cost' => $monthlyFabricCost,
            'total_making_cost' => $monthlyMakingCost,
            'monthly_orders_chart' => $monthlyOrdersChart,
        ];
    }

    /**
     * RM stock report with filters.
     */
    public function getRmStockReport(array $filters): array
    {
        $query = RmStock::with(['rawMaterial'])
            ->whereHas('rawMaterial', function ($q) {
                $q->where('is_active', true);
            });

        if (!empty($filters['category'])) {
            $query->whereHas('rawMaterial', function ($q) use ($filters) {
                $q->where('category', $filters['category']);
            });
        }

        if (!empty($filters['low_stock_only'])) {
            $query->lowStock();
        }

        $stocks = $query->get()->map(function ($stock) {
            $totalValue = (float) $stock->quantity * (float) $stock->avg_cost;
            $isLowStock = $stock->rawMaterial && (float) $stock->quantity <= (float) $stock->rawMaterial->reorder_level;

            return (object) [
                'id' => $stock->id,
                'material_code' => $stock->rawMaterial->code ?? '--',
                'material_name' => $stock->rawMaterial->name ?? '--',
                'category' => $stock->rawMaterial->category ?? '--',
                'unit' => $stock->rawMaterial->unit ?? '--',
                'quantity' => $stock->quantity,
                'avg_cost' => $stock->avg_cost,
                'last_cost' => $stock->last_cost,
                'total_value' => $totalValue,
                'is_low_stock' => $isLowStock,
                'reorder_level' => $stock->rawMaterial->reorder_level ?? 0,
            ];
        });

        $totalStockValue = $stocks->sum('total_value');

        return [
            'stocks' => $stocks,
            'total_stock_value' => $totalStockValue,
        ];
    }

    /**
     * Production report with filters.
     */
    public function getProductionReport(array $filters): array
    {
        $query = ProductionOrder::with(['factory'])
            ->whereNull('deleted_at');

        if (!empty($filters['factory_id'])) {
            $query->where('factory_id', $filters['factory_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from_date'])) {
            $query->where('order_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->where('order_date', '<=', $filters['to_date']);
        }

        $orders = $query->orderByDesc('order_date')->get()->map(function ($order) {
            $damageRate = $order->received_quantity > 0
                ? round(($order->damaged_quantity / $order->received_quantity) * 100, 2)
                : 0;
            $completionRate = $order->total_quantity > 0
                ? round(($order->received_quantity / $order->total_quantity) * 100, 2)
                : 0;

            return (object) [
                'id' => $order->id,
                'po_number' => $order->po_number,
                'factory_name' => $order->factory->name ?? '--',
                'order_date' => $order->order_date,
                'total_quantity' => $order->total_quantity,
                'received_quantity' => $order->received_quantity,
                'good_quantity' => $order->good_quantity,
                'damaged_quantity' => $order->damaged_quantity,
                'wasted_quantity' => $order->wasted_quantity,
                'damage_rate' => $damageRate,
                'completion_rate' => $completionRate,
                'status' => $order->status,
            ];
        });

        return [
            'orders' => $orders,
            'summary' => [
                'total_ordered' => $orders->sum('total_quantity'),
                'total_received' => $orders->sum('received_quantity'),
                'total_good' => $orders->sum('good_quantity'),
                'total_damaged' => $orders->sum('damaged_quantity'),
                'total_wasted' => $orders->sum('wasted_quantity'),
                'avg_damage_rate' => $orders->count() > 0
                    ? round($orders->avg('damage_rate'), 2)
                    : 0,
            ],
        ];
    }

    /**
     * Damage report with filters.
     */
    public function getDamageReport(array $filters): array
    {
        $query = ProductionDamage::with(['productionOrder', 'lot', 'catalog', 'color', 'size', 'product']);

        if (!empty($filters['factory_id'])) {
            $query->whereHas('productionOrder', function ($q) use ($filters) {
                $q->where('factory_id', $filters['factory_id']);
            });
        }

        if (!empty($filters['damage_type'])) {
            $query->where('damage_type', $filters['damage_type']);
        }

        if (!empty($filters['responsibility'])) {
            $query->where('responsibility', $filters['responsibility']);
        }

        if (!empty($filters['compensation_status'])) {
            $query->where('compensation_status', $filters['compensation_status']);
        }

        if (!empty($filters['from_date'])) {
            $query->where('damage_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->where('damage_date', '<=', $filters['to_date']);
        }

        $damages = $query->orderByDesc('damage_date')->get();

        $totalDamageCost = $damages->sum('total_damage_cost');
        $pendingCompensation = $damages->where('compensation_status', ProductionDamage::COMP_PENDING)
            ->sum('compensation_amount');
        $receivedCompensation = $damages->sum('compensation_received');

        return [
            'damages' => $damages,
            'summary' => [
                'total_damage_cost' => $totalDamageCost,
                'pending_compensation' => $pendingCompensation,
                'received_compensation' => $receivedCompensation,
            ],
        ];
    }

    /**
     * Waste report with filters.
     */
    public function getWasteReport(array $filters): array
    {
        $rmQuery = RmWaste::with(['productionOrder', 'lot', 'rawMaterial']);
        $productQuery = ProductWaste::with(['productionOrder', 'lot', 'catalog', 'color', 'size']);

        if (!empty($filters['from_date'])) {
            $rmQuery->where('waste_date', '>=', $filters['from_date']);
            $productQuery->where('waste_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $rmQuery->where('waste_date', '<=', $filters['to_date']);
            $productQuery->where('waste_date', '<=', $filters['to_date']);
        }

        if (!empty($filters['waste_type']) && $filters['tab'] === 'rm') {
            $rmQuery->where('waste_type', $filters['waste_type']);
        }

        if (!empty($filters['waste_type']) && ($filters['tab'] ?? '') === 'product') {
            $productQuery->where('waste_type', $filters['waste_type']);
        }

        $rmWastes = $rmQuery->orderByDesc('waste_date')->get();
        $productWastes = $productQuery->orderByDesc('waste_date')->get();

        $totalNormalRmWaste = $rmWastes->where('is_normal', true)->sum('total_cost');
        $totalAbnormalRmWaste = $rmWastes->where('is_normal', false)->sum('total_cost');
        $totalNormalProductWaste = $productWastes->where('is_normal', true)->sum('total_cost');
        $totalAbnormalProductWaste = $productWastes->where('is_normal', false)->sum('total_cost');

        return [
            'rm_wastes' => $rmWastes,
            'product_wastes' => $productWastes,
            'summary' => [
                'total_normal_waste' => $totalNormalRmWaste + $totalNormalProductWaste,
                'total_abnormal_waste' => $totalAbnormalRmWaste + $totalAbnormalProductWaste,
                'total_normal_rm' => $totalNormalRmWaste,
                'total_abnormal_rm' => $totalAbnormalRmWaste,
                'total_normal_product' => $totalNormalProductWaste,
                'total_abnormal_product' => $totalAbnormalProductWaste,
            ],
        ];
    }

    /**
     * Cost analysis report with filters.
     */
    public function getCostAnalysisReport(array $filters): array
    {
        $query = ProductionOrder::with(['factory'])
            ->where('status', ProductionOrder::STATUS_COMPLETED)
            ->whereNull('deleted_at');

        if (!empty($filters['factory_id'])) {
            $query->where('factory_id', $filters['factory_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->where('order_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->where('order_date', '<=', $filters['to_date']);
        }

        $orders = $query->orderByDesc('order_date')->get()->map(function ($order) {
            $totalWasteCost = (float) $order->total_rm_waste_cost
                + (float) $order->total_rm_waste_abnormal_cost
                + (float) $order->total_product_waste_cost
                + (float) $order->total_product_waste_abnormal_cost;
            $netDamageCost = (float) $order->total_damage_cost - (float) $order->total_compensation;

            return (object) [
                'id' => $order->id,
                'po_number' => $order->po_number,
                'factory_name' => $order->factory->name ?? '--',
                'total_quantity' => $order->total_quantity,
                'good_quantity' => $order->good_quantity,
                'total_fabric_cost' => (float) $order->total_fabric_cost,
                'total_making_cost' => (float) $order->total_making_cost,
                'total_delivery_cost' => (float) $order->total_delivery_cost,
                'total_other_cost' => (float) $order->total_other_cost,
                'net_damage_cost' => $netDamageCost,
                'total_waste_cost' => $totalWasteCost,
                'grand_total' => (float) $order->grand_total,
                'final_cost_per_unit' => (float) $order->final_cost_per_unit,
            ];
        });

        // Factory comparison: avg cost per unit by factory
        $factoryComparison = $orders->groupBy('factory_name')->map(function ($factoryOrders, $factoryName) {
            $avgCostPerUnit = $factoryOrders->avg('final_cost_per_unit');
            $totalOrders = $factoryOrders->count();
            $totalGoodQty = $factoryOrders->sum('good_quantity');
            $totalGrandCost = $factoryOrders->sum('grand_total');

            return (object) [
                'factory_name' => $factoryName,
                'total_orders' => $totalOrders,
                'total_good_qty' => $totalGoodQty,
                'total_cost' => $totalGrandCost,
                'avg_cost_per_unit' => round($avgCostPerUnit, 2),
            ];
        })->values();

        return [
            'orders' => $orders,
            'factory_comparison' => $factoryComparison,
        ];
    }
}
