<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Purchase\Models\Purchase;
use Modules\Purchase\Models\GoodsReceiveNote;
use Modules\Purchase\Services\PurchaseService;
use Modules\Supplier\Models\Supplier;
use Modules\Product\Models\Product;

class PurchaseApiController extends BaseApiController
{
    public function __construct(
        private readonly PurchaseService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $purchases = $this->service->list($request->all(), $request->input('per_page', 15));
        return $this->paginatedSuccess($purchases, 'Purchases retrieved successfully');
    }

    public function show(int $id): JsonResponse
    {
        $purchase = Purchase::with(['supplier', 'branch', 'items.product', 'grns.items', 'createdBy', 'approvedBy'])
            ->findOrFail($id);
        return $this->success($purchase, 'Purchase retrieved');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id'       => 'required|integer|exists:suppliers,id',
            'po_date'           => 'required|date',
            'expected_delivery' => 'nullable|date',
            'payment_terms'     => 'nullable|string|max:255',
            'discount_amount'   => 'nullable|numeric|min:0',
            'tax_amount'        => 'nullable|numeric|min:0',
            'shipping_cost'     => 'nullable|numeric|min:0',
            'notes'             => 'nullable|string',
            'items'             => 'required|array|min:1',
            'items.*.product_id'      => 'required|integer|exists:products,id',
            'items.*.variant_id'      => 'nullable|integer',
            'items.*.quantity'        => 'required|numeric|min:0.01',
            'items.*.unit_price'      => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
        ]);

        $purchase = $this->service->create($validated);
        return $this->success($purchase->load('items.product'), 'Purchase order created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $purchase = Purchase::findOrFail($id);

        if (!in_array($purchase->status, ['draft', 'pending'])) {
            return $this->error('Only draft or pending purchases can be updated', 422);
        }

        $validated = $request->validate([
            'supplier_id'       => 'sometimes|integer|exists:suppliers,id',
            'po_date'           => 'sometimes|date',
            'expected_delivery' => 'nullable|date',
            'payment_terms'     => 'nullable|string|max:255',
            'discount_amount'   => 'nullable|numeric|min:0',
            'tax_amount'        => 'nullable|numeric|min:0',
            'shipping_cost'     => 'nullable|numeric|min:0',
            'notes'             => 'nullable|string',
            'items'             => 'sometimes|array|min:1',
            'items.*.product_id'      => 'required_with:items|integer|exists:products,id',
            'items.*.variant_id'      => 'nullable|integer',
            'items.*.quantity'        => 'required_with:items|numeric|min:0.01',
            'items.*.unit_price'      => 'required_with:items|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
        ]);

        $purchase = $this->service->update($purchase, $validated);
        return $this->success($purchase->load('items.product'), 'Purchase order updated');
    }

    public function approve(int $id): JsonResponse
    {
        $purchase = Purchase::findOrFail($id);

        if (!$purchase->canBeApproved()) {
            return $this->error('This purchase cannot be approved', 422);
        }

        $purchase = $this->service->approve($purchase, auth()->id());
        return $this->success($purchase, 'Purchase order approved');
    }

    public function cancel(int $id): JsonResponse
    {
        $purchase = Purchase::findOrFail($id);

        if (!$purchase->canBeCancelled()) {
            return $this->error('This purchase cannot be cancelled', 422);
        }

        $purchase = $this->service->cancel($purchase);
        return $this->success($purchase, 'Purchase order cancelled');
    }

    public function grnList(int $id): JsonResponse
    {
        $grns = GoodsReceiveNote::with('items')
            ->where('purchase_id', $id)
            ->latest('received_date')
            ->get();

        return $this->success($grns, 'GRNs retrieved');
    }

    public function createGrn(Request $request, int $id): JsonResponse
    {
        $purchase = Purchase::findOrFail($id);

        if (!$purchase->canBeReceived()) {
            return $this->error('This purchase cannot receive goods', 422);
        }

        $validated = $request->validate([
            'received_date'            => 'required|date',
            'notes'                    => 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.purchase_item_id' => 'required|integer',
            'items.*.quantity_received' => 'required|numeric|min:0.01',
            'items.*.quantity_accepted' => 'nullable|numeric|min:0',
        ]);

        $grn = GoodsReceiveNote::create([
            'purchase_id'  => $id,
            'grn_number'   => 'GRN-' . now()->format('Ym') . '-' . str_pad(GoodsReceiveNote::whereYear('created_at', now()->year)->count() + 1, 4, '0', STR_PAD_LEFT),
            'received_date' => $validated['received_date'],
            'branch_id'    => $purchase->branch_id,
            'received_by'  => auth()->id(),
            'status'       => 'received',
            'notes'        => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $grn->items()->create([
                'purchase_item_id'  => $item['purchase_item_id'],
                'quantity_received' => $item['quantity_received'],
                'quantity_accepted' => $item['quantity_accepted'] ?? $item['quantity_received'],
            ]);
        }

        return $this->success($grn->load('items'), 'Goods received successfully', 201);
    }

    public function formOptions(): JsonResponse
    {
        return $this->success([
            'suppliers'  => Supplier::active()->ordered()->get(['id', 'company_name', 'contact_person', 'phone']),
            'products'   => Product::where('status', 'active')->orderBy('name')->get(['id', 'name', 'sku', 'cost_price']),
        ], 'Form options retrieved');
    }
}
