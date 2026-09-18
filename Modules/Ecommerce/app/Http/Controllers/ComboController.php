<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Helpers\Upload;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\StoreComboRequest;
use Modules\Ecommerce\Http\Requests\UpdateComboRequest;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Services\ComboService;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;

class ComboController extends Controller
{
    public function __construct(private ComboService $combos) {}

    public function create(): View
    {
        $products = Product::active()->with(['images', 'variants.attributeValues.attribute'])->orderBy('name')->get();

        return view('ecommerce::combos.create', [
            'products'   => $products,
            'categories' => $this->categoryTree(),
        ]);
    }

    public function store(StoreComboRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = Upload::store($request->file('thumbnail'), 'combos');
        }
        $this->combos->persist($data);

        return redirect()->route('products.index')->with('success', __('Combo created.'));
    }

    public function edit(Combo $combo): View
    {
        $combo->load(['items.product.images', 'items.variant', 'galleryImages', 'categories']);
        $products = Product::active()->with(['images', 'variants.attributeValues.attribute'])->orderBy('name')->get();

        return view('ecommerce::combos.edit', [
            'combo'      => $combo,
            'products'   => $products,
            'categories' => $this->categoryTree(),
            'service'    => $this->combos,
        ]);
    }

    private function categoryTree()
    {
        return Category::active()->ordered()
            ->with('children', 'children.children')
            ->whereNull('parent_id')
            ->get();
    }

    public function update(UpdateComboRequest $request, Combo $combo): RedirectResponse
    {
        $data = $request->validated();
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = Upload::store($request->file('thumbnail'), 'combos');
            Upload::delete($combo->thumbnail);
        }
        $this->combos->persist($data, $combo);

        return redirect()->route('products.index')->with('success', __('Combo updated.'));
    }

    public function destroy(Combo $combo): RedirectResponse
    {
        $combo->delete();

        return redirect()->route('products.index')->with('success', __('Combo deleted.'));
    }

    public function toggleStatus(Combo $combo, Request $request): JsonResponse|RedirectResponse
    {
        $combo->update(['is_active' => ! $combo->is_active]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'is_active' => (bool) $combo->is_active,
                'message'   => __('Combo status updated.'),
            ]);
        }

        return back()->with('success', __('Combo status updated.'));
    }
}
