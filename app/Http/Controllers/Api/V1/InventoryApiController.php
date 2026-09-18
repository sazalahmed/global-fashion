<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Inventory\Models\WarehouseStock;
use Modules\Inventory\Models\StockAdjustment;
use Modules\Inventory\Services\InventoryService;

class InventoryApiController extends BaseApiController
{
    public function __construct(
        private readonly InventoryService $service,
    ) {}

    public function stockLevels(Request $request): JsonResponse
    {
        $stocks = $this->service->getStockLevels($request->all(), $request->input('per_page', 20));
        return $this->paginatedSuccess($stocks, 'Stock levels retrieved');
    }

    public function productStock(int $productId): JsonResponse
    {
        $stocks = WarehouseStock::where('product_id', $productId)
            ->get()
            ->map(fn ($s) => [
                'quantity'          => (int) $s->quantity,
                'reserved_quantity' => (int) $s->reserved_quantity,
                'available'         => $s->available_quantity,
                'reorder_level'     => (int) ($s->reorder_level ?? 0),
            ]);

        return $this->success($stocks, 'Product stock retrieved');
    }

    public function adjustmentList(Request $request): JsonResponse
    {
        $adjustments = $this->service->listAdjustments($request->all(), $request->input('per_page', 15));
        return $this->paginatedSuccess($adjustments, 'Stock adjustments retrieved');
    }

    public function adjustmentShow(int $id): JsonResponse
    {
        $adjustment = $this->service->findAdjustment($id);
        return $this->success($adjustment, 'Stock adjustment retrieved');
    }

    public function adjustmentStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'         => 'required|in:addition,subtraction',
            'reason'       => 'required|string|max:500',
            'notes'        => 'nullable|string',
            'reference'    => 'nullable|string|max:255',
            'items'        => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.variant_id' => 'nullable|integer',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_cost'  => 'nullable|numeric|min:0',
            'items.*.note'       => 'nullable|string|max:255',
        ]);

        $adjustment = $this->service->createAdjustment(
            collect($validated)->except('items')->toArray(),
            $validated['items'],
        );

        return $this->success($adjustment->load('items.product'), 'Stock adjustment created', 201);
    }

    public function adjustmentApprove(int $id): JsonResponse
    {
        $adjustment = StockAdjustment::findOrFail($id);

        if ($adjustment->status !== 'draft') {
            return $this->error('Only draft adjustments can be approved', 422);
        }

        $this->service->approveAdjustment($adjustment);
        return $this->success($adjustment->fresh(), 'Stock adjustment approved');
    }

    public function lowStock(Request $request): JsonResponse
    {
        $products = $this->service->getLowStockProducts();
        return $this->success($products, 'Low stock products retrieved');
    }
}
