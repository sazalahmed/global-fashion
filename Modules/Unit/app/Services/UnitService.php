<?php

namespace Modules\Unit\Services;

use Modules\Unit\Models\Unit;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class UnitService
{
    /**
     * Get paginated list of units with optional filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->applyFilters(Unit::with('baseUnit')->ordered(), $filters);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get all active base units for dropdown selects.
     */
    public function getBaseUnits(): Collection
    {
        return Unit::active()->base()->ordered()->get(['id', 'name', 'short_name']);
    }

    /**
     * Create a new unit.
     */
    public function create(array $data): Unit
    {
        if (($data['unit_type'] ?? 'base') === 'base') {
            $data['base_unit_id'] = null;
            $data['conversion_factor'] = null;
        }

        unset($data['unit_type']);

        $data['allow_decimal'] = !empty($data['allow_decimal']);

        return Unit::create($data);
    }

    /**
     * Update an existing unit.
     */
    public function update(Unit $unit, array $data): Unit
    {
        if (($data['unit_type'] ?? 'base') === 'base') {
            $data['base_unit_id'] = null;
            $data['conversion_factor'] = null;
        }

        unset($data['unit_type']);

        $data['allow_decimal'] = !empty($data['allow_decimal']);

        $unit->update($data);

        return $unit;
    }

    /**
     * Toggle a unit's active/inactive status.
     */
    public function toggleStatus(Unit $unit): Unit
    {
        $unit->update(['status' => $unit->status === 'active' ? 'inactive' : 'active']);

        return $unit;
    }

    /**
     * Soft-delete a unit. Reassign derived units first.
     */
    public function delete(Unit $unit): bool
    {
        if ($unit->derivedUnits()->count() > 0) {
            $unit->derivedUnits()->update([
                'base_unit_id' => null,
                'conversion_factor' => null,
            ]);
        }

        return $unit->delete();
    }

    /**
     * Get summary stats for the index page, respecting active filters.
     */
    public function getStats(array $filters = []): array
    {
        $query = $this->applyFilters(Unit::query(), $filters);

        return [
            'total' => (clone $query)->count(),
            'base'  => (clone $query)->whereNull('base_unit_id')->count(),
            'sub'   => (clone $query)->whereNotNull('base_unit_id')->count(),
        ];
    }

    /**
     * Apply shared filter logic to a query builder.
     */
    private function applyFilters($query, array $filters)
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('short_name', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filters['type'])) {
            if ($filters['type'] === 'base') {
                $query->whereNull('base_unit_id');
            } elseif ($filters['type'] === 'sub') {
                $query->whereNotNull('base_unit_id');
            }
        }

        if (!empty($filters['status']) && in_array($filters['status'], ['active', 'inactive'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }
}
