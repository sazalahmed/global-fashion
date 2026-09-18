<?php

namespace Modules\Asset\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Asset\Http\Requests\StoreAssetCategoryRequest;
use Modules\Asset\Models\AssetCategory;

class AssetCategoryController extends Controller
{
    public function index(Request $request): View
    {
        bpAuthorize('finance.view');
        $categories = AssetCategory::query()
            ->withCount('assets')
            ->when($request->input('search'), fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('asset::categories.index', compact('categories'));
    }

    public function create(): View
    {
        bpAuthorize('finance.create');
        return view('asset::categories.create');
    }

    public function store(StoreAssetCategoryRequest $request): RedirectResponse
    {
        bpAuthorize('finance.create');
        $data = $request->validated();
        $data['depreciation_method'] = $data['depreciation_method'] ?? 'straight_line';

        $category = AssetCategory::create($data);

        return redirect()->route('asset-categories.index')
            ->with('success', "Category \"{$category->name}\" created.");
    }

    public function edit(AssetCategory $category): View
    {
        bpAuthorize('finance.edit');
        return view('asset::categories.edit', compact('category'));
    }

    public function update(StoreAssetCategoryRequest $request, AssetCategory $category): RedirectResponse
    {
        bpAuthorize('finance.edit');
        $category->update($request->validated());

        return redirect()->route('asset-categories.index')
            ->with('success', __('Category updated.'));
    }

    public function destroy(AssetCategory $category): RedirectResponse
    {
        bpAuthorize('finance.delete');
        if ($category->assets()->exists()) {
            return back()->with('error', "Can't delete \"{$category->name}\" — it has assets linked. Reassign or delete them first.");
        }

        $name = $category->name;
        $category->delete();

        return redirect()->route('asset-categories.index')
            ->with('success', "Category \"{$name}\" deleted.");
    }
}
