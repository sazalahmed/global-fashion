<?php

namespace Modules\Quotation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Branch\Models\Branch;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerGroup;
use Modules\Customer\Services\CustomerService;
use Modules\Location\Models\District;
use Modules\Product\Models\Product;
use Modules\Quotation\Http\Requests\StoreQuotationRequest;
use Modules\Quotation\Models\Quotation;
use Modules\Quotation\Services\QuotationService;
use Modules\Variant\Models\ProductVariant;

class QuotationController extends Controller
{
    public function __construct(
        protected QuotationService $quotationService,
        protected CustomerService $customerService,
    ) {}

    /**
     * Display a listing of quotations.
     */
    public function index(Request $request)
    {
        bpAuthorize('quotations.view');
        $filters = $request->all();
        $stats = $this->quotationService->getStats($filters);
        $quotations = $this->quotationService->list($filters);
        $customers = Customer::active()->get(['id', 'name', 'phone']);

        return view('quotation::index', compact('stats', 'quotations', 'customers'));
    }

    /**
     * Show the form for creating a new quotation.
     */
    public function create()
    {
        bpAuthorize('quotations.create');
        $customers = Customer::active()->get(['id', 'name', 'phone', 'address', 'shipping_address']);
        $branches = Branch::all();
        $customerGroups = CustomerGroup::active()->orderBy('name')->get();
        $districts = District::where('is_active', true)->orderBy('district_name')->get(['id', 'district_name']);

        $products = $this->productCatalog();

        // Customer prior balances across active orders (display-only).
        $previousDueMap = $this->customerService->getActiveDueMap();

        return view('quotation::create', compact('customers', 'branches', 'products', 'customerGroups', 'districts', 'previousDueMap'));
    }

    /**
     * Store a newly created quotation in storage.
     */
    public function store(StoreQuotationRequest $request)
    {
        bpAuthorize('quotations.create');
        $validated = $request->validated();

        $items = $validated['items'] ?? [];
        unset($validated['items']);

        $quotation = $this->quotationService->create($validated, $items);

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('success', __('Quotation created successfully.'));
    }

    /**
     * Display the specified quotation.
     */
    public function show(Quotation $quotation)
    {
        bpAuthorize('quotations.view');
        $quotation = $this->quotationService->find($quotation->id);

        return view('quotation::show', compact('quotation'));
    }

    /**
     * Show the form for editing the specified quotation.
     */
    public function edit(Quotation $quotation)
    {
        bpAuthorize('quotations.edit');
        $quotation = $this->quotationService->find($quotation->id);
        $customers = Customer::active()->get(['id', 'name', 'phone', 'address', 'shipping_address']);
        $branches = Branch::all();
        $customerGroups = CustomerGroup::active()->orderBy('name')->get();
        $districts = District::where('is_active', true)->orderBy('district_name')->get(['id', 'district_name']);

        $products = $this->productCatalog();

        // Customer prior balances across active orders (display-only). Quotations
        // carry no due of their own, so nothing is excluded.
        $previousDueMap = $this->customerService->getActiveDueMap();

        return view('quotation::edit', compact('quotation', 'customers', 'branches', 'products', 'customerGroups', 'districts', 'previousDueMap'));
    }

    /**
     * Update the specified quotation in storage.
     */
    public function update(StoreQuotationRequest $request, Quotation $quotation)
    {
        bpAuthorize('quotations.edit');
        $validated = $request->validated();

        $items = $validated['items'] ?? [];
        unset($validated['items']);

        $this->quotationService->update($quotation, $validated, $items);

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('success', __('Quotation updated successfully.'));
    }

    /**
     * Remove the specified quotation from storage.
     */
    public function destroy(Quotation $quotation)
    {
        bpAuthorize('quotations.delete');
        $quotation->delete();

        return redirect()
            ->route('quotations.index')
            ->with('success', __('Quotation deleted successfully.'));
    }

    /**
     * Bulk-update the status of the selected quotations.
     */
    public function bulkStatus(Request $request)
    {
        bpAuthorize('quotations.edit');
        $validated = $request->validate([
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer', 'exists:quotations,id'],
            'status' => ['required', 'in:draft,pending,accepted,rejected,expired'],
        ]);

        $count = Quotation::whereIn('id', $validated['ids'])->update(['status' => $validated['status']]);

        return back()->with('success', __(':count quotation(s) updated.', ['count' => $count]));
    }

    /**
     * Bulk soft-delete the selected quotations.
     */
    public function bulkDelete(Request $request)
    {
        bpAuthorize('quotations.delete');
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:quotations,id'],
        ]);

        $count = Quotation::whereIn('id', $validated['ids'])->count();
        Quotation::whereIn('id', $validated['ids'])->delete();

        return back()->with('success', __(':count quotation(s) deleted.', ['count' => $count]));
    }

    /**
     * Convert the quotation to a sale.
     */
    public function convertToSale(Quotation $quotation)
    {
        bpAuthorize('quotations.create');
        try {
            $sale = $this->quotationService->convertToSale($quotation);

            return redirect()
                ->route('sales.show', $sale)
                ->with('success', __('Quotation converted to sale successfully.'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display printable quotation view.
     */
    public function print(Quotation $quotation)
    {
        bpAuthorize('quotations.view');
        $quotation = $this->quotationService->find($quotation->id);

        return view('quotation::print', compact('quotation'));
    }

    /**
     * Download quotation as PDF.
     */
    public function pdf(Quotation $quotation)
    {
        bpAuthorize('quotations.view');
        $quotation = $this->quotationService->find($quotation->id);

        $brand = \Modules\Setting\Services\SettingService::printBranding(true);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('quotation::print', [
            'quotation' => $quotation,
            'isPdf' => true,
            'brand' => $brand,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("Quotation-{$quotation->quotation_number}.pdf");
    }

    /**
     * Duplicate the quotation.
     */
    public function duplicate(Quotation $quotation)
    {
        bpAuthorize('quotations.create');
        $newQuotation = $this->quotationService->duplicate($quotation);

        return redirect()
            ->route('quotations.edit', $newQuotation)
            ->with('success', __('Quotation duplicated successfully.'));
    }

    /**
     * AJAX product search — returns only necessary fields + variants.
     */
    public function searchProducts(Request $request): JsonResponse
    {
        bpAuthorize('quotations.view');
        $term = $request->input('q', '');

        $products = $this->productQuery()
            ->when(strlen($term) >= 1, fn ($q) => $q->search($term))
            ->limit(20)
            ->get();

        return response()->json($products->map(fn ($p) => $this->mapProductForCatalog($p))->values());
    }

    /**
     * Full active-product catalog (id, name, sku, image, price, variants) used
     * to render the line-item search client-side — the list shows instantly on
     * focus with no AJAX round-trip.
     */
    public function productCatalog(): array
    {
        return $this->productQuery()->get()->map(fn ($p) => $this->mapProductForCatalog($p))->values()->all();
    }

    /** Base query for the product catalog / search (active products + active variants). */
    private function productQuery()
    {
        return Product::active()
            ->orderBy('name')
            ->select('id', 'name', 'sku', 'model', 'barcode', 'sell_price', 'wholesale_price', 'resell_price', 'vat_rate', 'product_type', 'thumbnail')
            // images eager-loaded so the display_image gallery fallback (used when a
            // product has no thumbnail) does not lazy-load per row (N+1).
            ->with(['images', 'variants' => function ($q) {
                $q->where('is_active', true)
                  ->select('id', 'product_id', 'sku', 'cost_price', 'sell_price', 'wholesale_price', 'resell_price')
                  ->withSum('warehouseStock as stock_qty', 'quantity')
                  ->with(['attributeValues:id,value,variant_attribute_id']);
            }]);
    }

    /** Map a product to the search/catalog item shape consumed by BpProductSearch. */
    private function mapProductForCatalog(Product $product): array
    {
        $item = [
            'id'           => $product->id,
            'name'         => $product->name,
            'sku'          => $product->sku,
            'model'        => $product->model ?? '',
            'barcode'      => $product->barcode ?? '',
            'image'        => $product->display_image ? upload_url($product->display_image) : null,
            'sell_price'   => (float) $product->sell_price,
            'wholesale_price' => (float) ($product->wholesale_price ?? $product->sell_price),
            'resell_price' => (float) ($product->resell_price ?? $product->wholesale_price ?? $product->sell_price),
            'vat_rate'     => (float) $product->vat_rate,
            'product_type' => $product->product_type,
            'variants'     => [],
        ];

        if ($product->product_type === 'variable' && $product->variants->isNotEmpty()) {
            $item['variants'] = $product->variants->map(function ($v) use ($product) {
                $variantName = $v->attributeValues->pluck('value')->implode(' / ');

                return [
                    'id'         => $v->id,
                    'sku'        => $v->sku,
                    'name'       => $variantName ?: $v->sku,
                    'sell_price' => (float) ($v->sell_price ?? $product->sell_price),
                    'wholesale_price' => (float) ($v->wholesale_price ?? $product->wholesale_price ?? $v->sell_price ?? $product->sell_price),
                    'resell_price' => (float) ($v->resell_price ?? $product->resell_price ?? $v->wholesale_price ?? $product->wholesale_price ?? $v->sell_price ?? $product->sell_price),
                    'stock'      => (int) ($v->stock_qty ?? 0),
                ];
            })->values();
        }

        return $item;
    }
}
