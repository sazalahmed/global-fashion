<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerGroup;
use Modules\Customer\Services\CustomerService;

class CustomerApiController extends BaseApiController
{
    public function __construct(
        private readonly CustomerService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $customers = $this->service->list($request->all(), $request->input('per_page', 15));

        return $this->paginatedSuccess($customers, 'Customers retrieved successfully');
    }

    public function show(int $id): JsonResponse
    {
        $customer = $this->service->find($id);

        return $this->success($customer, 'Customer retrieved');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'phone'             => 'required|string|max:20',
            'email'             => 'nullable|email|max:255',
            'company_name'      => 'nullable|string|max:255',
            'customer_group_id' => 'nullable|integer|exists:customer_groups,id',
            'address'           => 'nullable|string|max:500',
            'shipping_address'  => 'nullable|string|max:500',
            'credit_limit'      => 'nullable|numeric|min:0',
            'notes'             => 'nullable|string',
        ]);

        $customer = $this->service->create($validated);

        return $this->success($customer, 'Customer created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'name'              => 'sometimes|string|max:255',
            'phone'             => 'sometimes|string|max:20',
            'email'             => 'nullable|email|max:255',
            'company_name'      => 'nullable|string|max:255',
            'customer_group_id' => 'nullable|integer|exists:customer_groups,id',
            'address'           => 'nullable|string|max:500',
            'shipping_address'  => 'nullable|string|max:500',
            'credit_limit'      => 'nullable|numeric|min:0',
            'notes'             => 'nullable|string',
        ]);

        $customer = $this->service->update($customer, $validated);

        return $this->success($customer, 'Customer updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $this->service->delete($customer);

        return $this->success(null, 'Customer deleted');
    }

    public function ledger(Request $request, int $id): JsonResponse
    {
        $filters = $request->only(['date_from', 'date_to']);
        $ledger = $this->service->getLedger($id, $filters);

        return $this->success($ledger, 'Customer ledger retrieved');
    }

    public function dues(int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        $dues = \Modules\Sale\Models\Sale::where('customer_id', $id)
            ->where('due_amount', '>', 0)
            ->whereNotIn('status', \Modules\Customer\Services\CustomerService::HIDDEN_SALE_STATUSES)
            ->orderBy('sale_date')
            ->get(['id', 'invoice_number', 'sale_date', 'grand_total', 'paid_amount', 'due_amount']);

        return $this->success($dues, 'Customer dues retrieved');
    }
}
