<?php

namespace Modules\Unit\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Unit\Models\Unit;
use Modules\Unit\Services\UnitService;
use Modules\Unit\Http\Requests\StoreUnitRequest;
use Modules\Unit\Http\Requests\UpdateUnitRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UnitController extends Controller
{
    public function __construct(private UnitService $unitService)
    {
    }

    /**
     * Display a listing of units.
     */
    public function index(Request $request)
    {
        bpAuthorize('units.view');
        $filters = $request->only(['search', 'type', 'status']);
        $units = $this->unitService->list($filters);
        $stats = $this->unitService->getStats($filters);

        return view('unit::index', compact('units', 'stats'));
    }

    /**
     * Show the form for creating a new unit.
     */
    public function create()
    {
        bpAuthorize('units.create');
        $baseUnits = $this->unitService->getBaseUnits();

        return view('unit::create', compact('baseUnits'));
    }

    /**
     * Store a newly created unit.
     */
    public function store(StoreUnitRequest $request)
    {
        bpAuthorize('units.create');
        $this->unitService->create($request->validated());

        return redirect()->route('units.index')->with('success', __('Unit created successfully.'));
    }

    /**
     * Quick-store a unit via AJAX (from product create page).
     */
    public function quickStore(Request $request): JsonResponse
    {
        bpAuthorize('units.create');
        $validated = $request->validate([
            'name'              => 'required|string|max:100',
            'short_name'        => 'required|string|max:20',
            'unit_type'         => 'nullable|string|in:base,sub',
            'base_unit_id'      => 'nullable|integer|exists:units,id',
            'conversion_factor' => 'nullable|numeric|min:0',
            'allow_decimal'     => 'nullable',
        ]);

        $unit = $this->unitService->create([
            'name'              => $validated['name'],
            'short_name'        => $validated['short_name'],
            'unit_type'         => $validated['unit_type'] ?? 'base',
            'base_unit_id'      => $validated['base_unit_id'] ?? null,
            'conversion_factor' => $validated['conversion_factor'] ?? null,
            'allow_decimal'     => !empty($validated['allow_decimal']),
            'status'            => 'active',
        ]);

        return response()->json([
            'id'         => $unit->id,
            'name'       => $unit->name,
            'short_name' => $unit->short_name,
        ]);
    }

    /**
     * Show the form for editing the specified unit.
     */
    public function edit(Unit $unit)
    {
        bpAuthorize('units.edit');
        $baseUnits = $this->unitService->getBaseUnits();

        return view('unit::edit', compact('unit', 'baseUnits'));
    }

    /**
     * Update the specified unit.
     */
    public function update(UpdateUnitRequest $request, Unit $unit)
    {
        bpAuthorize('units.edit');
        $this->unitService->update($unit, $request->validated());

        return redirect()->route('units.index')->with('success', __('Unit updated successfully.'));
    }

    /**
     * Toggle the active/inactive status of the specified unit.
     */
    public function toggleStatus(Unit $unit): JsonResponse
    {
        bpAuthorize('units.edit');
        $this->unitService->toggleStatus($unit);

        return response()->json([
            'success'   => true,
            'is_active' => $unit->status === 'active',
            'message'   => __('Status updated.'),
        ]);
    }

    /**
     * Remove the specified unit.
     */
    public function destroy(Unit $unit)
    {
        bpAuthorize('units.delete');
        $this->unitService->delete($unit);

        return redirect()->route('units.index')->with('success', __('Unit deleted successfully.'));
    }
}
