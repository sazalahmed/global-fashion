<?php

namespace Modules\Variant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Variant\Models\VariantAttribute;
use Modules\Variant\Models\VariantAttributeChartRow;
use Modules\Variant\Services\VariantChartService;

/**
 * AJAX endpoints for the chart editor on the Variant Attribute edit page.
 * All actions are scoped by {variant} (the parent attribute) so cross-
 * attribute writes are blocked by route binding alone.
 */
class VariantChartController extends Controller
{
    public function __construct(private VariantChartService $service) {}

    public function storeRow(Request $request, VariantAttribute $variant): JsonResponse
    {
        bpAuthorize('variants.create');
        $data = $request->validate(['label' => ['required', 'string', 'max:64']]);
        $row = $this->service->createRow($variant, $data['label']);
        return response()->json($this->rowToArray($row), 201);
    }

    public function updateRow(Request $request, VariantAttribute $variant, VariantAttributeChartRow $row): JsonResponse
    {
        bpAuthorize('variants.edit');
        abort_unless($variant->id === $row->variant_attribute_id, 403);

        $data = $request->validate([
            'label'     => ['sometimes', 'string', 'max:64'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $this->service->updateRow($row, $data);
        return response()->json($this->rowToArray($row->fresh()));
    }

    public function destroyRow(VariantAttribute $variant, VariantAttributeChartRow $row): JsonResponse
    {
        bpAuthorize('variants.delete');
        abort_unless($variant->id === $row->variant_attribute_id, 403);
        $this->service->deleteRow($row);
        return response()->json(['success' => true]);
    }

    public function reorderRows(Request $request, VariantAttribute $variant): JsonResponse
    {
        bpAuthorize('variants.edit');
        $data = $request->validate([
            'ordered_ids'   => ['required', 'array'],
            'ordered_ids.*' => ['integer'],
        ]);
        $this->service->reorderRows($variant, $data['ordered_ids']);
        return response()->json(['success' => true]);
    }

    public function upsertValue(Request $request, VariantAttribute $variant): JsonResponse
    {
        bpAuthorize('variants.edit');
        $data = $request->validate([
            'row_id'   => ['required', 'integer', 'exists:variant_attribute_chart_rows,id'],
            'value_id' => ['required', 'integer', 'exists:variant_attribute_values,id'],
            'value'    => ['nullable', 'string', 'max:64'],
        ]);

        // Row + value must both belong to this attribute.
        $rowOk = VariantAttributeChartRow::where('id', $data['row_id'])
            ->where('variant_attribute_id', $variant->id)
            ->exists();
        $valOk = $variant->values()->where('variant_attribute_values.id', $data['value_id'])->exists();
        abort_unless($rowOk && $valOk, 403);

        $entry = $this->service->upsertValue($data['row_id'], $data['value_id'], $data['value'] ?? null);
        return response()->json(['success' => true, 'id' => $entry->id]);
    }

    private function rowToArray(VariantAttributeChartRow $r): array
    {
        return [
            'id'         => $r->id,
            'label'      => $r->label,
            'sort_order' => $r->sort_order,
            'is_active'  => (bool) $r->is_active,
        ];
    }
}
