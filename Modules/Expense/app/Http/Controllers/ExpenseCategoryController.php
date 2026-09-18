<?php

namespace Modules\Expense\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Expense\Http\Requests\StoreExpenseCategoryRequest;
use Modules\Expense\Models\ExpenseCategory;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request): View
    {
        bpAuthorize('finance.view');
        $categories = ExpenseCategory::flatTree(activeOnly: false);

        if ($search = $request->input('search')) {
            $needle = mb_strtolower($search);
            $visibleIds = [];
            foreach ($categories as $cat) {
                if (str_contains(mb_strtolower($cat->name), $needle)) {
                    $visibleIds[$cat->id] = true;
                    if ($cat->parent_id) {
                        $visibleIds[$cat->parent_id] = true; // keep parent for context
                    }
                }
            }
            $categories = $categories->filter(fn ($c) => isset($visibleIds[$c->id]))->values();
        }

        return view('expense::categories.index', compact('categories'));
    }

    public function create(): View
    {
        bpAuthorize('finance.create');
        $parents = ExpenseCategory::active()->roots()->ordered()->get();

        return view('expense::categories.create', compact('parents'));
    }

    public function store(StoreExpenseCategoryRequest $request): RedirectResponse
    {
        bpAuthorize('finance.create');
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['level'] = isset($data['parent_id']) && $data['parent_id'] ? 1 : 0;

        $category = ExpenseCategory::create($data);

        return redirect()->route('expense-categories.index')
            ->with('success', "Category \"{$category->name}\" created.");
    }

    public function quickAdd(StoreExpenseCategoryRequest $request): JsonResponse
    {
        bpAuthorize('finance.create');
        $data = $request->validated();
        $data['is_active'] = true;
        $data['level'] = isset($data['parent_id']) && $data['parent_id'] ? 1 : 0;

        $category = ExpenseCategory::create($data);

        return response()->json([
            'success'  => true,
            'category' => [
                'id'   => $category->id,
                'name' => $category->name,
            ],
        ]);
    }

    public function edit(ExpenseCategory $category): View
    {
        bpAuthorize('finance.edit');
        $parents = ExpenseCategory::active()->roots()
            ->where('id', '!=', $category->id)
            ->ordered()
            ->get();

        return view('expense::categories.edit', compact('category', 'parents'));
    }

    public function update(StoreExpenseCategoryRequest $request, ExpenseCategory $category): RedirectResponse
    {
        bpAuthorize('finance.edit');
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['level'] = isset($data['parent_id']) && $data['parent_id'] ? 1 : 0;

        $category->update($data);

        return redirect()->route('expense-categories.index')
            ->with('success', __('Category updated.'));
    }

    public function toggleStatus(ExpenseCategory $category): JsonResponse
    {
        bpAuthorize('finance.edit');
        $category->update(['is_active' => ! $category->is_active]);

        return response()->json([
            'success'   => true,
            'is_active' => $category->is_active,
            'message'   => __('Status updated.'),
        ]);
    }

    public function destroy(ExpenseCategory $category): RedirectResponse
    {
        bpAuthorize('finance.delete');
        if ($category->expenses()->exists()) {
            return back()->with('error', "Can't delete \"{$category->name}\" — it has expenses linked. Reassign them first.");
        }

        if ($category->children()->exists()) {
            return back()->with('error', "Can't delete \"{$category->name}\" — it has sub-categories. Delete those first.");
        }

        $name = $category->name;
        $category->delete();

        return redirect()->route('expense-categories.index')
            ->with('success', "Category \"{$name}\" deleted.");
    }
}
