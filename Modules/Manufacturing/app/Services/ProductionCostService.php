<?php

namespace Modules\Manufacturing\Services;

use Illuminate\Support\Facades\DB;
use Modules\Manufacturing\Models\FabricIssuance;
use Modules\Manufacturing\Models\FabricReturn;
use Modules\Manufacturing\Models\FabricReturnItem;
use Modules\Manufacturing\Models\ProductionDamage;
use Modules\Manufacturing\Models\ProductionLot;
use Modules\Manufacturing\Models\ProductionOrder;
use Modules\Manufacturing\Models\RmWaste;

class ProductionCostService
{
    public function __construct(
        private readonly ManufacturingAccountingService $accountingService,
    ) {}

    /**
     * Calculate the provisional cost per unit for a production lot.
     *
     * Formula:
     *   fabric_cost_share = (lot_good / total_order_qty) * total_fabric_issued_cost
     *   lot_direct_cost = making_cost + delivery_charge + other_costs
     *   net_damage = lot_damage_cost - lot_compensation
     *   normal_rm_waste = lot normal RM waste cost
     *   lot_total = fabric_cost_share + lot_direct_cost + net_damage + normal_rm_waste
     *   provisional_cost_per_unit = lot_total / lot_good_units
     */
    public function calculateLotProvisionalCost(ProductionLot $lot): float
    {
        $order = $lot->productionOrder;
        $lotGood = (int) $lot->total_good;

        if ($lotGood <= 0) {
            return 0;
        }

        // Total fabric issued cost for the entire order
        $totalFabricIssuedCost = (float) FabricIssuance::where('production_order_id', $order->id)
            ->sum('total_cost');

        // Total fabric returned (good condition) cost — reduces fabric cost
        $totalFabricReturnedGoodCost = (float) FabricReturnItem::whereHas('fabricReturn', function ($q) use ($order) {
            $q->where('production_order_id', $order->id);
        })->where('condition', FabricReturnItem::CONDITION_GOOD)->sum('line_total');

        $netFabricCost = $totalFabricIssuedCost - $totalFabricReturnedGoodCost;

        // Share of fabric cost for this lot based on good units proportion
        $totalOrderQty = (int) $order->total_quantity;
        $fabricCostShare = $totalOrderQty > 0
            ? ($lotGood / $totalOrderQty) * $netFabricCost
            : 0;

        // Direct lot costs
        $lotDirectCost = (float) $lot->making_cost
            + (float) $lot->delivery_charge
            + (float) $lot->other_costs;

        // Lot damage cost minus compensation received
        $lotDamageCost = (float) ProductionDamage::where('lot_id', $lot->id)
            ->sum('total_damage_cost');
        $lotCompensation = (float) ProductionDamage::where('lot_id', $lot->id)
            ->sum('compensation_received');
        $netDamage = $lotDamageCost - $lotCompensation;

        // Normal RM waste cost for this lot
        $normalRmWasteCost = (float) RmWaste::where('lot_id', $lot->id)
            ->where('is_normal', true)
            ->sum('total_cost');

        // Total lot cost
        $lotTotal = $fabricCostShare + $lotDirectCost + $netDamage + $normalRmWasteCost;

        // Provisional cost per unit
        $provisionalCost = $lotTotal / $lotGood;

        // Update the lot record
        $lot->update(['provisional_cost_per_unit' => round($provisionalCost, 2)]);

        return round($provisionalCost, 2);
    }

    /**
     * Recalculate the running weighted average cost across all lots of an order.
     *
     * running_total = sum(lot.provisional_cost * lot.total_good) for all lots
     * running_good = sum(lot.total_good)
     * running_avg = running_total / running_good
     */
    public function calculateRunningWeightedAvg(ProductionOrder $order): float
    {
        $lots = ProductionLot::where('production_order_id', $order->id)
            ->where('total_good', '>', 0)
            ->get(['provisional_cost_per_unit', 'total_good']);

        $runningTotal = 0;
        $runningGood = 0;

        foreach ($lots as $lot) {
            $runningTotal += (float) $lot->provisional_cost_per_unit * (int) $lot->total_good;
            $runningGood += (int) $lot->total_good;
        }

        $runningAvg = $runningGood > 0 ? $runningTotal / $runningGood : 0;

        $order->update(['running_weighted_avg_cost' => round($runningAvg, 2)]);

        return round($runningAvg, 2);
    }

    /**
     * Reconcile the final cost per unit when a production order is completed.
     *
     * Aggregates all costs across the order lifecycle and calculates the true
     * final cost. If it differs from the running weighted average, creates a
     * cost adjustment journal entry.
     */
    public function reconcileFinalCost(ProductionOrder $order): float
    {
        // Total fabric issued cost
        $totalFabricIssued = (float) FabricIssuance::where('production_order_id', $order->id)
            ->sum('total_cost');

        // Total fabric returned (good condition only) cost
        $totalFabricReturnedGood = (float) FabricReturnItem::whereHas('fabricReturn', function ($q) use ($order) {
            $q->where('production_order_id', $order->id);
        })->where('condition', FabricReturnItem::CONDITION_GOOD)->sum('line_total');

        $netFabric = $totalFabricIssued - $totalFabricReturnedGood;

        // Making costs from all lots
        $totalMaking = (float) ProductionLot::where('production_order_id', $order->id)
            ->sum('making_cost');

        // Delivery costs from all lots
        $totalDelivery = (float) ProductionLot::where('production_order_id', $order->id)
            ->sum('delivery_charge');

        // Other costs from all lots
        $totalOther = (float) ProductionLot::where('production_order_id', $order->id)
            ->sum('other_costs');

        // Damage costs minus compensation
        $totalDamage = (float) ProductionDamage::where('production_order_id', $order->id)
            ->sum('total_damage_cost');
        $totalCompensation = (float) ProductionDamage::where('production_order_id', $order->id)
            ->sum('compensation_received');
        $netDamage = $totalDamage - $totalCompensation;

        // Normal RM waste cost (absorbed into production cost)
        $totalNormalRmWaste = (float) RmWaste::where('production_order_id', $order->id)
            ->where('is_normal', true)
            ->sum('total_cost');

        // Total good units
        $totalGood = (int) $order->good_quantity;

        if ($totalGood <= 0) {
            return 0;
        }

        // Grand total cost
        $grandTotal = $netFabric + $totalMaking + $totalDelivery + $totalOther
            + $netDamage + $totalNormalRmWaste;

        $finalCostPerUnit = $grandTotal / $totalGood;

        // Update production order with final figures
        $order->update([
            'total_fabric_cost'          => round($netFabric, 2),
            'total_making_cost'          => round($totalMaking, 2),
            'total_delivery_cost'        => round($totalDelivery, 2),
            'total_other_cost'           => round($totalOther, 2),
            'total_damage_cost'          => round($totalDamage, 2),
            'total_compensation'         => round($totalCompensation, 2),
            'total_rm_waste_cost'        => round($totalNormalRmWaste, 2),
            'grand_total'                => round($grandTotal, 2),
            'final_cost_per_unit'        => round($finalCostPerUnit, 2),
            'actual_making_cost_per_unit' => round($totalMaking / $totalGood, 2),
        ]);

        // Create adjustment journal entry if final differs from running avg
        $runningAvg = (float) $order->running_weighted_avg_cost;
        if ($runningAvg > 0) {
            $totalRunningCost = $runningAvg * $totalGood;
            $adjustment = $grandTotal - $totalRunningCost;

            if (abs($adjustment) > 0.01) {
                $this->accountingService->recordCostAdjustment(
                    $order->id,
                    $order->po_number,
                    round($adjustment, 2),
                    now()->toDateString(),
                );
            }
        }

        return round($finalCostPerUnit, 2);
    }
}
