<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Quotation\Models\Quotation;
use Modules\Quotation\Services\QuotationService;

class QuotationApiController extends BaseApiController
{
    public function __construct(
        private readonly QuotationService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $quotations = $this->service->list($request->all(), $request->input('per_page', 15));
        return $this->paginatedSuccess($quotations, 'Quotations retrieved successfully');
    }

    public function show(int $id): JsonResponse
    {
        $quotation = $this->service->find($id);
        return $this->success($quotation, 'Quotation retrieved');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id'     => 'nullable|integer|exists:customers,id',
            'quotation_date'  => 'required|date',
            'valid_until'     => 'required|date|after_or_equal:quotation_date',
            'discount_type'   => 'nullable|in:fixed,percentage',
            'discount_value'  => 'nullable|numeric|min:0',
            'tax_rate'        => 'nullable|numeric|min:0|max:100',
            'shipping_charge' => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string',
            'terms'           => 'nullable|string',
            'items'           => 'required|array|min:1',
            'items.*.product_id'      => 'required|integer|exists:products,id',
            'items.*.variant_id'      => 'nullable|integer',
            'items.*.quantity'        => 'required|numeric|min:0.01',
            'items.*.unit_price'      => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
        ]);

        $quotation = $this->service->create(
            collect($validated)->except('items')->toArray(),
            $validated['items'],
        );

        return $this->success($quotation->load('items'), 'Quotation created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $quotation = Quotation::findOrFail($id);

        if (!in_array($quotation->status, ['draft', 'sent'])) {
            return $this->error('Only draft or sent quotations can be updated', 422);
        }

        $validated = $request->validate([
            'customer_id'     => 'nullable|integer|exists:customers,id',
            'quotation_date'  => 'sometimes|date',
            'valid_until'     => 'sometimes|date',
            'discount_type'   => 'nullable|in:fixed,percentage',
            'discount_value'  => 'nullable|numeric|min:0',
            'tax_rate'        => 'nullable|numeric|min:0|max:100',
            'shipping_charge' => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string',
            'terms'           => 'nullable|string',
            'items'           => 'sometimes|array|min:1',
            'items.*.product_id'      => 'required_with:items|integer|exists:products,id',
            'items.*.variant_id'      => 'nullable|integer',
            'items.*.quantity'        => 'required_with:items|numeric|min:0.01',
            'items.*.unit_price'      => 'required_with:items|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
        ]);

        $quotation = $this->service->update(
            $quotation,
            collect($validated)->except('items')->toArray(),
            $validated['items'] ?? $quotation->items->toArray(),
        );

        return $this->success($quotation->load('items'), 'Quotation updated');
    }

    public function convertToSale(int $id): JsonResponse
    {
        $quotation = Quotation::findOrFail($id);

        if (!$quotation->isConvertible()) {
            return $this->error('This quotation cannot be converted to a sale', 422);
        }

        $sale = $this->service->convertToSale($quotation);
        return $this->success($sale, 'Quotation converted to sale');
    }

    public function destroy(int $id): JsonResponse
    {
        $quotation = Quotation::findOrFail($id);

        if (!in_array($quotation->status, ['draft'])) {
            return $this->error('Only draft quotations can be deleted', 422);
        }

        $quotation->items()->delete();
        $quotation->delete();

        return $this->success(null, 'Quotation deleted');
    }
}
