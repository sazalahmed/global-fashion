<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Manufacturing\Services\ProductWasteService;
use Illuminate\Http\Request;

class ProductWasteController extends Controller
{
    public function __construct(
        private ProductWasteService $wasteService
    ) {
    }

    /**
     * Display a listing of product wastes.
     */
    public function index(Request $request)
    {
        bpAuthorize('manufacturing.view');
        $wastes = $this->wasteService->list(
            $request->only(['production_order_id', 'waste_type', 'is_normal'])
        );

        return view('manufacturing::wastes.product-waste', compact('wastes'));
    }

    /**
     * Store a product waste record.
     */
    public function store(Request $request)
    {
        bpAuthorize('manufacturing.create');
        $validated = $request->validate([
            'production_order_id' => 'required|exists:production_orders,id',
            'lot_id' => 'nullable|exists:production_lots,id',
            'catalog_id' => 'nullable|exists:catalogs,id',
            'color_id' => 'nullable|exists:mfg_colors,id',
            'size_id' => 'nullable|exists:mfg_sizes,id',
            'product_id' => 'nullable|integer',
            'variant_id' => 'nullable|integer',
            'quantity_wasted' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric|min:0',
            'waste_type' => 'required|in:quality_rejection,manufacturing_defect,unrecoverable,other',
            'is_normal' => 'required|boolean',
            'waste_percentage' => 'nullable|numeric|min:0|max:100',
            'waste_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $this->wasteService->create($validated);

        return redirect()->route('manufacturing.wastes.products.index')
            ->with('success', __('Product waste record created successfully.'));
    }
}
