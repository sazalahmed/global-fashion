<?php

namespace Modules\LandingPage\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\LandingPage\Http\Requests\StoreLandingPageRequest;
use Modules\LandingPage\Models\LandingPage;
use Modules\LandingPage\Services\LandingPageService;
use Modules\Product\Models\Product;

class LandingPageController extends Controller
{
    public function __construct(
        private readonly LandingPageService $service,
    ) {}

    public function index()
    {
        $pages = $this->service->list();

        return view('landingpage::admin.index', compact('pages'));
    }

    public function create()
    {
        return view('landingpage::admin.edit', [
            'page' => new LandingPage([
                'template'               => 'template-1',
                'delivery_inside_dhaka'  => 60,
                'delivery_outside_dhaka' => 120,
                'product_ids'            => [],
                'sections'               => [],
            ]),
            'isEdit' => false,
        ]);
    }

    public function store(StoreLandingPageRequest $request)
    {
        $page = $this->service->create($request->validated());

        return redirect()->route('landing-pages.edit', $page)
            ->with('success', "Landing page \"{$page->name}\" created.");
    }

    public function edit(LandingPage $landingPage)
    {
        return view('landingpage::admin.edit', [
            'page'   => $landingPage,
            'isEdit' => true,
        ]);
    }

    public function update(StoreLandingPageRequest $request, LandingPage $landingPage)
    {
        $this->service->update($landingPage, $request->validated());

        return back()->with('success', __('Landing page updated.'));
    }

    public function destroy(LandingPage $landingPage)
    {
        $name = $landingPage->name;
        $this->service->delete($landingPage);

        return redirect()->route('landing-pages.index')
            ->with('success', "Landing page \"{$name}\" deleted.");
    }

    public function activate(LandingPage $landingPage)
    {
        $this->service->activate($landingPage);

        return back()->with('success', "\"{$landingPage->name}\" is now active. Landing page mode enabled.");
    }

    public function deactivate()
    {
        $this->service->deactivate();

        return back()->with('success', __('Landing page mode disabled. Full eCommerce site is active.'));
    }

    public function preview(LandingPage $landingPage)
    {
        $page = $landingPage;
        $products = $page->products;
        $templateView = 'landingpage::templates.' . $page->template;

        return view($templateView, compact('page', 'products'));
    }

    public function previewWithData(Request $request, LandingPage $landingPage)
    {
        return $this->renderPreview($request, $landingPage);
    }

    public function previewBlank(Request $request)
    {
        return $this->renderPreview($request, new LandingPage(['template' => 'template-1']));
    }

    private function renderPreview(Request $request, LandingPage $landingPage)
    {
        $landingPage->fill($request->except(['_token', '_method', 'hero_image']));

        if ($request->has('faqs') || $request->has('benefits') || $request->has('sizes') || $request->has('details') || $request->has('ingredients')) {
            $sections = [];
            foreach (['faqs', 'benefits', 'sizes', 'details', 'ingredients'] as $key) {
                if ($request->has($key)) {
                    $sections[$key] = array_values(array_filter($request->input($key, []), fn ($item) => !empty(array_filter($item))));
                }
            }
            $landingPage->sections = $sections;
        }

        $page = $landingPage;
        $productIds = $request->input('product_ids', []);
        $products = !empty($productIds)
            ? \Modules\Product\Models\Product::whereIn('id', $productIds)->get()
            : collect();

        $templateView = 'landingpage::templates.' . ($page->template ?: 'template-1');

        return view($templateView, compact('page', 'products'));
    }

    public function searchProducts(Request $request)
    {
        $query = $request->input('q', '');
        $products = Product::active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get(['id', 'name', 'sku', 'sell_price']);

        return response()->json($products->map(fn ($p) => [
            'id'    => $p->id,
            'text'  => "{$p->name} ({$p->sku}) — " . currency_symbol() . " " . number_format($p->sell_price),
            'name'  => $p->name,
            'sku'   => $p->sku,
            'price' => $p->sell_price,
        ]));
    }
}
