<?php

namespace Modules\Variant\Services;

use Illuminate\Support\Facades\DB;
use Modules\Variant\Models\VariantAttribute;
use Modules\Variant\Models\VariantAttributeChartRow;
use Modules\Variant\Models\VariantAttributeChartValue;

/**
 * Manages the measurement chart that hangs off a variant attribute
 * (typically the "Size" attribute). The chart's columns are the attribute's
 * own values (S, M, L, …); rows are measurement labels (Chest, Waist, …)
 * stored here; cells are upserted per (row, value) pair.
 */
class VariantChartService
{
    // ── Rows (measurements) ───────────────────────────────────────────

    public function createRow(VariantAttribute $attr, string $label): VariantAttributeChartRow
    {
        $next = ((int) $attr->chartRows()->max('sort_order')) + 1;

        return $attr->chartRows()->create([
            'label'      => trim($label),
            'sort_order' => $next,
            'is_active'  => true,
        ]);
    }

    public function updateRow(VariantAttributeChartRow $row, array $data): VariantAttributeChartRow
    {
        $row->fill(array_intersect_key($data, array_flip(['label', 'is_active'])))->save();
        return $row;
    }

    public function deleteRow(VariantAttributeChartRow $row): void
    {
        $row->delete();
    }

    public function reorderRows(VariantAttribute $attr, array $orderedIds): void
    {
        DB::transaction(function () use ($attr, $orderedIds) {
            foreach ($orderedIds as $i => $id) {
                $attr->chartRows()->whereKey($id)->update(['sort_order' => $i + 1]);
            }
        });
    }

    // ── Cell values ────────────────────────────────────────────────────

    public function upsertValue(int $rowId, int $valueId, ?string $cell): VariantAttributeChartValue
    {
        return VariantAttributeChartValue::updateOrCreate(
            [
                'variant_attribute_chart_row_id' => $rowId,
                'variant_attribute_value_id'     => $valueId,
            ],
            ['value' => $cell !== null ? trim($cell) : null]
        );
    }

    // ── Read for product page ─────────────────────────────────────────

    /**
     * Build the chart payload for an attribute, projected to only the
     * attribute values whose IDs appear in $allowedValueIds. Returns null
     * when there's no usable chart (no rows, or no values pass the filter).
     *
     * $overrides — optional [row_id][value_id] => string. Per-product overrides
     * win over the global defaults stored on the attribute itself.
     */
    public function buildChartFor(
        VariantAttribute $attr,
        ?array $allowedValueIds = null,
        ?array $overrides = null
    ): ?array {
        $values = $attr->values()->getQuery()->orderBy('sort_order')->orderBy('id')->get();

        if ($allowedValueIds !== null) {
            $allowed = array_map('intval', $allowedValueIds);
            $values = $values->filter(fn ($v) => in_array((int) $v->id, $allowed, true))->values();
        }

        if ($values->isEmpty()) return null;

        $rows = $attr->chartRows()->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')->get();
        if ($rows->isEmpty()) return null;

        $valueIds = $values->pluck('id')->all();
        $rowIds   = $rows->pluck('id')->all();

        $cells = VariantAttributeChartValue::whereIn('variant_attribute_chart_row_id', $rowIds)
            ->whereIn('variant_attribute_value_id', $valueIds)
            ->get()
            ->groupBy('variant_attribute_chart_row_id');

        $rowData = $rows->map(function ($row) use ($cells, $valueIds, $overrides) {
            $byRow = $cells->get($row->id, collect())->keyBy('variant_attribute_value_id');
            $line = [];
            foreach ($valueIds as $vid) {
                $override = $overrides[$row->id][$vid] ?? null;
                if ($override !== null && $override !== '') {
                    $line[] = $override;
                } else {
                    $line[] = optional($byRow->get($vid))->value;
                }
            }
            return ['label' => $row->label, 'cells' => $line];
        })->all();

        // Drop rows where every visible cell is empty.
        $rowData = array_values(array_filter(
            $rowData,
            fn ($r) => collect($r['cells'])->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty()
        ));

        if (empty($rowData)) return null;

        return [
            'sizes' => $values->map(fn ($v) => ['id' => $v->id, 'label' => $v->value])->all(),
            'rows'  => $rowData,
        ];
    }
}
