<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierGroup;
use Modules\Supplier\Services\SupplierService;

class SupplierApiController extends BaseApiController
{
    public function __construct(
        private readonly SupplierService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $suppliers = $this->service->list($request->all(), $request->input('per_page', 15));

        return $this->paginatedSuccess($suppliers, 'Suppliers retrieved successfully');
    }

    public function show(int $id): JsonResponse
    {
        $supplier = Supplier::with('supplierGroup')->findOrFail($id);

        return $this->success($supplier, 'Supplier retrieved');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name'      => 'required|string|max:255',
            'contact_person'    => 'required|string|max:255',
            'phone'             => 'required|string|max:20',
            'email'             => 'nullable|email|max:255',
            'address'           => 'nullable|string|max:500',
            'bank_name'         => 'nullable|string|max:255',
            'account_number'    => 'nullable|string|max:100',
            'credit_limit'      => 'nullable|numeric|min:0',
            'payment_terms'     => 'nullable|string|max:100',
            'opening_balance'   => 'nullable|numeric|min:0',
            'supplier_group_id' => 'nullable|integer|exists:supplier_groups,id',
            'notes'             => 'nullable|string',
        ]);

        $supplier = $this->service->create($validated);

        return $this->success($supplier, 'Supplier created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'company_name'      => 'sometimes|required|string|max:255',
            'contact_person'    => 'sometimes|required|string|max:255',
            'phone'             => 'sometimes|required|string|max:20',
            'email'             => 'nullable|email|max:255',
            'address'           => 'nullable|string|max:500',
            'bank_name'         => 'nullable|string|max:255',
            'account_number'    => 'nullable|string|max:100',
            'credit_limit'      => 'nullable|numeric|min:0',
            'payment_terms'     => 'nullable|string|max:100',
            'supplier_group_id' => 'nullable|integer|exists:supplier_groups,id',
            'notes'             => 'nullable|string',
        ]);

        $supplier = $this->service->update($supplier, $validated);

        return $this->success($supplier, 'Supplier updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        $this->service->delete($supplier);

        return $this->success(null, 'Supplier deleted');
    }

    public function ledger(Request $request, int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        $filters = $request->only(['from', 'to']);
        $ledger = $this->service->getLedger($supplier, $filters);

        return $this->success($ledger, 'Supplier ledger retrieved');
    }

    public function groups(): JsonResponse
    {
        $groups = SupplierGroup::withCount('suppliers')->orderBy('name')->get();

        return $this->success($groups, 'Supplier groups retrieved');
    }

    public function storeGroup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $group = SupplierGroup::create($validated);

        return $this->success($group, 'Supplier group created', 201);
    }
}
