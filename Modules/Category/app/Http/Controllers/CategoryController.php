<?php

namespace Modules\Category\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Category\Models\Category;
use Modules\Category\Services\CategoryService;
use Modules\Category\Http\Requests\StoreCategoryRequest;
use Modules\Category\Http\Requests\UpdateCategoryRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private CategoryService $categoryService)
    {
    }

    /**
     * Display a listing of categories.
     */
    public function index(Request $request): View
    {
        bpAuthorize('categories.view');
        $categories = $this->categoryService->tree(
            $request->only(['search', 'status', 'parent_id'])
        );
        $stats = $this->categoryService->getStats();
        $parentOptions = $this->categoryService->getParentOptions();

        return view('category::index', compact('categories', 'stats', 'parentOptions'));
    }

    /**
     * Show the form for creating a new category.
     */
    public function create(): View
    {
        bpAuthorize('categories.create');
        $parentOptions = $this->categoryService->getParentOptions();

        return view('category::create', compact('parentOptions'));
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        bpAuthorize('categories.create');
        $this->categoryService->create($request->validated());

        return redirect()->route('categories.index')
            ->with('success', __('Category created successfully.'));
    }

    /**
     * Quick-store a category via AJAX (from product create page).
     */
    public function quickStore(Request $request): JsonResponse
    {
        bpAuthorize('categories.create');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = $this->categoryService->create([
            'name'   => $validated['name'],
            'status' => 'active',
        ]);

        return response()->json([
            'id'   => $category->id,
            'name' => $category->name,
        ]);
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(Category $category): View
    {
        bpAuthorize('categories.edit');
        $parentOptions = $this->categoryService->getParentOptions($category->id);

        return view('category::edit', compact('category', 'parentOptions'));
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        bpAuthorize('categories.edit');
        $this->categoryService->update($category, $request->validated());

        return redirect()->route('categories.index')
            ->with('success', __('Category updated successfully.'));
    }

    /**
     * Toggle a category's active/inactive status (AJAX).
     */
    public function toggleStatus(Category $category): JsonResponse
    {
        bpAuthorize('categories.edit');
        $category = $this->categoryService->toggleStatus($category);

        return response()->json([
            'success'   => true,
            'status'    => $category->status,
            'is_active' => $category->is_active,
            'message'   => __('Category status updated.'),
        ]);
    }

    /**
     * Remove the specified category (soft delete).
     */
    public function destroy(Category $category): RedirectResponse
    {
        bpAuthorize('categories.delete');
        $this->categoryService->delete($category);

        return redirect()->route('categories.index')
            ->with('success', __('Category deleted successfully.'));
    }

    /**
     * Persist a new ordering of categories.
     * Body: { "ordered_ids": [3, 1, 2, ...] }
     */
    public function reorder(Request $request): JsonResponse
    {
        bpAuthorize('categories.edit');
        $validated = $request->validate([
            'ordered_ids'   => 'required|array',
            'ordered_ids.*' => 'integer|exists:categories,id',
        ]);

        $this->categoryService->reorder($validated['ordered_ids']);

        return response()->json(['success' => true]);
    }
}
