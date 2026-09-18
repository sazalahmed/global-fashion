<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Expense\Services\ExpenseService;

class ExpenseApiController extends BaseApiController
{
    public function __construct(
        private readonly ExpenseService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $expenses = $this->service->list($request->all(), $request->input('per_page', 15));
        return $this->paginatedSuccess($expenses, 'Expenses retrieved successfully');
    }

    public function show(int $id): JsonResponse
    {
        $expense = $this->service->find($id);
        return $this->success($expense, 'Expense retrieved');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'expense_category_id' => 'required|integer|exists:expense_categories,id',
            'amount'              => 'required|numeric|min:0.01',
            'tax_amount'          => 'nullable|numeric|min:0',
            'expense_date'        => 'required|date',
            'due_date'            => 'nullable|date',
            'payment_method'      => 'nullable|in:cash,mobile_banking,card,bank_transfer',
            'payment_account_id'  => 'nullable|integer|exists:payment_accounts,id',
            'reference'           => 'nullable|string|max:255',
            'description'         => 'nullable|string',
        ]);

        $expense = $this->service->create($validated);
        return $this->success($expense, 'Expense created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $expense = Expense::findOrFail($id);

        if ($expense->status !== 'pending') {
            return $this->error('Only pending expenses can be updated', 422);
        }

        $validated = $request->validate([
            'expense_category_id' => 'sometimes|integer|exists:expense_categories,id',
            'amount'              => 'sometimes|numeric|min:0.01',
            'tax_amount'          => 'nullable|numeric|min:0',
            'expense_date'        => 'sometimes|date',
            'due_date'            => 'nullable|date',
            'payment_method'      => 'nullable|in:cash,mobile_banking,card,bank_transfer',
            'reference'           => 'nullable|string|max:255',
            'description'         => 'nullable|string',
        ]);

        $expense = $this->service->update($expense, $validated);
        return $this->success($expense, 'Expense updated');
    }

    public function approve(int $id): JsonResponse
    {
        $expense = Expense::findOrFail($id);

        if ($expense->status !== 'pending') {
            return $this->error('Only pending expenses can be approved', 422);
        }

        $expense = $this->service->approve($expense);
        return $this->success($expense, 'Expense approved');
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $expense = Expense::findOrFail($id);

        if ($expense->status !== 'pending') {
            return $this->error('Only pending expenses can be rejected', 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $expense = $this->service->reject($expense, $validated['reason']);
        return $this->success($expense, 'Expense rejected');
    }

    public function categories(): JsonResponse
    {
        $categories = ExpenseCategory::active()->ordered()->get(['id', 'name', 'parent_id', 'description']);
        return $this->success($categories, 'Expense categories retrieved');
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'parent_id'   => 'nullable|integer|exists:expense_categories,id',
            'description' => 'nullable|string|max:500',
        ]);

        $category = ExpenseCategory::create($validated);
        return $this->success($category, 'Expense category created', 201);
    }

    public function formOptions(): JsonResponse
    {
        return $this->success([
            'categories'       => ExpenseCategory::active()->ordered()->get(['id', 'name', 'parent_id']),
            'payment_accounts' => \Modules\Payment\Models\PaymentAccount::active()->get(['id', 'name', 'account_type']),
        ], 'Form options retrieved');
    }
}
