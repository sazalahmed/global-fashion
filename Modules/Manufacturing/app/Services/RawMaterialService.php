<?php

namespace Modules\Manufacturing\Services;

use Modules\Manufacturing\Models\RawMaterial;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class RawMaterialService
{
    /**
     * Get paginated list of raw materials with optional filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = RawMaterial::ordered();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filters['category']) && in_array($filters['category'], array_keys(RawMaterial::getCategories()))) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create a new raw material.
     */
    public function create(array $data): RawMaterial
    {
        $data['created_by'] = Auth::id();

        return RawMaterial::create($data);
    }

    /**
     * Update an existing raw material.
     */
    public function update(RawMaterial $material, array $data): RawMaterial
    {
        $material->update($data);

        return $material;
    }

    /**
     * Soft delete a raw material.
     */
    public function delete(RawMaterial $material): bool
    {
        return $material->delete();
    }

    /**
     * Find a raw material by ID.
     */
    public function find(int $id): RawMaterial
    {
        return RawMaterial::findOrFail($id);
    }

    /**
     * Get all active raw materials for dropdown selects.
     */
    public function getActiveRawMaterials(): Collection
    {
        return RawMaterial::active()->ordered()->get(['id', 'name', 'code', 'category', 'unit', 'cost_price']);
    }

    /**
     * Get raw materials filtered by category.
     */
    public function getByCategory(string $category): Collection
    {
        return RawMaterial::active()
            ->ordered()
            ->where('category', $category)
            ->get();
    }
}
