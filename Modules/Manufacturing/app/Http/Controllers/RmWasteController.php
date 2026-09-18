<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Manufacturing\Services\RmWasteService;
use Modules\Manufacturing\Services\RawMaterialService;
use Illuminate\Http\Request;

class RmWasteController extends Controller
{
    public function __construct(
        private RmWasteService $wasteService,
        private RawMaterialService $rawMaterialService
    ) {
    }

    /**
     * Display a listing of RM wastes.
     */
    public function index(Request $request)
    {
        bpAuthorize('manufacturing.view');
        $wastes = $this->wasteService->list(
            $request->only(['production_order_id', 'raw_material_id', 'waste_type', 'is_normal'])
        );
        $rawMaterials = $this->rawMaterialService->getActiveRawMaterials();

        return view('manufacturing::wastes.rm-waste', compact('wastes', 'rawMaterials'));
    }

    /**
     * Store an RM waste record.
     */
    public function store(Request $request)
    {
        bpAuthorize('manufacturing.create');
        $validated = $request->validate([
            'production_order_id' => 'required|exists:production_orders,id',
            'lot_id' => 'nullable|exists:production_lots,id',
            'raw_material_id' => 'required|exists:raw_materials,id',
            'quantity_wasted' => 'required|numeric|min:0.0001',
            'unit_cost' => 'required|numeric|min:0',
            'waste_type' => 'required|in:cutting,defective_material,spillage,shrinkage,other',
            'is_normal' => 'required|boolean',
            'waste_percentage' => 'nullable|numeric|min:0|max:100',
            'waste_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $this->wasteService->create($validated);

        return redirect()->route('manufacturing.wastes.rm.index')
            ->with('success', __('RM waste record created successfully.'));
    }
}
