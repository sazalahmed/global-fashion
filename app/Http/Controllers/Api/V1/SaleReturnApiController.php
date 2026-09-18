<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\SaleReturn\Services\SaleReturnService;

class SaleReturnApiController extends BaseApiController
{
    public function __construct(
        private readonly SaleReturnService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $returns = $this->service->list($request->all(), $request->input('per_page', 15));

        return $this->paginatedSuccess($returns, 'Sale returns retrieved successfully');
    }

    public function show(int $id): JsonResponse
    {
        $return = $this->service->find($id);

        return $this->success([
            'id'            => $return->id,
            'return_number' => $return->return_number,
            'sale_id'       => $return->sale_id,
            'invoice_number' => $return->sale?->invoice_number,
            'customer'      => $return->customer ? [
                'id'   => $return->customer->id,
                'name' => $return->customer->name,
                'phone' => $return->customer->phone,
            ] : null,
            'return_date'   => $return->return_date,
            'reason'        => $return->reason,
            'subtotal'      => (float) $return->subtotal,
            'tax_amount'    => (float) $return->tax_amount,
            'total_amount'  => (float) $return->total_amount,
            'status'        => $return->status,
            'refund_method' => $return->refund_method,
            'notes'         => $return->notes,
            'items'         => $return->items->map(fn ($item) => [
                'id'           => $item->id,
                'product_id'   => $item->product_id,
                'product_name' => $item->product?->name,
                'variant_id'   => $item->variant_id,
                'quantity'     => (int) $item->quantity,
                'unit_price'   => (float) $item->unit_price,
                'tax_amount'   => (float) $item->tax_amount,
                'subtotal'     => (float) $item->subtotal,
                'condition'    => $item->condition,
            ]),
            'created_by'    => $return->creator ? [
                'id'   => $return->creator->id,
                'name' => $return->creator->name,
            ] : null,
            'created_at'    => $return->created_at?->toIso8601String(),
        ], 'Sale return retrieved');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sale_id'       => 'required|integer|exists:sales,id',
            'return_date'   => 'required|date',
            'reason'        => 'required|string|max:500',
            'refund_method' => 'nullable|in:cash,credit_note',
            'notes'         => 'nullable|string',
            'items'         => 'required|array|min:1',
            'items.*.product_id'   => 'required|integer|exists:products,id',
            'items.*.variant_id'   => 'nullable|integer',
            'items.*.sale_item_id' => 'nullable|integer',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.tax_amount'   => 'nullable|numeric|min:0',
            'items.*.condition'    => 'nullable|in:good,damaged',
        ]);

        $sale = \Modules\Sale\Models\Sale::findOrFail($validated['sale_id']);

        $return = $this->service->create(
            array_merge($validated, [
                'customer_id' => $sale->customer_id,
                'branch_id'   => $sale->branch_id ?? auth()->user()->branch_id,
            ]),
            $validated['items'],
        );

        return $this->success($return->load('items.product'), 'Sale return created successfully', 201);
    }
}
