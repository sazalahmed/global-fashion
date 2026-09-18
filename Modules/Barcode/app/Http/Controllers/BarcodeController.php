<?php

namespace Modules\Barcode\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Barcode\Http\Requests\BarcodeSearchRequest;
use Modules\Barcode\Http\Requests\GenerateBarcodeRequest;
use Modules\Barcode\Http\Requests\PrintBarcodeRequest;
use Modules\Barcode\Services\BarcodeService;

class BarcodeController extends Controller
{
    protected BarcodeService $barcodeService;

    public function __construct(BarcodeService $barcodeService)
    {
        $this->barcodeService = $barcodeService;
    }

    /**
     * Display the barcode generator page.
     */
    public function index(Request $request)
    {
        bpAuthorize('barcode.view');
        $preselected = collect();
        if ($request->has('product_id')) {
            $preselected = $this->barcodeService->getProductsByIds(
                (array) $request->input('product_id')
            );
        }

        return view('barcode::index', compact('preselected'));
    }

    /**
     * Search products for the barcode generator (AJAX).
     * Returns JSON for the search dropdown.
     */
    public function search(BarcodeSearchRequest $request)
    {
        bpAuthorize('barcode.view');
        $products = $this->barcodeService->searchProducts($request->input('q'));

        return response()->json([
            'products' => $products->map(function ($product) {
                return [
                    'id'            => $product->id,
                    'name'          => $product->name,
                    'sku'           => $product->sku,
                    'barcode'       => $product->barcode ?? '',
                    'sell_price'    => (float) $product->sell_price,
                    'current_stock' => (int) ($product->current_stock ?? 0),
                ];
            }),
        ]);
    }

    /**
     * Generate barcodes for selected products.
     * Returns JSON with barcode data for preview.
     */
    public function generate(GenerateBarcodeRequest $request)
    {
        bpAuthorize('barcode.generate');
        $products = $this->barcodeService->getProductsByIds($request->input('product_ids'));
        $quantities = $request->input('quantities', []);

        $labels = [];
        foreach ($products as $product) {
            $qty = $quantities[$product->id] ?? 1;
            $labels[] = [
                'id'         => $product->id,
                'name'       => $product->name,
                'sku'        => $product->sku,
                'barcode'    => $product->barcode ?? $product->sku,
                'sell_price' => (float) $product->sell_price,
                'quantity'   => $qty,
            ];
        }

        return response()->json([
            'labels'   => $labels,
            'settings' => [
                'barcode_type'  => $request->input('barcode_type', 'code128'),
                'show_name'     => $request->boolean('show_name', true),
                'show_price'    => $request->boolean('show_price', true),
                'show_business' => $request->boolean('show_business', false),
                'label_size'    => $request->input('label_size', '50x25'),
                'labels_per_row'=> $request->input('labels_per_row', 3),
            ],
        ]);
    }

    /**
     * Print barcodes — returns a printable HTML page.
     */
    public function print(PrintBarcodeRequest $request)
    {
        bpAuthorize('barcode.generate');
        $products = $this->barcodeService->getProductsByIds($request->input('product_ids'));
        $quantities = $request->input('quantities', []);

        return view('barcode::print', [
            'products'   => $products,
            'quantities' => $quantities,
            'settings'   => $request->only(['barcode_type', 'show_name', 'show_price', 'label_size', 'labels_per_row']),
        ]);
    }
}
