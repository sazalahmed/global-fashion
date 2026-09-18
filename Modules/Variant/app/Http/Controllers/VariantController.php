<?php

namespace Modules\Variant\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Variant\Models\VariantAttribute;
use Modules\Variant\Services\VariantService;
use Modules\Variant\Http\Requests\StoreVariantAttributeRequest;
use Modules\Variant\Http\Requests\UpdateVariantAttributeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Variant\Models\VariantAttributeValue;

class VariantController extends Controller
{
    public function __construct(
        private readonly VariantService $service
    ) {}

    public function index(Request $request)
    {
        bpAuthorize('variants.view');
        $stats = $this->service->getStats();
        $attributes = $this->service->list($request->only(['search']));

        return view('variant::index', [
            'attributes'     => $attributes,
            'totalAttributes'=> $stats['total_attributes'],
            'totalValues'    => $stats['total_values'],
            'productsUsing'  => $stats['products_using'],
        ]);
    }

    public function create()
    {
        bpAuthorize('variants.create');
        return view('variant::create');
    }

    public function store(StoreVariantAttributeRequest $request)
    {
        bpAuthorize('variants.create');
        $this->service->createAttribute($request->validated());
        return redirect()->route('variants.index')->with('success', __('Variant attribute created successfully.'));
    }

    public function show(VariantAttribute $variant)
    {
        bpAuthorize('variants.view');
        $variant->load('values');
        return view('variant::show', ['attribute' => $variant]);
    }

    public function edit(VariantAttribute $variant)
    {
        bpAuthorize('variants.edit');
        $variant->load('values');

        $chartRows = $variant->chartRows()->orderBy('sort_order')->orderBy('id')->get();

        // Pre-load cells as [row_id][value_id] => value for the editor grid.
        $chartCells = [];
        if ($chartRows->isNotEmpty() && $variant->values->isNotEmpty()) {
            $cellRows = \Modules\Variant\Models\VariantAttributeChartValue::query()
                ->whereIn('variant_attribute_chart_row_id', $chartRows->pluck('id'))
                ->whereIn('variant_attribute_value_id', $variant->values->pluck('id'))
                ->get();
            foreach ($cellRows as $c) {
                $chartCells[$c->variant_attribute_chart_row_id][$c->variant_attribute_value_id] = $c->value;
            }
        }

        return view('variant::edit', [
            'attribute'  => $variant,
            'chartRows'  => $chartRows,
            'chartCells' => $chartCells,
        ]);
    }

    public function update(UpdateVariantAttributeRequest $request, VariantAttribute $variant)
    {
        bpAuthorize('variants.edit');
        try {
            $this->service->updateAttribute($variant, $request->validated());
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('variants.index')->with('success', __('Variant attribute updated successfully.'));
    }

    public function destroy(VariantAttribute $variant)
    {
        bpAuthorize('variants.delete');
        try {
            $this->service->deleteAttribute($variant);
        } catch (\RuntimeException $e) {
            return redirect()->route('variants.index')->with('error', $e->getMessage());
        }

        return redirect()->route('variants.index')->with('success', __('Variant attribute deleted successfully.'));
    }

    public function toggleStatus(VariantAttribute $variant): JsonResponse
    {
        bpAuthorize('variants.edit');
        $this->service->toggleStatus($variant);

        return response()->json([
            'success'   => true,
            'is_active' => $variant->status === 'active',
            'message'   => __('Status updated.'),
        ]);
    }

    public function setValueActive(Request $request, int $value): JsonResponse
    {
        bpAuthorize('variants.edit');
        $request->validate(['is_active' => 'required|boolean']);

        $val = VariantAttributeValue::findOrFail($value);
        $val->update(['is_active' => $request->boolean('is_active')]);

        return response()->json(['success' => true, 'is_active' => $val->is_active]);
    }
}
