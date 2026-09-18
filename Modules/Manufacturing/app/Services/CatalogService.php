<?php

namespace Modules\Manufacturing\Services;

use Modules\Manufacturing\Models\Catalog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CatalogService
{
    /**
     * Get paginated list of catalogs with optional filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Catalog::ordered();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create a new catalog.
     */
    public function create(array $data): Catalog
    {
        return Catalog::create($data);
    }

    /**
     * Update an existing catalog.
     */
    public function update(Catalog $catalog, array $data): Catalog
    {
        $catalog->update($data);

        return $catalog;
    }

    /**
     * Soft delete a catalog.
     */
    public function delete(Catalog $catalog): bool
    {
        return $catalog->delete();
    }

    /**
     * Get all active catalogs for dropdown selects.
     */
    public function getActiveCatalogs(): Collection
    {
        return Catalog::active()->ordered()->get(['id', 'name', 'code']);
    }
}
