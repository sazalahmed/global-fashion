<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Manufacturing\Models\ProductionDamage;
use Modules\Manufacturing\Services\ProductionDamageService;
use Modules\Manufacturing\Services\FactoryService;
use Illuminate\Http\Request;

class ProductionDamageController extends Controller
{
    public function __construct(
        private ProductionDamageService $damageService,
        private FactoryService $factoryService
    ) {
    }

    /**
     * Display a listing of damages.
     */
    public function index(Request $request)
    {
        bpAuthorize('manufacturing.view');
        $damages = $this->damageService->list(
            $request->only(['production_order_id', 'factory_id', 'damage_type', 'responsibility', 'compensation_status'])
        );
        $factories = $this->factoryService->getActiveFactories();

        return view('manufacturing::damages.index', compact('damages', 'factories'));
    }

    /**
     * Store a damage record.
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
            'quantity' => 'required|integer|min:1',
            'estimated_cost_per_unit' => 'required|numeric|min:0',
            'damage_type' => 'required|in:factory_fault,transit,storage,material_defect,other',
            'responsibility' => 'required|in:factory,own,supplier,transit',
            'compensation_amount' => 'nullable|numeric|min:0',
            'compensation_method' => 'nullable|in:cash,deduct_from_payable,replacement,mixed',
            'damage_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $this->damageService->create($validated);

        return redirect()->route('manufacturing.damages.index')
            ->with('success', __('Damage record created successfully.'));
    }

    /**
     * Record compensation for a damage.
     */
    public function recordCompensation(Request $request, ProductionDamage $damage)
    {
        bpAuthorize('manufacturing.create');
        $validated = $request->validate([
            'compensation_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,deduct_from_payable,replacement,mixed',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $this->damageService->recordCompensation($damage, $validated);

        return redirect()->route('manufacturing.damages.index')
            ->with('success', __('Compensation recorded successfully.'));
    }
}
